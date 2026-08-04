"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api } from "@/lib/api";

type Job = {
  id: number;
  title: string;
  company: string | null;
  country: string;
  city: string | null;
  work_mode: string | null;
  visa: boolean | null;
  apply_url: string;
};

const COUNTRIES = ["", "GB", "IE", "MT", "AU", "NZ", "DE", "US"];

export default function PublicJobsPage() {
  const [jobs, setJobs] = useState<Job[]>([]);
  const [q, setQ] = useState("");
  const [country, setCountry] = useState("");
  const [visa, setVisa] = useState(false);
  const [loading, setLoading] = useState(true);

  async function load() {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (q) params.set("q", q);
      if (country) params.set("country", country);
      if (visa) params.set("visa", "1");
      const res = await api.get<{ data: Job[] }>(`/public/jobs?${params}`);
      setJobs(res.data);
    } catch {
      setJobs([]);
    } finally {
      setLoading(false);
    }
  }
  useEffect(() => {
    load();
  }, []);

  return (
    <div className="mx-auto max-w-5xl px-6 py-10">
      <header className="flex items-center justify-between">
        <Link href="/" className="text-lg font-semibold tracking-tight">FoxLoopr</Link>
        <Link href="/login" className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
          Get started free
        </Link>
      </header>

      <div className="mt-10">
        <h1 className="text-2xl font-semibold tracking-tight">Job board</h1>
        <p className="mt-1 text-sm text-stone-500">
          Curated roles across GB · IE · MT · AU · NZ · DE · US. Sign up to auto-apply with a tailored CV.
        </p>
      </div>

      <div className="mt-6 flex flex-wrap gap-2">
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          onKeyDown={(e) => e.key === "Enter" && load()}
          placeholder="Title keyword…"
          className="flex-1 rounded-md border border-stone-200 px-3 py-2 text-sm"
        />
        <select value={country} onChange={(e) => setCountry(e.target.value)} className="rounded-md border border-stone-200 px-3 py-2 text-sm">
          {COUNTRIES.map((c) => <option key={c} value={c}>{c || "All countries"}</option>)}
        </select>
        <label className="flex items-center gap-2 rounded-md border border-stone-200 px-3 py-2 text-sm">
          <input type="checkbox" checked={visa} onChange={(e) => setVisa(e.target.checked)} />
          Visa sponsor
        </label>
        <button onClick={load} className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white">Search</button>
      </div>

      <div className="mt-6 space-y-2">
        {loading ? (
          <p className="text-sm text-stone-500">Loading…</p>
        ) : jobs.length === 0 ? (
          <div className="rounded-xl border border-dashed border-stone-300 p-10 text-center text-sm text-stone-500">
            No jobs yet — the board fills as our Scouts pull listings. Check back soon.
          </div>
        ) : (
          jobs.map((j) => (
            <div key={j.id} className="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
              <div className="min-w-0">
                <p className="truncate text-sm font-medium text-brand-700">{j.title}</p>
                <p className="text-xs text-stone-500">
                  {j.company ?? "—"} · {j.country}{j.city ? ` · ${j.city}` : ""}
                  {j.visa ? " · visa sponsor" : ""}
                </p>
              </div>
              <a href={j.apply_url} target="_blank" rel="noopener noreferrer"
                 className="shrink-0 rounded-md border border-stone-200 px-3 py-1.5 text-sm text-stone-700 hover:bg-stone-50">
                View
              </a>
            </div>
          ))
        )}
      </div>

      <div className="mt-10 rounded-2xl bg-brand-600 p-8 text-center text-white">
        <p className="text-lg font-semibold">Let a Scout apply for you.</p>
        <p className="mt-1 text-sm text-brand-50">Tailored CV + cover letter per job. You review, one click to send.</p>
        <Link href="/login" className="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-brand-700">
          Start free
        </Link>
      </div>
    </div>
  );
}
