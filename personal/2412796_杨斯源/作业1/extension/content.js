/* 内容脚本 —— 在页面里收集 <table> 数据
 *
 * 注入方式：popup.js 打开时用 chrome.scripting.executeScript({files:['content.js']}) 按需注入。
 * 因为走的是 activeTab 权限，manifest 里不需要声明 <all_urls>，
 * 安装时不会出现「读取您在所有网站上的数据」这条警告。
 *
 * 本脚本只负责「取数据」，SQL 文本的生成全在 lib/sqlbuilder.js 里。
 */
(function () {
  'use strict';

  // 弹窗可能被反复打开，重复注入时不要叠加监听器
  if (window.__p1TableCollectorInstalled) { return; }
  window.__p1TableCollectorInstalled = true;

  var MAX_ROWS_PER_TABLE = 2000;   // 防止超大表格把消息通道撑爆

  // ---------------------------------------------------------- 取值工具

  function cellText(cell) {
    var text = (typeof cell.innerText === 'string' && cell.innerText !== '')
      ? cell.innerText
      : (cell.textContent || '');
    return text.replace(/\s+/g, ' ').trim();
  }

  /** 把一行单元格摊平：colspan 占的格子用空串补齐，保证各行列数一致。 */
  function expandCells(row) {
    var out = [];
    Array.prototype.forEach.call(row.cells, function (cell) {
      var span = Math.max(1, cell.colSpan || 1);
      out.push(cellText(cell));
      for (var i = 1; i < span; i++) { out.push(''); }
    });
    return out;
  }

  function isVisible(el) {
    if (!el || el.hidden) { return false; }
    var rect = el.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) { return false; }
    var style = window.getComputedStyle(el);
    return style.visibility !== 'hidden' && style.display !== 'none';
  }

  /** 表格前面最近的标题文字，用作表名候选。 */
  function headingBefore(table) {
    var headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
    var found = '';
    for (var i = 0; i < headings.length; i++) {
      var h = headings[i];
      var rel = h.compareDocumentPosition(table);
      if (rel & Node.DOCUMENT_POSITION_FOLLOWING) {
        found = (h.innerText || h.textContent || '').replace(/\s+/g, ' ').trim();
      } else {
        break;                              // querySelectorAll 是文档序，后面的都不用看了
      }
    }
    return found;
  }

  function captionOf(table) {
    var caption = table.querySelector('caption');
    if (caption) {
      var t = (caption.innerText || caption.textContent || '').trim();
      if (t) { return t; }
    }
    var aria = table.getAttribute('aria-label');
    return (aria || '').trim();
  }

  // ---------------------------------------------------------- 收集单张表

  function collectTable(table, index) {
    var rows = Array.prototype.slice.call(table.rows);   // 含 thead / tbody / tfoot

    var headers = [];
    var bodyRows = [];     // 数据行（不含表头）
    var allRows = [];      // 所有候选数据行（含可能被当作表头的那行）——用户取消勾选表头时用这个
    var firstRowIsHeader = false;
    var headerSource = 'none';

    var headRows = table.tHead ? Array.prototype.slice.call(table.tHead.rows) : [];
    var footRows = table.tFoot ? Array.prototype.slice.call(table.tFoot.rows) : [];

    // <tfoot> 是合计行，任何情况下都不当数据
    var dataRows = rows.filter(function (r) { return footRows.indexOf(r) === -1; });

    if (headRows.length) {
      // 有 thead：多行表头取最贴近数据的那一行
      headers = expandCells(headRows[headRows.length - 1]);
      headerSource = 'thead';
      firstRowIsHeader = true;
      allRows = dataRows.filter(function (r) { return headRows.indexOf(r) === -1; })
                        .map(expandCells);
      bodyRows = allRows;
    } else if (dataRows.length) {
      // 没有 thead：首行一律先当表头用，但标记成「猜的」，界面上提示可取消
      var first = dataRows[0];
      var allTh = first.cells.length > 0 &&
        Array.prototype.every.call(first.cells, function (c) { return c.tagName === 'TH'; });
      firstRowIsHeader = true;
      headerSource = allTh ? 'first-row-th' : 'first-row-guess';
      headers = expandCells(first);
      allRows = dataRows.map(expandCells);
      bodyRows = allRows.slice(1);
    }

    var colCount = headers.length;
    allRows.forEach(function (r) { colCount = Math.max(colCount, r.length); });

    var truncated = 0;
    if (bodyRows.length > MAX_ROWS_PER_TABLE) {
      truncated = bodyRows.length - MAX_ROWS_PER_TABLE;
      bodyRows = bodyRows.slice(0, MAX_ROWS_PER_TABLE);
      allRows = allRows.slice(0, MAX_ROWS_PER_TABLE);
    }

    return {
      index: index,
      caption: captionOf(table),
      heading: headingBefore(table),
      headers: headers,
      rows: bodyRows,
      allRows: allRows,
      totalRows: allRows.length + truncated,
      rowCount: bodyRows.length,
      colCount: colCount,
      firstRowIsHeader: firstRowIsHeader,
      headerSource: headerSource,
      truncated: truncated,
      id: table.id || ''
    };
  }

  // ---------------------------------------------------------- 全页扫描

  function collectAll() {
    var all = document.querySelectorAll('table');
    var tables = [];
    var hidden = 0;
    var empty = 0;

    Array.prototype.forEach.call(all, function (table) {
      if (!isVisible(table)) { hidden += 1; return; }
      if (!table.rows.length) { empty += 1; return; }
      tables.push(collectTable(table, tables.length));
    });

    return {
      ok: true,
      page: { title: document.title, url: location.href },
      tables: tables,
      skipped: { hidden: hidden, empty: empty },
      scanned: all.length
    };
  }

  // ---------------------------------------------------------- 消息接口

  chrome.runtime.onMessage.addListener(function (msg, sender, sendResponse) {
    if (!msg || typeof msg.type !== 'string') { return; }
    if (msg.type === 'PING') {
      sendResponse({ ok: true, page: { title: document.title, url: location.href } });
      return true;
    }
    if (msg.type === 'COLLECT_TABLES') {
      try {
        sendResponse(collectAll());
      } catch (err) {
        sendResponse({ ok: false, error: String(err && err.message ? err.message : err) });
      }
      return true;                          // 同步回包也返回 true，保持通道语义一致
    }
  });
})();
