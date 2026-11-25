chrome.action.onClicked.addListener((tab) => {
  chrome.scripting.executeScript({
    target: { tabId: tab.id },
    function: changePageBackground
  });
});

function changePageBackground() {
  document.body.style.backgroundColor = "#C7EDCC"; 
  alert("护眼模式已开启！");
}