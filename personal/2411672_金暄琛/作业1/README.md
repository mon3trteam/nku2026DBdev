# 个人作业 1 - Web 前端初探

提交目录：`作业1(2411672_金暄琛)`  
演示网页：`web/`  
浏览器插件：`extension/`  

## 1. 启动演示网页

在 PowerShell 中执行：

```powershell
cd "<解压目录>\web"
node server.js
```

浏览器打开：

```text
http://127.0.0.1:3000
```

不要直接双击 `index.html`。必须通过本地服务器访问，否则 `/api/messages` 接口无法工作。

## 2. 记录 GET 请求

1. 打开页面后按 `F12`，进入 **Network（网络）**，选择 **Fetch/XHR**。
2. 勾选 **Preserve log（保留日志）**，然后刷新页面。
3. 点击名称为 `messages` 的请求。
4. 分别截取以下内容：
   - **Headers**：Request URL、Request Method: GET、Status Code: 200。
   - **Response / Preview**：返回的 JSON，其中包含 `method: "GET"`、`count` 和 `data`。
5. 本次提交已在报告第 2 节写出请求与响应数据包，并在 `docs/get-packet.txt` 保存原始响应；截图不是 PDF 要求的必需项。

## 3. 记录 POST 请求

1. 保持 Network 面板打开。
2. 在页面表单中输入留言，点击“发送留言（POST）”。
3. 点击新出现的 `messages` 请求，确认：
   - Request Method: POST
   - Status Code: 201
   - Request Headers 中的 `Content-Type: application/json; charset=UTF-8`
   - Payload 中包含 `author` 和 `content`
   - Response 中包含 `method: "POST"`、`received` 和新建留言的 `data`
4. 本次提交已在报告第 3 节写出请求、Payload 和响应数据包，并在 `docs/post-packet.txt` 保存原始响应；截图不是 PDF 要求的必需项。

## 4. 截图 jQuery 事件前后状态

1. 滚动到“03 jQuery 事件演示”卡片，截取点击前的页面，保存为 `screenshots/06_jquery_before.png`。
2. 点击“触发 jQuery 事件”按钮。
3. 截取点击后的页面，保存为 `screenshots/07_jquery_after.png`。
4. 报告中粘贴代码时使用 `web/app.js` 中 `$("#eventDemo").on("click", ...)` 这一段。

核心语句：

```javascript
$("#eventDemo").on("click", handler);
$("#eventCard").addClass("active");
$("#eventStatus").text("jQuery 事件已触发");
$("#demoBadge").text("已触发");
```

## 5. 安装并演示浏览器插件

1. Chrome/Edge 地址栏打开 `chrome://extensions/` 或 `edge://extensions/`。
2. 打开右上角的“开发者模式”。
3. 点击“加载已解压的扩展程序”，选择：
   `<解压目录>\extension`
4. 回到演示页面，用鼠标选中一段文字。
5. 点击浏览器工具栏中的“页面标注助手”，点击“高亮选中文字”。
6. 截取插件弹窗与网页高亮效果，保存为 `screenshots/08_extension.png`。

插件功能：

- 高亮当前网页中选中的文字。
- 读取当前选中文字并显示在插件弹窗中。
- 清除本插件在当前页面添加的全部高亮。

插件关键代码见：

- `extension/manifest.json`：Manifest V3 配置和权限。
- `extension/popup.js`：调用 `chrome.scripting.executeScript` 操作当前页面。

## 6. 报告结构建议

1. 作业目标与运行环境
2. GET 请求与响应数据包
3. POST 请求与响应数据包
4. jQuery 事件前后状态
5. 浏览器插件功能、代码与运行截图
6. 实验总结

## 7. 最终提交

将报告和插件一起压缩，命名示例：

```text
作业1(2411672_金暄琛).zip
```

提交压缩包命名：

```text
作业1(2411672_金暄琛).zip
```

压缩包包含：

```text
作业1(2411672_金暄琛).docx
作业1(2411672_金暄琛).pdf
页面标注助手(2411672_金暄琛).zip
extension/
screenshots/
web/    # 建议保留，方便复现
```

## 8. 真实网页对照实验

docs/ 下另有一组真实站点的抓包记录，作为自建页面的对照：

- docs/real-site-get.txt：学校官网首页的 GET 请求与响应
- docs/real-site-post.txt：httpbin.org 表单提交的 POST 请求与响应
- docs/real-site.har：同一组数据的 HAR 导出（可用浏览器 DevTools 的 Import HAR 打开）

采集方式为 Edge（Chromium 157）的 DevTools 协议录制，报文中的请求头、状态码、响应体均为实际数据。
