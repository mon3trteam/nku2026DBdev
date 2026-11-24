/* sqlbuilder.js 的单元测试 —— 用 Node 直接跑，不需要浏览器：
 *     node lib/sqlbuilder.test.js
 *
 * 插件的界面部分没法脱离浏览器测，但「表格数据 → SQL 文本」这段纯逻辑可以，
 * 所以把它单独放在 lib/sqlbuilder.js 里，这里覆盖类型推断、转义、命名、截断等边界。
 */
'use strict';

const SB = require('./sqlbuilder.js');

let passed = 0;
let failed = 0;

function check(name, condition, detail) {
  if (condition) {
    passed += 1;
    console.log('  ✓ ' + name);
  } else {
    failed += 1;
    console.log('  ✗ ' + name);
    if (detail !== undefined) { console.log('      实际: ' + detail); }
  }
}

function group(title) {
  console.log('\n' + title);
}

const SAMPLE = {
  headers: ['ID', '名称', '分类', '数量', '单价'],
  rows: [
    ['1', '机械键盘', '外设', '12', '399'],
    ['2', '27寸显示器', '外设', '5', '1299.5'],
  ]
};

// ------------------------------------------------------------ 1. 基本生成
group('1. 基本建表与插入');
{
  const r = SB.build(Object.assign({ tableName: 'inventory', dialect: 'mysql' }, SAMPLE));
  check('包含 CREATE TABLE', r.sql.includes('CREATE TABLE `inventory` ('));
  check('包含 DROP TABLE IF EXISTS', r.sql.includes('DROP TABLE IF EXISTS `inventory`;'));
  check('INTEGER 列判定正确', r.sql.includes('`ID` INTEGER PRIMARY KEY'));
  check('REAL 列判定正确（5 与 1299.5 混合）', r.sql.includes('`单价` REAL'));
  check('VARCHAR 列判定正确（最长 6 字符 → 10）', r.sql.includes('`名称` VARCHAR(10)'));
  check('列数 = 5', r.stats.cols === 5, r.stats.cols);
  check('行数 = 2', r.stats.rows === 2, r.stats.rows);
  check('INSERT 只有一条语句（多值形式）',
        (r.sql.match(/INSERT INTO/g) || []).length === 1);
  check('INSERT 列出全部列名',
        r.sql.includes('INSERT INTO `inventory` (`ID`, `名称`, `分类`, `数量`, `单价`) VALUES'));
  check('数字不加引号', r.sql.includes('(1, \'机械键盘\', \'外设\', 12, 399)'), r.sql.split('\n').pop());
  check('无告警', r.warnings.length === 0, JSON.stringify(r.warnings));
}

// ------------------------------------------------------------ 2. 无表头
group('2. 首行不作为表头');
{
  const r = SB.build({ tableName: 't', hasHeader: false,
                       headers: [], rows: [['a', 'b'], ['c', 'd']] });
  check('列名为 col_1 / col_2',
        r.sql.includes('"col_1" VARCHAR(10)') && r.sql.includes('"col_2" VARCHAR(10)'));
  check('两行都进了 INSERT', (r.sql.match(/^  \(/gm) || []).length === 2);
  check('提示未使用表头', r.warnings.some(w => w.includes('未使用表头')));
}

// ------------------------------------------------------------ 3. 转义
group('3. 字符串转义');
{
  const r = SB.build({ tableName: 't', rows: [["Tom's 键盘"]], headers: ['名称'] });
  check("单引号翻倍转义", r.sql.includes("'Tom''s 键盘'"), r.sql.split('\n').slice(-2)[0]);
  check("没有裸的未转义引号导致语法错误",
        !/VALUES[\s\S]*'Tom's/.test(r.sql));
}
{
  check('反斜杠原样保留（SQL 标准不做反斜杠转义）',
        SB.escapeString('a\\b') === "'a\\b'", SB.escapeString('a\\b'));
  check('标识符里的反引号被剥掉',
        SB.quoteIdent('a`b', 'mysql') === '`ab`', SB.quoteIdent('a`b', 'mysql'));
}

// ------------------------------------------------------------ 4. 边界与告警
group('4. 边界情况与告警');
{
  const r = SB.build({ tableName: 't', headers: ['A', 'B'], rows: [['1', '']] });
  check('全空列退回 VARCHAR(255) + 告警',
        r.sql.includes('"B" VARCHAR(255)') && r.warnings.some(w => w.includes('全为空值')));
}
{
  const r = SB.build({ tableName: 't', headers: ['名称', '名称', ''], rows: [['a', 'b', 'c']] });
  check('重名列自动加后缀', r.sql.includes('"名称_2"'), r.sql.split('\n').find(l => l.includes('名称')));
  check('空表头退回 col_N', r.sql.includes('"col_3"'));
}
{
  const r = SB.build({ tableName: 't', headers: ['A', 'B'], rows: [['1']] });
  check('短行自动补齐为 NULL', r.sql.includes('(1, NULL)'), r.sql.split('\n').pop());
}
{
  const r = SB.build({ tableName: 't', headers: [], rows: [] });
  check('空表格不崩，返回占位注释',
        r.stats.rows === 0 && r.sql.includes('没有可导出的单元格'));
}

// ------------------------------------------------------------ 5. 类型推断
group('5. 类型推断');
{
  check('整数列 → INTEGER', SB.inferType(['1', '2', '-3']).type === 'INTEGER');
  check('小数列 → REAL', SB.inferType(['1', '2.5']).type === 'REAL');
  check('科学计数法 → REAL', SB.inferType(['1e3', '2']).type === 'REAL');
  check('纯日期 → DATE', SB.inferType(['2026-01-02', '2026-03-04']).type === 'DATE');
  check('日期混合时间 → DATETIME',
        SB.inferType(['2026-01-02', '2026-03-04 10:00:00']).type === 'DATETIME');
  check('带前导零的编号仍算整数（如 007）', SB.inferType(['007', '008']).type === 'INTEGER');
  check('数字与文字混排 → VARCHAR', SB.inferType(['1', 'A']).type === 'VARCHAR(10)');
  check('全空 → VARCHAR(255) 且标记 allEmpty',
        SB.inferType(['', '  ']).type === 'VARCHAR(255)' && SB.inferType(['', '']).allEmpty === true);
  check('长文本长度按 1.2 倍向上取整到 10',
        SB.inferType(['x'.repeat(31)]).type === 'VARCHAR(40)', SB.inferType(['x'.repeat(31)]).type);
}

// ------------------------------------------------------------ 6. 方言与命名
group('6. 方言与标识符清洗');
{
  const my = SB.build({ tableName: 'my table', dialect: 'mysql', headers: ['商品 名'], rows: [['x']] });
  check('MySQL 用反引号且表名空格转下划线',
        my.sql.includes('CREATE TABLE `my_table` (') && my.sql.includes('`商品_名`'),
        my.sql.split('\n').filter(l => l.includes('CREATE') || l.includes('商品')).join(' | '));

  const lite = SB.build({ tableName: 'my table', dialect: 'sqlite', headers: ['a'], rows: [['x']] });
  check('SQLite 用双引号', lite.sql.includes('CREATE TABLE "my_table" ('));
  check('SQLite 不生成 DROP（显式关闭）',
        SB.build({ tableName: 't', dropIfExists: false, headers: ['a'], rows: [['x']] })
          .sql.indexOf('DROP TABLE') === -1);
}
{
  check('以数字开头加 t_ 前缀', SB.sanitizeIdentifier('123abc', 'x') === 't_123abc');
  check('空字符串用 fallback', SB.sanitizeIdentifier('   ', 'col_1') === 'col_1');
  check('超长名截到 60 字符', SB.sanitizeIdentifier('z'.repeat(100), 'c').length === 60);

  const cleaned = SB.sanitizeIdentifier('a";DROP TABLE x;--', 'c');
  check('表示结尾的引号与分号被剥掉', !/[;"'`]/.test(cleaned), cleaned);
  check('清洗后仍是合法标识符', /^[A-Za-z_]/.test(cleaned), cleaned);

  // 注入尝试：恶意表名 / 恶意单元格都不该产生第二条语句
  const injName = SB.build({ tableName: 'x"; DROP TABLE users; --',
                             headers: ['a'], rows: [['1']] });
  check('恶意表名只留下一个标识符，不生成额外的 DROP',
        (injName.sql.match(/DROP TABLE/g) || []).length === 1,
        injName.sql.split('\n').slice(0, 8).join(' | '));

  const injCell = SB.build({ tableName: 't',
                             headers: ['名称'],
                             rows: [["'); DROP TABLE users; --"]] });
  check('恶意单元格内容被引号包裹，分号失效',
        injCell.sql.includes("'''); DROP TABLE users; --'"),
        injCell.sql.split('\n').pop());
}

// ------------------------------------------------------------ 7. 主键与截断
group('7. 主键猜测与行数截断');
{
  const ok = SB.build({ tableName: 't', headers: ['id', '名称'], rows: [['1', 'a'], ['2', 'b']] });
  check('id 列唯一且为整数 → 设为主键', ok.sql.includes('"id" INTEGER PRIMARY KEY'));

  const dup = SB.build({ tableName: 't', headers: ['id', '名称'], rows: [['1', 'a'], ['1', 'b']] });
  check('id 重复 → 不设主键并告警',
        !dup.sql.includes('PRIMARY KEY') && dup.warnings.some(w => w.includes('取值有重复')));

  const txt = SB.build({ tableName: 't', headers: ['id', '名称'], rows: [['A1', 'a']] });
  check('id 非整数 → 不设主键并告警',
        !txt.sql.includes('PRIMARY KEY') && txt.warnings.some(w => w.includes('不全是整数')));

  const cut = SB.build({ tableName: 't', maxRows: 2, headers: ['n'],
                         rows: [['1'], ['2'], ['3'], ['4']] });
  check('超出 maxRows 被截断', cut.stats.rows === 2 && cut.stats.truncated === 2);
  check('截断会告警', cut.warnings.some(w => w.includes('仅导出前 2 行')));
}

// ------------------------------------------------------------ 8. 输出卫生
group('8. 输出卫生');
{
  const r = SB.build(Object.assign({ tableName: 'inventory' }, SAMPLE));
  check('以注释头开始', r.sql.startsWith('-- 由「网页表格 → SQL 生成器」浏览器插件生成'));
  check('包含生成时间', /-- 生成时间 : \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(r.sql));
  check('包含数据规模', r.sql.includes('-- 数据规模 : 2 行 × 5 列'));
  check('INSERT 语句以分号结束', r.sql.trimEnd().endsWith(';'));
  check('列清单返回类型信息',
        r.columns.length === 5 && r.columns[4].type === 'REAL' && r.columns[0].primaryKey === true);
}

// ------------------------------------------------------------ 汇总
console.log('\n' + '='.repeat(52));
console.log('通过 ' + passed + ' 项，失败 ' + failed + ' 项');
process.exit(failed === 0 ? 0 : 1);
