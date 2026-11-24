/* 表格数据 → SQL DDL/DML 生成器
 *
 * 这个文件不依赖任何浏览器 API，因此既能在插件弹窗里用（挂到 window.SqlBuilder），
 * 也能被 Node 直接 require 做单元测试（见 lib/sqlbuilder.test.js）。
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();          // Node
  } else {
    root.SqlBuilder = factory();         // 浏览器
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var INT_RE = /^[+-]?\d+$/;
  var NUM_RE = /^[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?$/;
  var DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
  var DATETIME_RE = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/;

  var MAX_IDENT = 60;

  // ------------------------------------------------------------ 基础工具

  function isBlank(v) {
    return v === null || v === undefined || String(v).trim() === '';
  }

  /** 按方言把标识符包起来。SQLite/PG 用双引号，MySQL 用反引号。 */
  function quoteIdent(name, dialect) {
    var open = dialect === 'mysql' ? '`' : '"';
    var clean = String(name).split(open).join('');   // 去掉会破坏引用的字符
    return open + clean + open;
  }

  /** 把任意表头/表名清洗成合法标识符；无法用时返回 fallback。 */
  function sanitizeIdentifier(name, fallback) {
    var s = (name === null || name === undefined) ? '' : String(name);
    s = s.replace(/[\r\n\t]+/g, ' ')
         .replace(/[`"'\[\];(){}<>]/g, '')          // 语法符号
         .replace(/[\s.\-/\\,，、]+/g, '_')          // 空白与常见分隔符
         .replace(/_{2,}/g, '_')
         .replace(/^_+|_+$/g, '');                  // 去掉首尾下划线
    if (!s) { return fallback; }
    if (/^\d/.test(s)) { s = 't_' + s; }
    return s.slice(0, MAX_IDENT);
  }

  /** 字符串字面量：单引号按 SQL 标准翻倍转义（SQLite / MySQL 都适用）。 */
  function escapeString(value) {
    return "'" + String(value).split("'").join("''") + "'";
  }

  // ------------------------------------------------------------ 类型推断

  /** 根据一列的所有取值推断列类型。 */
  function inferType(values) {
    var filled = values.filter(function (v) { return !isBlank(v); })
                       .map(function (v) { return String(v).trim(); });

    if (!filled.length) {
      return { type: 'VARCHAR(255)', kind: 'text', allEmpty: true };
    }
    if (filled.every(function (v) { return INT_RE.test(v); })) {
      return { type: 'INTEGER', kind: 'int', allEmpty: false };
    }
    if (filled.every(function (v) { return NUM_RE.test(v); })) {
      return { type: 'REAL', kind: 'real', allEmpty: false };
    }
    if (filled.every(function (v) { return DATE_RE.test(v); })) {
      return { type: 'DATE', kind: 'text', allEmpty: false };
    }
    if (filled.every(function (v) { return DATE_RE.test(v) || DATETIME_RE.test(v); })) {
      return { type: 'DATETIME', kind: 'text', allEmpty: false };
    }

    var maxLen = filled.reduce(function (m, v) { return Math.max(m, v.length); }, 0);
    var n = Math.min(Math.max(10, Math.ceil(maxLen * 1.2 / 10) * 10), 4000);
    return { type: 'VARCHAR(' + n + ')', kind: 'text', allEmpty: false };
  }

  /** 生成单个值的 SQL 字面量。 */
  function literalFor(value, kind) {
    if (isBlank(value)) { return 'NULL'; }
    var v = String(value).trim();
    if ((kind === 'int' || kind === 'real') && NUM_RE.test(v)) {
      return v;                                  // 数字直接写，不加引号
    }
    return escapeString(v);
  }

  // ------------------------------------------------------------ 组装

  /**
   * @param {Object} opts
   *   headers     {string[]}  表头（hasHeader 为 true 时使用）
   *   rows        {string[][]} 数据行（不含表头）
   *   tableName   {string}    目标表名
   *   hasHeader   {boolean}   首行是否表头
   *   dialect     {'mysql'|'sqlite'}
   *   dropIfExists{boolean}
   *   maxRows     {number}    最多导出的行数
   *   pageTitle   {string}
   *   pageUrl     {string}
   * @returns {{sql:string, columns:Object[], stats:Object, warnings:string[]}}
   */
  function build(opts) {
    var o = opts || {};
    var dialect = o.dialect === 'mysql' ? 'mysql' : 'sqlite';
    var warnings = [];
    var hasHeader = o.hasHeader !== false;

    var rawHeaders = (o.headers || []).map(function (h) { return String(h == null ? '' : h); });
    var bodyRows = (o.rows || []).map(function (r) { return (r || []).map(function (c) {
      return String(c == null ? '' : c);
    }); });

    var width = rawHeaders.length;
    bodyRows.forEach(function (r) { width = Math.max(width, r.length); });
    if (width === 0) {
      return { sql: '-- 该表格没有可导出的单元格。\n', columns: [],
               stats: { rows: 0, cols: 0, truncated: 0 }, warnings: ['表格为空。'] };
    }

    // 列名：优先用表头，否则 col_1、col_2 …
    var rawNames = [];
    for (var i = 0; i < width; i++) {
      var candidate = hasHeader ? (rawHeaders[i] || '') : '';
      rawNames.push(candidate);
    }

    var used = {};
    var columns = rawNames.map(function (raw, idx) {
      var base = sanitizeIdentifier(raw, 'col_' + (idx + 1));
      var name = base;
      var k = 2;
      while (used[name.toLowerCase()]) { name = base + '_' + k; k += 1; }
      used[name.toLowerCase()] = true;
      return { name: name, fromHeader: !!raw.trim() };
    });

    if (hasHeader) {
      columns.forEach(function (c, idx) {
        if (!c.fromHeader) {
          warnings.push('第 ' + (idx + 1) + ' 列表头为空，已命名为 ' + c.name + '。');
        }
      });
    } else {
      warnings.push('未使用表头，列名按 col_1 … col_' + width + ' 生成。');
    }

    // 补齐每行长度
    bodyRows.forEach(function (r) {
      while (r.length < width) { r.push(''); }
      if (r.length > width) { r.length = width; }
    });

    // 逐列推断类型
    var colValues = columns.map(function (_, idx) {
      return bodyRows.map(function (r) { return r[idx]; });
    });
    var types = colValues.map(function (values) { return inferType(values); });
    types.forEach(function (t, idx) {
      if (t.allEmpty) {
        warnings.push('列 ' + columns[idx].name + ' 全为空值，类型暂定 VARCHAR(255)，请按实际含义修改。');
      }
    });

    // 猜测主键：首列名为 id、类型 INTEGER、取值互不相同、且确实有数据
    var pkIndex = -1;
    if (/^id$/i.test(columns[0].name)) {
      var notInt = types[0].kind !== 'int';
      var seen = {};
      var duplicated = false;
      colValues[0].forEach(function (v) {
        if (seen[v]) { duplicated = true; }
        seen[v] = true;
      });
      if (!bodyRows.length) {
        warnings.push('首列名为 id，但表格没有数据行，未自动设为主键。');
      } else if (notInt) {
        warnings.push('首列名为 id，但取值不全是整数，未自动设为主键。');
      } else if (duplicated) {
        warnings.push('首列名为 id，但取值有重复，未自动设为主键。');
      } else {
        pkIndex = 0;
      }
    }

    // 行数截断
    var truncated = 0;
    var exportRows = bodyRows;
    var maxRows = parseInt(o.maxRows, 10);
    if (maxRows > 0 && bodyRows.length > maxRows) {
      truncated = bodyRows.length - maxRows;
      exportRows = bodyRows.slice(0, maxRows);
      warnings.push('共 ' + bodyRows.length + ' 行，按设置仅导出前 ' + maxRows + ' 行（省略 ' + truncated + ' 行）。');
    }

    var tableName = sanitizeIdentifier(o.tableName, 'table_1');
    var q = function (n) { return quoteIdent(n, dialect); };

    // ---- 文件头注释 ----
    var now = new Date();
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var stamp = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) +
                ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

    var lines = [];
    lines.push('-- 由「网页表格 → SQL 生成器」浏览器插件生成');
    lines.push('-- 方言     : ' + (dialect === 'mysql' ? 'MySQL' : 'SQLite / PostgreSQL'));
    if (o.pageTitle) { lines.push('-- 来源页面 : ' + o.pageTitle); }
    if (o.pageUrl)   { lines.push('-- 页面地址 : ' + o.pageUrl); }
    lines.push('-- 生成时间 : ' + stamp);
    lines.push('-- 数据规模 : ' + exportRows.length + ' 行 × ' + width + ' 列');
    lines.push('');

    if (o.dropIfExists !== false) {
      lines.push('DROP TABLE IF EXISTS ' + q(tableName) + ';');
      lines.push('');
    }

    // ---- CREATE TABLE ----
    lines.push('CREATE TABLE ' + q(tableName) + ' (');
    var colDefs = columns.map(function (c, idx) {
      var def = '  ' + q(c.name) + ' ' + types[idx].type;
      if (idx === pkIndex) { def += ' PRIMARY KEY'; }
      return def;
    });
    lines.push(colDefs.join(',\n'));
    lines.push(');');
    lines.push('');

    // ---- INSERT ----
    if (exportRows.length) {
      var colList = columns.map(function (c) { return q(c.name); }).join(', ');
      lines.push('INSERT INTO ' + q(tableName) + ' (' + colList + ') VALUES');
      var valueLines = exportRows.map(function (row) {
        var cells = columns.map(function (_, idx) {
          return literalFor(row[idx], types[idx].kind);
        });
        return '  (' + cells.join(', ') + ')';
      });
      lines.push(valueLines.join(',\n') + ';');
    } else {
      lines.push('-- 表格没有数据行，未生成 INSERT。');
    }
    lines.push('');

    return {
      sql: lines.join('\n'),
      columns: columns.map(function (c, idx) {
        return { name: c.name, type: types[idx].type, primaryKey: idx === pkIndex };
      }),
      stats: { rows: exportRows.length, cols: width, truncated: truncated },
      warnings: warnings
    };
  }

  return {
    build: build,
    inferType: inferType,
    sanitizeIdentifier: sanitizeIdentifier,
    quoteIdent: quoteIdent,
    escapeString: escapeString,
    literalFor: literalFor
  };
});
