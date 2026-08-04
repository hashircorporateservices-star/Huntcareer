// Injected into the employer's application page. Receives your profile and fills
// matching fields. It NEVER clicks submit — that stays your decision.
function huntcareerFill(profile) {
  const setValue = (el, val) => {
    if (!el || val == null || val === "") return false;
    const proto = el instanceof HTMLTextAreaElement
      ? window.HTMLTextAreaElement.prototype
      : window.HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(proto, "value").set;
    setter.call(el, val);
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
    return true;
  };

  // Map profile fields to the words that commonly label them.
  const map = [
    [["first name", "firstname", "given name"], profile.first_name],
    [["last name", "lastname", "surname", "family name"], profile.last_name],
    [["full name", "your name", "name"], profile.full_name],
    [["email", "e-mail"], profile.email],
    [["phone", "mobile", "telephone"], profile.mobile],
    [["linkedin"], profile.linkedin_url],
    [["current title", "job title", "current role"], profile.current_title],
    [["city", "town"], profile.based_city],
    [["country"], profile.based_country],
    [["salary expectation", "expected salary", "desired salary"], profile.expected_salary],
    [["summary", "about you", "cover", "why", "message", "experience"], profile.experience_summary],
  ];

  const labelText = (el) => {
    let t = (el.name || "") + " " + (el.id || "") + " " + (el.placeholder || "") + " " + (el.getAttribute("aria-label") || "");
    if (el.labels) for (const l of el.labels) t += " " + l.textContent;
    return t.toLowerCase();
  };

  let filled = 0;
  const fields = document.querySelectorAll(
    "input[type=text], input[type=email], input[type=tel], input:not([type]), textarea"
  );
  fields.forEach((el) => {
    const ctx = labelText(el);
    for (const [keys, val] of map) {
      if (val && keys.some((k) => ctx.includes(k))) {
        if (setValue(el, val)) filled++;
        break;
      }
    }
  });

  return filled;
}
