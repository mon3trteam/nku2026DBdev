# 请求与响应示例

以下命令在演示服务运行时执行。

## GET 请求

```powershell
curl.exe -i http://127.0.0.1:3000/api/messages
```

预期响应：

```http
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
Cache-Control: no-store

{
  "ok": true,
  "method": "GET",
  "count": 2,
  "data": [
    {
      "id": 2,
      "author": "李同学",
      "content": "再提交一条留言，观察 POST 请求的 Payload 和 Response。",
      "createdAt": "..."
    }
  ]
}
```

## POST 请求

PowerShell 7：

```powershell
$body = @{ author = "金同学"; content = "这是我通过 POST 请求提交的留言。" } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:3000/api/messages" -ContentType "application/json; charset=utf-8" -Body $body
```

curl 版本：

```powershell
curl.exe -i -X POST "http://127.0.0.1:3000/api/messages" ^
  -H "Content-Type: application/json; charset=UTF-8" ^
  -d "{\"author\":\"金同学\",\"content\":\"这是我通过 POST 请求提交的留言。\"}"
```

预期响应：

```http
HTTP/1.1 201 Created
Content-Type: application/json; charset=utf-8
Cache-Control: no-store

{
  "ok": true,
  "method": "POST",
  "received": {
    "author": "金同学",
    "content": "这是我通过 POST 请求提交的留言。"
  },
  "data": {
    "id": 3,
    "author": "金同学",
    "content": "这是我通过 POST 请求提交的留言。",
    "createdAt": "..."
  }
}
```
