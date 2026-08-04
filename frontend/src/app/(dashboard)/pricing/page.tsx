"use client";

import { useState } from "react";
import { api } from "@/lib/api";

// Mirrors backend config/plans.php. Keep in sync (or fetch from /api/plans).
const CYCLES = [
  { key: "weekly", label: "Weekly" },
  { key: "monthly", label: "Monthly (save 43%)" },
  { key: "quarterly", label: "Quarterly (save 55%)" },
] as const;

type Cycle = (typeof CYCLES)[number]["key"];

const PLANS = [
  {
    key: "premium",
    name: "Premium",
    price: { weekly: 39, monthly: 99, quarterly: 267 } as Record<Cycle, number>,
    highlight: false,
    features: [
      "1 Scout",
      "Up to 20 job matches daily",
      "Automate applications",
      "Save applications for review",
      "Hiring manager contacts",
      "Job application tracker",
      "12 credits / month",
      "Chrome extension",
      "AI resume builder",
      "AI cover letter builder",
      "AI interview roleplay",
      "AI career tools",
    ],
  },
  {
    key: "elite",
    name: "Elite",
    price: { weekly: 49, monthly: 129, quarterly: 349 } as Record<Cycle, number>,
    highlight: true,
    features: [
      "3 Scouts",
      "Up to 50 job matches daily",
      "Tailor your resume for every application",
      "Automate applications",
      "Save applications for review",
      "Hiring manager contacts",
      "Job application tracker",
      "20 credits / month",
      "Chrome extension",
      "AI resume builder",
      "AI cover letter builder",
      "AI interview roleplay",
      "AI career tools",
    ],
  },
];

export default function PricingPage() {
  const [cycle, setCycle] = useState<Cycle>("monthly");
  const [busy, setBusy] = useState<string | null>(null);

  async function activate(plan: string) {
    setBusy(plan);
    try {
      const { url } = await api.post<{ url: string }>("/billing/checkout", { plan, cycle });
      window.location.href = url; // hosted Lemon Squeezy checkout
    } catch {
      setBusy(null);
    }
  }

  return (
    <div className="space-y-10">
      <header className="text-center">
        <h1 className="text-2xl font-semibold tracking-tight">Choose your plan</h1>
        <p className="mt-2 text-sm text-stone-500">
          Cancel anytime. Prices in AED.
        </p>
      </header>

      {/* Billing toggle */}
      <div className="mx-auto flex w-fit items-center gap-1 rounded-full border border-stone-200 bg-white p-1">
        {CYCLES.map((c) => (
          <button
            key={c.key}
            onClick={() => setCycle(c.key)}
            className={[
              "rounded-full px-4 py-1.5 text-sm font-medium transition-colors",
              cycle === c.key ? "bg-brand-600 text-white" : "text-stone-600 hover:text-stone-900",
            ].join(" ")}
          >
            {c.label}
          </button>
        ))}
      </div>

      {/* Plan cards */}
      <div className="mx-auto grid max-w-3xl gap-6 sm:grid-cols-2">
        {PLANS.map((plan) => (
          <div
            key={plan.key}
            className={[
              "flex flex-col overflow-hidden rounded-2xl border bg-white",
              plan.highlight ? "border-brand-300 shadow-lg shadow-brand-100" : "border-stone-200",
            ].join(" ")}
          >
            <div
              className={[
                "px-6 py-4 text-center text-sm font-semibold uppercase tracking-wide text-white",
                plan.highlight ? "bg-brand-700" : "bg-brand-500",
              ].join(" ")}
            >
              {plan.name}
            </div>

            <div className="px-6 py-6 text-center">
              <div className="flex items-start justify-center gap-1">
                <span className="mt-1 text-sm text-stone-500">AED</span>
                <span className="figure text-5xl font-semibold">{plan.price[cycle]}</span>
              </div>
              <div className="mt-1 text-sm text-stone-400">{cycle}</div>
            </div>

            <ul className="flex-1 space-y-2.5 px-6 pb-6">
              {plan.features.map((f) => (
                <li key={f} className="flex items-start gap-2 text-sm text-stone-700">
                  <span className="mt-0.5 text-brand-500">✓</span>
                  <span>{f}</span>
                </li>
              ))}
            </ul>

            <div className="px-6 pb-6">
              <button
                onClick={() => activate(plan.key)}
                disabled={busy === plan.key}
                className="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
              >
                {busy === plan.key ? "Redirecting…" : `Activate ${plan.name}`}
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
