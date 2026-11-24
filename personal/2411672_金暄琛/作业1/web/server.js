const http = require("http");
const fs = require("fs");
const path = require("path");

const port = Number(process.env.PORT || 3000);
const publicDir = __dirname;
const messages = [
  {
    id: 1,
    author: "张同学",
    content: "GET 请求已经准备好了，打开 Network 面板就能看到响应数据。",
    createdAt: new Date(Date.now() - 3600_000).toISOString()
  },
  {
    id: 2,
    author: "李同学",
    content: "再提交一条留言，观察 POST 请求的 Payload 和 Response。",
    createdAt: new Date(Date.now() - 1800_000).toISOString()
  }
];
let nextId = 3;

const mimeTypes = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".png": "image/png",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon"
};

function sendJson(res, statusCode, payload) {
  const body = JSON.stringify(payload, null, 2);
  res.writeHead(statusCode, {
    "Content-Type": "application/json; charset=utf-8",
    "Content-Length": Buffer.byteLength(body),
    "Cache-Control": "no-store"
  });
  res.end(body);
}

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = "";
    req.on("data", (chunk) => {
      body += chunk;
      if (Buffer.byteLength(body) > 1024 * 1024) {
        reject(new Error("请求体超过 1 MB"));
        req.destroy();
      }
    });
    req.on("end", () => {
      try {
        resolve(body ? JSON.parse(body) : {});
      } catch {
        reject(new Error("请求体不是合法 JSON"));
      }
    });
    req.on("error", reject);
  });
}

const server = http.createServer(async (req, res) => {
  const requestUrl = new URL(req.url, `http://${req.headers.host || "localhost"}`);
  const startedAt = Date.now();

  res.on("finish", () => {
    const stamp = new Date().toLocaleTimeString("zh-CN", { hour12: false });
    console.log(`[${stamp}] ${req.method} ${requestUrl.pathname} -> ${res.statusCode} (${Date.now() - startedAt} ms)`);
  });

  if (requestUrl.pathname === "/api/messages" && req.method === "GET") {
    return sendJson(res, 200, {
      ok: true,
      method: "GET",
      count: messages.length,
      data: [...messages].sort((a, b) => b.id - a.id)
    });
  }

  if (requestUrl.pathname === "/api/messages" && req.method === "POST") {
    try {
      const body = await readJsonBody(req);
      const author = String(body.author || "").trim();
      const content = String(body.content || "").trim();

      if (!author || !content) {
        return sendJson(res, 400, { ok: false, error: "author 和 content 不能为空" });
      }
      if (author.length > 20 || content.length > 200) {
        return sendJson(res, 422, { ok: false, error: "昵称最多 20 字，留言最多 200 字" });
      }

      const message = {
        id: nextId++,
        author,
        content,
        createdAt: new Date().toISOString()
      };
      messages.push(message);

      return sendJson(res, 201, {
        ok: true,
        method: "POST",
        received: { author, content },
        data: message
      });
    } catch (error) {
      return sendJson(res, 400, { ok: false, error: error.message });
    }
  }

  if (req.method !== "GET" && req.method !== "HEAD") {
    return sendJson(res, 405, { ok: false, error: "Method Not Allowed" });
  }

  if (requestUrl.pathname === "/favicon.ico") {
    res.writeHead(204);
    return res.end();
  }

  const requestedPath = requestUrl.pathname === "/" ? "/index.html" : requestUrl.pathname;
  const filePath = path.resolve(publicDir, `.${decodeURIComponent(requestedPath)}`);

  if (!filePath.startsWith(publicDir + path.sep)) {
    return sendJson(res, 403, { ok: false, error: "Forbidden" });
  }

  fs.readFile(filePath, (error, content) => {
    if (error) {
      if (error.code === "ENOENT") {
        return sendJson(res, 404, { ok: false, error: "Not Found" });
      }
      return sendJson(res, 500, { ok: false, error: "Internal Server Error" });
    }

    const type = mimeTypes[path.extname(filePath).toLowerCase()] || "application/octet-stream";
    res.writeHead(200, {
      "Content-Type": type,
      "Content-Length": content.length,
      "Cache-Control": "no-cache"
    });
    if (req.method === "HEAD") return res.end();
    res.end(content);
  });
});

server.listen(port, "127.0.0.1", () => {
  console.log("");
  console.log("个人作业 1 演示服务已启动");
  console.log(`网页地址: http://127.0.0.1:${port}`);
  console.log("按 Ctrl+C 停止服务");
  console.log("");
});
