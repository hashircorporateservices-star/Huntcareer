"use client";

import { useState } from "react";
import { api } from "@/lib/api";

export default function CareerToolsPage() {
  return (
    <div className="mx-auto max-w-2xl space-y-10">
      <header>
        <h1 className="text-xl font-semibold tracking-tight">Career tools</h1>
      </header>
      <SalaryEstimator />
      <FollowUpGenerator />
    </div>
  );
}

function SalaryEstimator() {
  const [form, setForm] = useState({ title: "", country: "GB", years: "" });
  const [result, setResult] = useState<{ min: number; max: number; currency: string; note: string } | null>(null);
  const [busy, setBusy] = useState(false);

  async function run() {
    setBusy(true);
    try {
      setResult(
        await api.post("/career/salary-estimate", {
          title: form.title,
          country: form.country,
          years: form.years ? Number(form.years) : undefined,
        })
      );
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white p-6">
      <h2 className="text-sm font-medium">Salary estimator</h2>
      <div className="mt-4 grid grid-cols-3 gap-3">
        <input
          value={form.title}
          onChange={(e) => setForm({ ...form, title: e.target.value })}
          placeholder="Job title"
          className="col-span-2 rounded-md border border-stone-200 px-3 py-2 text-sm"
        />
        <input
          value={form.country}
          onChange={(e) => setForm({ ...form, country: e.target.value.toUpperCase() })}
          placeholder="GB"
          maxLength={2}
          className="rounded-md border border-stone-200 px-3 py-2 text-sm"
        />
      </div>
      <button
        onClick={run}
        disabled={!form.title || busy}
        className="mt-4 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >
        {busy ? "Estimating…" : "Estimate"}
      </button>

      {result && result.min != null && (
        <div className="mt-4 rounded-md bg-stone-50 p-4">
          <p className="figure text-xl font-semibold">
            {result.currency} {result.min.toLocaleString()} – {result.max.toLocaleString()}
          </p>
          <p className="mt-1 text-xs text-stone-500">{result.note}</p>
        </div>
      )}
    </section>
  );
}

function FollowUpGenerator() {
  const [type, setType] = useState("post_application");
  const [company, setCompany] = useState("");
  const [role, setRole] = useState("");
  const [body, setBody] = useState("");
  const [busy, setBusy] = useState(false);

  const TYPES = [
    ["post_application", "After applying"],
    ["post_interview", "After interview"],
    ["thank_you", "Thank-you"],
  ];

  async function run() {
    setBusy(true);
    try {
      const res = await api.post<{ body: string }>("/career/follow-up", {
        type,
        context: { company, role },
      });
      setBody(res.body);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white p-6">
      <h2 className="text-sm font-medium">Follow-up email generator</h2>
      <div className="mt-4 flex flex-wrap gap-2">
        {TYPES.map(([k, l]) => (
          <button
            key={k}
            onClick={() => setType(k)}
            className={[
              "rounded-full px-3 py-1.5 text-xs font-medium",
              type === k ? "bg-brand-600 text-white" : "bg-stone-100 text-stone-600",
            ].join(" ")}
          >
            {l}
          </button>
        ))}
      </div>
      <div className="mt-3 grid grid-cols-2 gap-3">
        <input value={company} onChange={(e) => setCompany(e.target.value)} placeholder="Company" className="rounded-md border border-stone-200 px-3 py-2 text-sm" />
        <input value={role} onChange={(e) => setRole(e.target.value)} placeholder="Role" className="rounded-md border border-stone-200 px-3 py-2 text-sm" />
      </div>
      <button
        onClick={run}
        disabled={busy}
        className="mt-4 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >
        {busy ? "Writing…" : "Generate"}
      </button>
      {body && (
        <textarea
          readOnly
          value={body}
          rows={8}
          className="mt-4 w-full rounded-md border border-stone-200 bg-stone-50 px-3 py-2 text-sm"
        />
      )}
    </section>
  );
}
