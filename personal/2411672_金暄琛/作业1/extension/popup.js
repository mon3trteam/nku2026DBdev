async function getActiveTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  if (!tab?.id) throw new Error("没有找到当前标签页");
  return tab;
}

async function runInPage(func, args = []) {
  const tab = await getActiveTab();
  const results = await chrome.scripting.executeScript({
    target: { tabId: tab.id },
    func,
    args
  });
  return results[0]?.result;
}

function setStatus(text, kind = "normal") {
  const status = document.getElementById("status");
  status.textContent = text;
  status.style.color = kind === "error" ? "#b42318" : kind === "success" ? "#087443" : "#667085";
}

document.getElementById("highlightBtn").addEventListener("click", async () => {
  try {
    const result = await runInPage(() => {
      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
        return { ok: false, message: "请先在网页中选中一段文字" };
      }

      const range = selection.getRangeAt(0);
      const text = selection.toString().trim();
      const mark = document.createElement("mark");
      mark.dataset.pageMarker = "true";
      mark.style.backgroundColor = "#ffe58f";
      mark.style.color = "inherit";
      mark.style.padding = "0 2px";
      mark.style.borderRadius = "3px";

      try {
        mark.appendChild(range.extractContents());
        range.insertNode(mark);
        selection.removeAllRanges();
        return { ok: true, text, message: `已高亮 ${text.length} 个字符` };
      } catch {
        return { ok: false, message: "当前选区跨度过大，请缩小选区后再试" };
      }
    });

    setStatus(result.message, result.ok ? "success" : "error");
    if (result.ok) document.getElementById("selectionPreview").textContent = result.text;
  } catch (error) {
    setStatus(error.message, "error");
  }
});

document.getElementById("readBtn").addEventListener("click", async () => {
  try {
    const result = await runInPage(() => {
      const text = window.getSelection()?.toString().trim() || "";
      return { ok: Boolean(text), text, message: text ? "已读取选中文字" : "请先在网页中选中文字" };
    });

    setStatus(result.message, result.ok ? "success" : "error");
    document.getElementById("selectionPreview").textContent = result.text || "尚未读取";
  } catch (error) {
    setStatus(error.message, "error");
  }
});

document.getElementById("clearBtn").addEventListener("click", async () => {
  try {
    const result = await runInPage(() => {
      const marks = document.querySelectorAll('mark[data-page-marker="true"]');
      marks.forEach((mark) => {
        const parent = mark.parentNode;
        while (mark.firstChild) parent.insertBefore(mark.firstChild, mark);
        parent.removeChild(mark);
        parent.normalize();
      });
      return { count: marks.length };
    });

    setStatus(result.count ? `已清除 ${result.count} 处高亮` : "当前页面没有本插件的高亮", "success");
    if (result.count) document.getElementById("selectionPreview").textContent = "尚未读取";
  } catch (error) {
    setStatus(error.message, "error");
  }
});
