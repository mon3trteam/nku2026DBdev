/* 内容脚本 content.js 的 DOM 级测试
 *
 * 用 jsdom 把 tests/../web/demo.html 真加载一遍，注入 content.js，模拟弹窗发消息，
 * 检查取回的表格数据对不对，再喂给 SqlBuilder 看生成的 SQL 是否符合预期。
 *
 *     cd E:\web-db-lab\p1-frontend\tests
 *     node collector.test.js
 *
 * 说明：jsdom 不做排版，getBoundingClientRect() 恒为 0，所以下面把它打桩成固定尺寸；
 *      因此本测试覆盖的是「不可见」判定里的 display:none 分支，而不是零尺寸分支。
 *      另外 jsdom 没有 innerText，content.js 会自动回退到 textContent——
 *      真实 Chrome 走的是 innerText 分支，两者在这里的数据上结果相同。
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const EXT_DIR = path.join(__dirname, '..', 'extension');
const DEMO_PAGE = path.join(__dirname, '..', 'web', 'demo.html');

const SqlBuilder = require(path.join(EXT_DIR, 'lib', 'sqlbuilder.js'));

let passed = 0;
let failed = 0;

function check(name, condition, detail) {
  if (condition) { passed += 1; console.log('  ✓ ' + name); }
  else {
    failed += 1;
    console.log('  ✗ ' + name);
    if (detail !== undefined) { console.log('      实际: ' + detail); }
  }
}

function group(title) { console.log('\n' + title); }

// ------------------------------------------------------------ 搭环境

function setup() {
  const html = fs.readFileSync(DEMO_PAGE, 'utf8');
  const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true });
  const { window } = dom;

  // jsdom 无排版，给所有元素一个非零尺寸
  window.Element.prototype.getBoundingClientRect = function () {
    return { width: 400, height: 120, top: 0, left: 0, right: 400, bottom: 120, x: 0, y: 0 };
  };

  // 再挂一张被隐藏的表，用于验证「不可见表格会被跳过」
  const hidden = window.document.createElement('table');
  hidden.style.display = 'none';
  hidden.innerHTML = '<tr><th>不该</th></tr><tr><td>被抓到</td></tr>';
  window.document.body.appendChild(hidden);

  // 假的 chrome.runtime.onMessage
  const listeners = [];
  window.chrome = {
    runtime: { onMessage: { addListener: function (fn) { listeners.push(fn); } } }
  };

  window.eval(fs.readFileSync(path.join(EXT_DIR, 'content.js'), 'utf8'));
  return { window, listeners, dom };
}

function collect(window, listeners) {
  let response = null;
  listeners[0]({ type: 'COLLECT_TABLES' }, {}, function (r) { response = r; });
  return response;
}

// ------------------------------------------------------------ 开跑

const { window, listeners } = setup();

group('1. 注入与消息通道');
check('content.js 注册了监听器', listeners.length === 1, listeners.length);
{
  let pong = null;
  listeners[0]({ type: 'PING' }, {}, function (r) { pong = r; });
  check('PING 能回应', pong && pong.ok === true);
  check('PING 带回页面标题', /插件测试页/.test(pong.page.title), pong.page.title);
}
{
  const before = listeners.length;
  window.eval(fs.readFileSync(path.join(EXT_DIR, 'content.js'), 'utf8'));
  check('重复注入不会叠加监听器（有防重入标记）', listeners.length === before, listeners.length);
}

const res = collect(window, listeners);

group('2. 全页扫描结果');
check('返回 ok', res && res.ok === true);
check('扫描到 4 个 <table>', res.scanned === 4, res.scanned);
check('跳过 1 个不可见的表格', res.skipped.hidden === 1, JSON.stringify(res.skipped));
check('返回 3 个可用表格', res.tables.length === 3, res.tables.length);

const [t1, t2, t3] = res.tables;

group('3. 表一：标准 thead + caption + tfoot');
check('表头取自 thead', t1.headerSource === 'thead', t1.headerSource);
check('caption 取到表名', t1.caption === '商品库存清单', t1.caption);
check('标题取到最近的 <h2>', /表一：标准结构/.test(t1.heading), t1.heading);
check('表头 5 列', JSON.stringify(t1.headers) ===
      JSON.stringify(['ID', '名称', '分类', '数量', '单价']), JSON.stringify(t1.headers));
check('数据 4 行（tfoot 合计行被排除）', t1.rowCount === 4 && t1.allRows.length === 4, t1.rowCount);
check('第 3 行数据正确', JSON.stringify(t1.rows[2]) ===
      JSON.stringify(['3', '固态硬盘 1TB', '存储', '30', '549']), JSON.stringify(t1.rows[2]));
check('tfoot 的「合计」没有混进数据行',
      !t1.rows.some(r => r.join(' ').includes('合计')));

group('4. 表二：没有 thead，首行是普通 td');
check('标记为「猜的首行表头」', t2.headerSource === 'first-row-guess', t2.headerSource);
check('默认按表头处理', t2.firstRowIsHeader === true);
check('表头 = 首行', JSON.stringify(t2.headers) ===
      JSON.stringify(['学号', '姓名', '成绩']), JSON.stringify(t2.headers));
check('数据行排除首行 → 2 行', t2.rowCount === 2, t2.rowCount);
check('allRows 含首行 → 3 行（取消勾选表头时用）', t2.allRows.length === 3, t2.allRows.length);

group('5. 表三：两行 thead + colspan + 空单元格');
check('表头取 thead 最后一行（最贴近数据）',
      JSON.stringify(t3.headers) === JSON.stringify(['客户名称', '联系电话', '下单日期', '金额']),
      JSON.stringify(t3.headers));
check('数据 3 行', t3.rowCount === 3, t3.rowCount);
check('2 行表头没有多算成数据行', t3.rows.length === 3, t3.rows.length);
check('含单引号的单元格原样取出', t3.rows[0][0] === "Tom's 五金店", t3.rows[0][0]);
check('空单元格保留为空串', t3.rows[1][1] === '', JSON.stringify(t3.rows[1]));
check('日期单元格原样取出', t3.rows[0][2] === '2026-03-01', t3.rows[0][2]);

group('6. 取回的数据交给 SqlBuilder');
{
  const sql1 = SqlBuilder.build({
    headers: t1.headers, rows: t1.rows, tableName: 'product_stock',
    dialect: 'mysql', hasHeader: true, maxRows: 200
  }).sql;
  check('表一：ID 推断为 INTEGER PRIMARY KEY', sql1.includes('`ID` INTEGER PRIMARY KEY'));
  check('表一：单价推断为 REAL', sql1.includes('`单价` REAL'));
  check('表一：数量推断为 INTEGER', sql1.includes('`数量` INTEGER'));
  check('表一：INSERT 恰好 4 组值', (sql1.match(/^  \(/gm) || []).length === 4);
  check('表一：合计行不在 SQL 里', !sql1.includes('合计'));
  check('表一：27寸显示器的 1299.5 写成数值字面量', sql1.includes('1299.5'));
}
{
  const sql2 = SqlBuilder.build({
    headers: t2.headers, rows: t2.allRows, tableName: 'score',
    dialect: 'sqlite', hasHeader: false, maxRows: 200
  }).sql;
  check('表二（取消表头）：列名退化为 col_1…col_3',
        sql2.includes('"col_1"') && sql2.includes('"col_3"'));
  check('表二（取消表头）：3 行数据一个不少（首行没丢）',
        (sql2.match(/^  \(/gm) || []).length === 3, (sql2.match(/^  \(/gm) || []).length);
  // 首行「学号/姓名/成绩」这时是数据，整列文字数字混排 → 推断为 VARCHAR，所以数值也带引号
  check('表二（取消表头）：混排列推断为 VARCHAR，数值也加引号',
        sql2.includes("('2023001', '张三', '88')"),
        sql2.split('\n').find(l => l.includes('2023001')));
}
{
  const sql3 = SqlBuilder.build({
    headers: t3.headers, rows: t3.rows, tableName: 'customer_order',
    dialect: 'mysql', hasHeader: true, maxRows: 200
  }).sql;
  check('表三：Tom\'s 的单引号被翻倍转义', sql3.includes("'Tom''s 五金店'"));
  check('表三：空电话号写成 NULL', sql3.includes("('启明科技', NULL, '2026-03-05', 288.8)"),
        sql3.split('\n').find(l => l.includes('启明科技')));
  check('表三：下单日期推断为 DATE', sql3.includes('`下单日期` DATE'));
  check('表三：金额推断为 REAL', sql3.includes('`金额` REAL'));
  check('表三：电话列因含短横线退化为 VARCHAR',
        /`联系电话` VARCHAR\(\d+\)/.test(sql3),
        sql3.split('\n').find(l => l.includes('联系电话')));
}

group('7. 全流程的 SQL 快照（表一）');
{
  const r = SqlBuilder.build({
    headers: t1.headers, rows: t1.rows, tableName: 'product_stock',
    dialect: 'mysql', hasHeader: true, maxRows: 200,
    pageTitle: res.page.title, pageUrl: res.page.url
  });
  console.log('  ┌──── SQL ────');
  r.sql.split('\n').forEach(function (l) { console.log('  │ ' + l); });
  console.log('  └────────────');
  check('无告警', r.warnings.length === 0, JSON.stringify(r.warnings));
}

console.log('\n' + '='.repeat(52));
console.log('通过 ' + passed + ' 项，失败 ' + failed + ' 项');
process.exit(failed === 0 ? 0 : 1);
