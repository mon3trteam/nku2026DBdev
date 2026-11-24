# -*- coding: utf-8 -*-
"""P1 任务1 —— 本地抓包演示服务

零第三方依赖，只用 Python 标准库。启动：

    E:\\python\\python.exe server.py

默认监听 http://127.0.0.1:8000

接口（有意设计成 REST 风格，P2 升级为数据库后端时 URL 与 JSON 结构保持不变）：
    GET    /api/items?keyword=&page=1&size=10   列表，参数放 URL query —— 演示 GET 的传参位置
    GET    /api/items/<id>                      单条
    POST   /api/items                           新增，参数放 JSON 请求体 —— 演示 POST 的传参位置
    PUT    /api/items/<id>                      修改
    DELETE /api/items/<id>                      删除

静态资源：
    GET /            -> ../web/index.html
    GET /static/*    -> ../web/static/*

抓包证据：
    控制台实时打印每个请求的「请求行 / 请求头 / 请求体」；
    同时以 JSON Lines 追加写入 logs/requests.log，可直接作为报告素材。
"""

import json
import mimetypes
import os
import sys
import urllib.parse
from datetime import datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

ROOT = os.path.dirname(os.path.abspath(__file__))
WEB_DIR = os.path.normpath(os.path.join(ROOT, "..", "web"))
DATA_FILE = os.path.join(ROOT, "data.json")
LOG_FILE = os.path.join(ROOT, "logs", "requests.log")
HOST, PORT = "127.0.0.1", 8000

SEED = [
    {"id": 1, "name": "机械键盘", "category": "外设", "qty": 12, "price": 399.0},
    {"id": 2, "name": "27寸显示器", "category": "外设", "qty": 5, "price": 1299.0},
    {"id": 3, "name": "固态硬盘 1TB", "category": "存储", "qty": 30, "price": 549.0},
    {"id": 4, "name": "人体工学椅", "category": "家具", "qty": 3, "price": 880.0},
    {"id": 5, "name": "USB-C 扩展坞", "category": "外设", "qty": 20, "price": 259.0},
]


# ---------------------------------------------------------------- 数据持久化

def load_items():
    if os.path.exists(DATA_FILE):
        with open(DATA_FILE, encoding="utf-8") as f:
            return json.load(f)
    save_items(SEED)
    return [dict(x) for x in SEED]


def save_items(items):
    with open(DATA_FILE, "w", encoding="utf-8") as f:
        json.dump(items, f, ensure_ascii=False, indent=2)


ITEMS = load_items()
NEXT_ID = max((x["id"] for x in ITEMS), default=0) + 1


# ---------------------------------------------------------------- 请求日志

def record(entry):
    """把一条请求记录写到 logs/requests.log（JSON Lines）并打印到控制台。"""
    entry["time"] = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    os.makedirs(os.path.dirname(LOG_FILE), exist_ok=True)
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write(json.dumps(entry, ensure_ascii=False) + "\n")

    print("=" * 72)
    print("[{}] {} {} {}".format(entry["time"], entry["method"], entry["path"], entry["http"]))
    print("  客户端     : {}:{}".format(entry["client"][0], entry["client"][1]))
    if entry.get("query"):
        print("  Query 参数 : " + entry["query"])
    if entry.get("body"):
        print("  请求体     : " + entry["body"])
    print("  --- 请求头 ---")
    for k, v in entry["headers"].items():
        print("    {}: {}".format(k, v))
    sys.stdout.flush()


# ---------------------------------------------------------------- 业务逻辑

def handle_items_list(query):
    keyword = (query.get("keyword", [""])[0] or "").strip()
    page = max(int(query.get("page", ["1"])[0] or 1), 1)
    size = min(max(int(query.get("size", ["10"])[0] or 10), 1), 100)

    rows = ITEMS
    if keyword:
        needle = keyword.lower()
        rows = [x for x in rows
                if needle in str(x["name"]).lower() or needle in str(x["category"]).lower()]
    start = (page - 1) * size
    return 200, {
        "code": 0,
        "message": "ok",
        "query": {"keyword": keyword, "page": page, "size": size},
        "total": len(rows),
        "data": rows[start:start + size],
    }


def handle_item_create(payload):
    global NEXT_ID
    name = str(payload.get("name", "")).strip()
    if not name:
        return 400, {"code": 400, "message": "字段 name 不能为空"}

    item = {
        "id": NEXT_ID,
        "name": name,
        "category": str(payload.get("category", "未分类")).strip() or "未分类",
        "qty": int(payload.get("qty", 0) or 0),
        "price": float(payload.get("price", 0) or 0),
    }
    ITEMS.append(item)
    NEXT_ID += 1
    save_items(ITEMS)
    return 201, {"code": 0, "message": "created", "data": item}


def handle_item_update(item_id, payload):
    for item in ITEMS:
        if item["id"] == item_id:
            for field in ("name", "category", "qty", "price"):
                if field in payload:
                    item[field] = payload[field]
            item["qty"] = int(item["qty"] or 0)
            item["price"] = float(item["price"] or 0)
            save_items(ITEMS)
            return 200, {"code": 0, "message": "updated", "data": item}
    return 404, {"code": 404, "message": "记录 {} 不存在".format(item_id)}


def handle_item_delete(item_id):
    for index, item in enumerate(ITEMS):
        if item["id"] == item_id:
            ITEMS.pop(index)
            save_items(ITEMS)
            return 200, {"code": 0, "message": "deleted", "data": item}
    return 404, {"code": 404, "message": "记录 {} 不存在".format(item_id)}


# ---------------------------------------------------------------- HTTP 处理器

class Handler(BaseHTTPRequestHandler):
    server_version = "P1CaptureServer/1.0"
    protocol_version = "HTTP/1.1"  # 让抓包里能看到 Connection: keep-alive

    # ---- 工具方法 ----------------------------------------------------

    def _read_body(self):
        length = int(self.headers.get("Content-Length") or 0)
        return self.rfile.read(length) if length else b""

    def _log(self, body):
        record({
            "method": self.command,
            "path": self.path,
            "http": self.request_version,
            "client": self.client_address,
            "query": urllib.parse.urlparse(self.path).query,
            "body": body.decode("utf-8", "replace"),
            "headers": {k: v for k, v in self.headers.items()},
        })

    def _send(self, status, content_type, payload, extra_headers=None):
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(payload)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, X-Demo-Token")
        for k, v in (extra_headers or {}).items():
            self.send_header(k, v)
        self.end_headers()
        self.wfile.write(payload)

    def _send_json(self, status, obj, extra_headers=None):
        payload = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self._send(status, "application/json; charset=utf-8", payload, extra_headers)

    def _parse_json_body(self, body):
        if not body:
            return {}
        try:
            return json.loads(body.decode("utf-8"))
        except (ValueError, UnicodeDecodeError):
            return None

    def _serve_static(self, path):
        rel = "index.html" if path in ("/", "") else path.lstrip("/")
        if rel.startswith("static/"):
            full = os.path.normpath(os.path.join(WEB_DIR, rel))
        else:
            full = os.path.normpath(os.path.join(WEB_DIR, rel))

        # 防目录穿越
        if not full.startswith(WEB_DIR + os.sep) and full != WEB_DIR:
            return self._send_json(403, {"code": 403, "message": "forbidden"})
        if not os.path.isfile(full):
            return self._send_json(404, {"code": 404, "message": "not found: " + path})

        ctype = mimetypes.guess_type(full)[0] or "application/octet-stream"
        if ctype.startswith("text/") or ctype in ("application/javascript",):
            ctype += "; charset=utf-8"
        with open(full, "rb") as f:
            self._send(200, ctype, f.read())

    # ---- 路由 --------------------------------------------------------

    def do_GET(self):
        self._log(b"")
        parsed = urllib.parse.urlparse(self.path)
        query = urllib.parse.parse_qs(parsed.query, keep_blank_values=True)
        path = parsed.path

        if path == "/api/items":
            return self._send_json(*handle_items_list(query))
        if path.startswith("/api/items/"):
            try:
                item_id = int(path.rsplit("/", 1)[1])
            except ValueError:
                return self._send_json(400, {"code": 400, "message": "id 必须是整数"})
            for item in ITEMS:
                if item["id"] == item_id:
                    return self._send_json(200, {"code": 0, "message": "ok", "data": item})
            return self._send_json(404, {"code": 404, "message": "记录 {} 不存在".format(item_id)})
        return self._serve_static(path)

    def do_POST(self):
        body = self._read_body()
        self._log(body)
        parsed = urllib.parse.urlparse(self.path)
        payload = self._parse_json_body(body)

        if payload is None:
            return self._send_json(400, {"code": 400, "message": "请求体不是合法 JSON"})
        if parsed.path == "/api/items":
            # 回显一个自定义响应头，方便在抓包里对比请求头与响应头
            status, obj = handle_item_create(payload)
            return self._send_json(status, obj, {"X-Demo-Server": "P1CaptureServer"})
        return self._send_json(404, {"code": 404, "message": "not found: " + parsed.path})

    def do_PUT(self):
        body = self._read_body()
        self._log(body)
        parsed = urllib.parse.urlparse(self.path)
        payload = self._parse_json_body(body)

        if payload is None:
            return self._send_json(400, {"code": 400, "message": "请求体不是合法 JSON"})
        if parsed.path.startswith("/api/items/"):
            try:
                item_id = int(parsed.path.rsplit("/", 1)[1])
            except ValueError:
                return self._send_json(400, {"code": 400, "message": "id 必须是整数"})
            return self._send_json(*handle_item_update(item_id, payload))
        return self._send_json(404, {"code": 404, "message": "not found: " + parsed.path})

    def do_DELETE(self):
        self._log(b"")
        parsed = urllib.parse.urlparse(self.path)
        if parsed.path.startswith("/api/items/"):
            try:
                item_id = int(parsed.path.rsplit("/", 1)[1])
            except ValueError:
                return self._send_json(400, {"code": 400, "message": "id 必须是整数"})
            return self._send_json(*handle_item_delete(item_id))
        return self._send_json(404, {"code": 404, "message": "not found: " + parsed.path})

    def do_OPTIONS(self):
        self._log(b"")
        self._send(204, "text/plain", b"")

    # ---- 日志：关掉默认的逐行打印，避免和 record() 的输出重复 -------

    def log_message(self, fmt, *args):
        pass


def main():
    mimetypes.add_type("application/javascript", ".js")
    mimetypes.add_type("text/css", ".css")
    server = ThreadingHTTPServer((HOST, PORT), Handler)
    print("P1 抓包服务已启动： http://{}:{}".format(HOST, PORT))
    print("演示页面：        http://{}:{}/".format(HOST, PORT))
    print("请求日志：        {}".format(LOG_FILE))
    print("按 Ctrl+C 停止。")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n已停止。")
        server.server_close()


if __name__ == "__main__":
    main()
