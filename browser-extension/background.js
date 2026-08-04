// Minimal service worker. All work happens in the popup + injected content script.
// Kept so the extension has a valid MV3 background and room to grow (e.g. alarms).
chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.sync.get(["apiBase"], (v) => {
    if (!v.apiBase) chrome.storage.sync.set({ apiBase: "https://copilot.yourdomain.com/api" });
  });
});
