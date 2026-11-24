/* 把 demo.html 里的表格抓出来，生成 SQLite 方言的 SQL 并打到 stdout。
 *
 * 配合 run-in-sqlite.py 使用，验证「插件生成的 SQL 是真能执行的」：
 *     node emit-sql.js | E:\python\python.exe run-in-sqlite.py
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const EXT_DIR = path.join(__dirname, '..', 'extension');
const SqlBuilder = require(path.join(EXT_DIR, 'lib', 'sqlbuilder.js'));

const dom = new JSDOM(fs.readFileSync(path.join(__dirname, '..', 'web', 'demo.html'), 'utf8'),
                      { runScripts: 'outside-only', pretendToBeVisual: true });
const { window } = dom;
window.Element.prototype.getBoundingClientRect = function () {
  return { width: 400, height: 120, top: 0, left: 0, right: 400, bottom: 120, x: 0, y: 0 };
};
const listeners = [];
window.chrome = { runtime: { onMessage: { addListener: function (fn) { listeners.push(fn); } } } };
window.eval(fs.readFileSync(path.join(EXT_DIR, 'content.js'), 'utf8'));

let res = null;
listeners[0]({ type: 'COLLECT_TABLES' }, {}, function (r) { res = r; });

const NAMES = ['product_stock', 'score', 'customer_order'];

res.tables.forEach(function (t, i) {
  const out = SqlBuilder.build({
    headers: t.headers,
    rows: t.rows,
    tableName: NAMES[i],
    dialect: 'sqlite',
    hasHeader: true,
    maxRows: 500,
    pageTitle: res.page.title,
    pageUrl: 'http://127.0.0.1:8000/demo.html'
  });
  process.stdout.write(out.sql + '\n');
  out.warnings.forEach(function (w) { process.stderr.write('[警告] ' + w + '\n'); });
});
