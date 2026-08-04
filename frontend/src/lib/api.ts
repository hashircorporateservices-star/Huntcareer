/**
 * Thin fetch wrapper for the Laravel API. Reads the base URL from env and
 * forwards the Sanctum session cookie. Every helper throws on non-2xx so pages
 * can surface real errors instead of silently rendering empty states.
 */
const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    credentials: "include",
    headers: { "Content-Type": "application/json", Accept: "application/json", ...init.headers },
    ...init,
  });

  if (!res.ok) {
    const detail = await res.text().catch(() => "");
    throw new Error(`${res.status} ${res.statusText} — ${detail}`);
  }
  return res.status === 204 ? (undefined as T) : res.json();
}

export const api = {
  get: <T>(p: string) => request<T>(p),
  post: <T>(p: string, body?: unknown) =>
    request<T>(p, { method: "POST", body: body ? JSON.stringify(body) : undefined }),
  patch: <T>(p: string, body?: unknown) =>
    request<T>(p, { method: "PATCH", body: body ? JSON.stringify(body) : undefined }),
  del: (p: string) => request<void>(p, { method: "DELETE" }),
};

// ---- Auth ----
export type Me = { id: number; name: string; email: string; is_admin: boolean };

export const auth = {
  // Sanctum SPA: prime the CSRF cookie before any state-changing request.
  csrf: () =>
    fetch(`${BASE.replace(/\/api\/?$/, "")}/sanctum/csrf-cookie`, { credentials: "include" }),
  me: () => api.get<Me>("/me"),
  logout: async () => {
    await auth.csrf();
    return api.post("/logout");
  },
};

// ---- Domain types (mirror the API) ----
export type QueueItem = {
  id: number;
  match_score: number;
  status: string;
  submit_method: "browser_assisted" | "ats_api";
  prepared_summary: string;
  job: { id: number; title: string; country: string; city?: string; apply_url: string; company?: { name: string } };
  resume?: { id: number; label: string };
  cover_letter?: { id: number; label: string };
};

export type AutoApplyRule = {
  id: number;
  label: string;
  active: boolean;
  job_titles: string[];
  remote: boolean;
  onsite: boolean;
  match_threshold: string;
  min_match_score: number;
  run_at: string;
  run_days: string[];
  timezone: string;
  max_per_run: number;
  mode: "manual_review" | "auto_ats";
};
