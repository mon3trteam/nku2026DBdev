# -*- coding: utf-8 -*-
"""从 stdin 读入 SQL，在真的 SQLite 里执行，再把表和内容打印出来。

    node emit-sql.js | E:\\python\\python.exe run-in-sqlite.py

用来证明插件生成的 SQL 不只是「看起来像」，而是能被数据库接受、数据能完整落库。
"""

import sqlite3
import sys

sys.stdin.reconfigure(encoding="utf-8")
sys.stdout.reconfigure(encoding="utf-8")


def main():
    sql = sys.stdin.read()
    if not sql.strip():
        print("stdin 没有拿到 SQL")
        return 1

    print("收到的 SQL 共 {} 行 {} 字符".format(sql.count("\n") + 1, len(sql)))

    con = sqlite3.connect(":memory:")
    try:
        con.executescript(sql)          # 一次执行全部 DDL + DML，语法错会在这里抛
    except sqlite3.Error as exc:
        print("执行失败：{}".format(exc))
        return 1

    cur = con.cursor()
    names = [r[0] for r in cur.execute(
        "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")]
    if not names:
        print("没有创建任何表")
        return 1

    print("执行成功，共建表 {} 张：".format(len(names)))
    for name in names:
        info = list(cur.execute('PRAGMA table_info("{}")'.format(name)))
        count = cur.execute('SELECT COUNT(*) FROM "{}"'.format(name)).fetchone()[0]
        print()
        print("  [{}]  {} 列 / {} 行".format(name, len(info), count))
        for cid, cname, ctype, notnull, default, pk in info:
            mark = " PK" if pk else ""
            print("      {:<3} {:<12} {}{}".format(cid, cname, ctype, mark))
        print("      ---- 数据 ----")
        for row in cur.execute('SELECT * FROM "{}"'.format(name)):
            print("      " + repr(row))

    # 抽查：第一条订单的电话应当是 NULL
    row = cur.execute(
        'SELECT 客户名称, 联系电话 FROM customer_order WHERE 客户名称 = ?', ("启明科技",)).fetchone()
    print()
    print("抽查 customer_order 里「启明科技」的电话 = {!r}（预期 None）".format(row[1] if row else "记录不存在"))
    ok = row is not None and row[1] is None
    print("结果：{}".format("通过" if ok else "失败"))
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
