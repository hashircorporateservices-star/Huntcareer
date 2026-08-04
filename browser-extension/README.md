# HuntCareer Autofill (Chrome / Edge MV3)

Browser-assisted apply: fills employer application forms from your HuntCareer
profile. It never clicks submit — you review every field and send it yourself.
That's what keeps this on the right side of every job board's ToS.

## Load it (unpacked, for your own use)
1. Sign in to HuntCareer in the browser first (the extension uses that session).
2. Go to `chrome://extensions`, enable Developer mode, "Load unpacked", pick this folder.
3. Set your API base: the extension defaults to `https://copilot.yourdomain.com/api`.
   Change it in `background.js` (or via `chrome.storage`) to your domain.

## How it works
- Popup → "Fill this application" reads `/api/job-profile` + `/api/me` (with your
  session cookie), then injects `autofill.js` into the current tab.
- `autofill.js` matches your data to form fields by their labels/names and fills them,
  firing input/change events so React/Vue forms register the values.
- You check the fields and press the employer's own submit button.

## Requirement: CORS + cookies
Because the extension calls your API from a page origin, the API must allow the
request with credentials. In Laravel `config/cors.php`, allow your extension/site
origin and set `'supports_credentials' => true`. Sanctum's stateful domain must
cover the browser session.

## Add an icon
Drop a 128×128 `icon128.png` in this folder (referenced by the manifest).
