const $ = (id) => document.getElementById(id);

async function getApiBase() {
  return new Promise((r) =>
    chrome.storage.sync.get(["apiBase"], (v) => r(v.apiBase || "https://copilot.yourdomain.com/api"))
  );
}

async function apiGet(path) {
  const base = await getApiBase();
  const res = await fetch(base + path, { credentials: "include", headers: { Accept: "application/json" } });
  if (!res.ok) throw new Error(String(res.status));
  return res.json();
}

// Build a flat profile object the content script can consume.
function flatten(p) {
  const name = (p.full_name || "").trim().split(" ");
  return {
    full_name: p.full_name || "",
    first_name: name[0] || "",
    last_name: name.slice(1).join(" ") || "",
    email: p.email || "",
    mobile: p.mobile || "",
    linkedin_url: p.linkedin_url || "",
    current_title: p.current_title || "",
    based_city: p.based_city || "",
    based_country: p.based_country || "",
    expected_salary: p.expected_salary || "",
    experience_summary: p.experience_summary || "",
  };
}

$("fill").addEventListener("click", async () => {
  $("status").textContent = "Loading your profile…";
  try {
    const [profile, me] = await Promise.all([apiGet("/job-profile"), apiGet("/me")]);
    const flat = flatten({ ...profile, full_name: me.name, email: me.email });
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    await chrome.scripting.executeScript({ target: { tabId: tab.id }, files: ["autofill.js"] });
    const [{ result }] = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      func: (p) => huntcareerFill(p),
      args: [flat],
    });
    $("status").textContent = `Filled ${result} field(s). Review them, then submit yourself.`;
  } catch (e) {
    $("status").textContent = "Not signed in. Open HuntCareer and sign in first.";
  }
});

$("open-review").addEventListener("click", async () => {
  const base = await getApiBase();
  chrome.tabs.create({ url: base.replace(/\/api\/?$/, "") + "/review-queue" });
});

// Show the top approved queue items for quick context.
(async () => {
  try {
    const q = await apiGet("/auto-apply/queue");
    const items = (q.data || []).slice(0, 3);
    $("queue").innerHTML = items
      .map(
        (i) =>
          `<div class="item"><b>${i.job.title}</b><span class="muted">${i.match_score}% match · ${i.job.country}</span></div>`
      )
      .join("");
  } catch {
    /* not signed in — silent */
  }
})();
