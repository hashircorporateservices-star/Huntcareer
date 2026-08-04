"use client";

// OAuth is a browser redirect to the backend (web routes, not /api).
const API = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";
const ORIGIN = API.replace(/\/api\/?$/, "");

const PROVIDERS = [
  { key: "google", label: "Continue with Google" },
  { key: "microsoft", label: "Continue with Microsoft" },
  { key: "facebook", label: "Continue with Facebook" },
];

export default function LoginPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-[var(--paper)] px-4">
      <div className="w-full max-w-sm space-y-8 text-center">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">HuntCareer</h1>
          <p className="mt-2 text-sm text-stone-500">
            Your Scouts find, tailor, and prepare applications. You review and send.
          </p>
        </div>

        <div className="space-y-3">
          {PROVIDERS.map((p) => (
            <a
              key={p.key}
              href={`${ORIGIN}/auth/${p.key}/redirect`}
              className="block w-full rounded-lg border border-stone-200 bg-white py-2.5 text-sm font-medium text-stone-800 hover:border-brand-300 hover:bg-brand-50"
            >
              {p.label}
            </a>
          ))}
        </div>

        <p className="text-xs text-stone-400">
          Signing in with Google also lets your Scout read job-alert emails (read-only).
        </p>
      </div>
    </div>
  );
}
