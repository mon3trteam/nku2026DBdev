# 网页表格 → SQL 生成器

个人作业1 · 任务3 的浏览器插件。抓取当前网页里的 `<table>`，自动推断每列类型，
生成可直接执行的 `CREATE TABLE` + `INSERT` 语句，支持复制到剪贴板或下载 `.sql` 文件。

---

## 一、功能

| 功能 | 说明 |
|---|---|
| 抓取表格 | 一次抓取页面内所有**可见**的 `<table>`；下拉框里逐个切换 |
| 表头识别 | 优先用 `<thead>`（多行表头取最贴近数据的那行）；没有 `<thead>` 时把首行当表头**并提示这是猜测**，可一键取消 |
| 表尾排除 | `<tfoot>` 是合计行，不会被当成数据（这是很多同类工具会做错的地方） |
| `colspan` 展开 | 合并单元格按跨度补齐列，保证每行列数一致 |
| 类型推断 | 逐列扫描取值，推断为 `INTEGER` / `REAL` / `DATE` / `DATETIME` / `VARCHAR(n)` |
| 主键猜测 | 首列名为 `id`、取值为整数且互不重复时，自动加 `PRIMARY KEY`；不满足则给出原因提示 |
| 表名推断 | 依次尝试 `<caption>`、表格前最近的 `<h1>`~`<h6>`、`id` 属性，再清洗成合法标识符 |
| 方言切换 | MySQL（反引号）/ SQLite、PostgreSQL（双引号） |
| 安全转义 | 单元格里的单引号按 SQL 标准翻倍（`'` → `''`），表名/列名清洗后再加引号，杜绝 SQL 注入 |
| 复制 / 下载 | 一键复制到剪贴板；或下载成 `<表名>.sql` |
| 边界提示 | 空列、空表头、重名列、行数截断等都会在界面上给出黄色告警，而不是悄悄生成有问题的 SQL |

**示例**：对 `web/demo.html` 里的「商品库存清单」表，插件生成：

```sql
CREATE TABLE `商品库存清单` (
  `ID` INTEGER PRIMARY KEY,
  `名称` VARCHAR(10),
  `分类` VARCHAR(10),
  `数量` INTEGER,
  `单价` REAL
);

INSERT INTO `商品库存清单` (`ID`, `名称`, `分类`, `数量`, `单价`) VALUES
  (1, '机械键盘', '外设', 12, 399),
  (2, '27寸显示器', '外设', 5, 1299.5),
  (3, '固态硬盘 1TB', '存储', 30, 549),
  (4, '人体工学椅', '家具', 3, 880);
```

---

## 二、安装

1. Chrome / Edge 地址栏输入 `chrome://extensions`（Edge 是 `edge://extensions`）
2. 右上角打开 **开发者模式**
3. 点 **加载已解压的扩展程序**，选择本目录 `p1-frontend\extension`
4. 工具栏出现蓝色表格图标即安装成功（没有的话点拼图图标把它固定出来）

安装时**只会申请「当前标签页」权限**，不会出现「读取您在所有网站上的数据」这类警告——
因为脚本是用 `activeTab` + `chrome.scripting.executeScript` 按需注入的，详见下面的权限说明。

## 三、使用

1. 先启动本地服务（`capture-server\start.bat`），打开 <http://127.0.0.1:8000/demo.html>
   ——这是为演示准备的三张结构不同的表格
2. 点插件图标 → 选表格 → 调参数 → 复制或下载
3. 想验证 SQL 真能跑，把内容粘进 DB Browser for SQLite，或直接在 `tests/` 里跑往返测试

---

## 四、代码结构

```
extension/
├── manifest.json              # Manifest V3 清单：权限、弹窗入口、图标
├── popup.html / popup.css     # 弹窗界面
├── popup.js                   # 弹窗逻辑：注入脚本、取数据、调 SqlBuilder、复制/下载
├── content.js                 # 内容脚本：在页面里扫描并提取表格数据
├── lib/
│   ├── sqlbuilder.js          # 纯逻辑：类型推断 + SQL 文本生成（不依赖浏览器，可单测）
│   └── sqlbuilder.test.js     # 52 项单元测试
├── tools/make_icons.py        # 生成图标（纯标准库手写 PNG，不依赖 Pillow）
└── icons/                     # 16 / 48 / 128 三档图标
```

配套测试在**上一层**的 `p1-frontend/tests/`（不放在插件目录里，避免 Chrome 加载时把
`node_modules` 也算进扩展包）。

### 各文件做什么

**`content.js`** —— 只负责「取数据」，不生成 SQL。核心几步：

```js
// 1) 跳过不可见的表格（隐藏表格里的数据通常不是用户想导出的）
if (!isVisible(table)) { hidden += 1; return; }

// 2) <tfoot> 是合计行，任何情况下都不当数据
var dataRows = rows.filter(function (r) { return footRows.indexOf(r) === -1; });

// 3) 有 thead 就取最贴近数据的那一行；没有就把首行当表头并标记为「猜的」
if (headRows.length) {
  headers = expandCells(headRows[headRows.length - 1]);
  headerSource = 'thead';
} else if (dataRows.length) {
  firstRowIsHeader = true;
  headerSource = allTh ? 'first-row-th' : 'first-row-guess';
}

// 4) colspan 展开：占几个格子就补几个空串，保证各行列数一致
function expandCells(row) {
  var out = [];
  Array.prototype.forEach.call(row.cells, function (cell) {
    var span = Math.max(1, cell.colSpan || 1);
    out.push(cellText(cell));
    for (var i = 1; i < span; i++) { out.push(''); }
  });
  return out;
}
```

同时返回 `rows`（不含表头）和 `allRows`（含首行）两份数据。这样用户在界面上取消勾选
「首行作为表头」时，首行会退回去当数据行，**不会凭空丢掉一行**。

**`lib/sqlbuilder.js`** —— 纯函数，输入表格数组、输出 SQL 文本。类型推断：

```js
if (filled.every(v => INT_RE.test(v)))  { return { type: 'INTEGER', kind: 'int' }; }
if (filled.every(v => NUM_RE.test(v)))  { return { type: 'REAL',    kind: 'real' }; }
if (filled.every(v => DATE_RE.test(v))) { return { type: 'DATE',    kind: 'text' }; }
// 都不匹配 → VARCHAR(最长值长度 × 1.2，向上取整到 10)
```

转义（防注入的关键）：

```js
function escapeString(value) {
  return "'" + String(value).split("'").join("''") + "'";   // 单引号翻倍，SQL 标准做法
}
```

**`popup.js`** —— 串起整条链路。注入 + 通信部分：

```js
// activeTab 权限：只在用户点插件时注入，不需要 <all_urls>
await chrome.scripting.executeScript({ target: { tabId: tabId }, files: ['content.js'] });
const res = await chrome.tabs.sendMessage(tabId, { type: 'COLLECT_TABLES' });
```

### 权限说明（为什么敢只申请两个）

```json
"permissions": ["activeTab", "scripting"]
```

- `activeTab`：只在用户**主动点击插件图标**时，临时获得当前这一个标签页的访问权，
  切走标签页就失效。不需要在安装时声明 `<all_urls>`，用户不会看到「读取您在所有网站上的数据」。
- `scripting`：允许调用 `chrome.scripting.executeScript` 把 `content.js` 注进去。
- 没有申请 `downloads`：下载走的是 Blob + `<a download>`，不需要额外权限。

---

## 五、已知限制

1. **`rowspan` 没有展开**，跨行合并的单元格会被算作该行的普通单元格。
2. 多行表头只取最后一行，上面的分组标题（如「客户信息 / 订单」）会被丢弃。
3. 类型推断是**保守**的：一列里只要有一个非数字值，整列就退化成 `VARCHAR`。
   这是刻意的——宁可让用户手动收紧类型，也不要生成插入失败的表。
4. 单次每张表最多抓 2000 行，超出部分丢弃并在界面上告警。
5. 只处理 `<table>`；用 `<div>` 拼出来的伪表格抓不到。
6. 生成的 SQL 不含外键、索引、约束，列类型也可能需要按业务含义微调——
   它的定位是「省掉手敲 DDL 的机械劳动」，不是完整的数据库设计工具。

---

## 六、测试

```bat
cd E:\web-db-lab\p1-frontend\tests
run-all.bat
```

| 测试 | 规模 | 覆盖什么 |
|---|---|---|
| `extension/lib/sqlbuilder.test.js` | 52 项 | 类型推断、转义、SQL 注入防护、命名清洗、主键猜测、行数截断、方言差异 |
| `tests/collector.test.js` | 41 项 | 用 jsdom 真加载 `demo.html`，跑完整条链路：扫描 → 表头识别 → tfoot 排除 → colspan 展开 → 生成 SQL |
| `tests/run-in-sqlite.py` | 3 张表 | 把生成的 SQL 丢进**真的 SQLite** 执行，再查回来核对数据 |

第三项是关键：它证明生成的不是「看起来像 SQL 的字符串」，而是**数据库真的能执行、
数据能完整落库**的 SQL。
