const api = {
  list: "/api/messages",
  create: "/api/messages"
};

let jqueryEventCount = 0;

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function formatTime(isoString) {
  return new Intl.DateTimeFormat("zh-CN", {
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit"
  }).format(new Date(isoString));
}

function renderMessages(messages) {
  const html = messages.map((message) => `
    <article class="message">
      <div class="avatar">${escapeHtml(message.author.slice(0, 1))}</div>
      <div>
        <div class="message__head">
          <strong>${escapeHtml(message.author)}</strong>
          <time datetime="${escapeHtml(message.createdAt)}">${formatTime(message.createdAt)}</time>
        </div>
        <p>${escapeHtml(message.content)}</p>
      </div>
      <span class="message__id">#${message.id}</span>
    </article>
  `).join("");

  $("#messageList").html(html);
}

function loadMessages() {
  $("#getStatus").text("GET 请求中...");

  return $.ajax({
    url: api.list,
    method: "GET",
    dataType: "json",
    cache: false
  }).done((response) => {
    renderMessages(response.data);
    $("#getStatus").text(`成功：收到 ${response.count} 条留言`);
  }).fail((xhr) => {
    $("#getStatus").text(`失败：HTTP ${xhr.status}`);
  });
}

function createMessage(payload) {
  $("#postStatus").text("POST 请求中...");

  return $.ajax({
    url: api.create,
    method: "POST",
    contentType: "application/json; charset=UTF-8",
    dataType: "json",
    data: JSON.stringify(payload)
  }).done((response) => {
    $("#postStatus").text(`成功：留言 #${response.data.id} 已保存`);
    $("#content").val("").trigger("focus");
    return loadMessages();
  }).fail((xhr) => {
    const error = xhr.responseJSON?.error || `HTTP ${xhr.status}`;
    $("#postStatus").text(`失败：${error}`);
  });
}

$(function () {
  loadMessages();

  $("#refreshMessages").on("click", loadMessages);

  $("#messageForm").on("submit", function (event) {
    event.preventDefault();
    createMessage({
      author: $("#author").val().trim(),
      content: $("#content").val().trim()
    });
  });

  $("#eventDemo").on("click", function () {
    jqueryEventCount += 1;
    $("#eventCard").addClass("active");
    $("#eventCard").data("eventCount", jqueryEventCount);
    $("#eventStatus").text(`jQuery 事件已触发 ${jqueryEventCount} 次`);
    $("#demoBadge").text("已触发");
  });
});

// 控制台也可调用，便于写报告或复现实验。
window.assignmentDemo = {
  loadMessages,
  createMessage,
  triggerJQueryEvent: () => $("#eventDemo").trigger("click")
};
