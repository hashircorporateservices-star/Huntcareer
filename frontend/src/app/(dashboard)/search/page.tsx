"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type Job = { id: number; title: string; country: string; company?: { name: string } };
export default function SearchPage() {
  const [q, setQ] = useState("");
  const [items, setItems] = useState<Job[]>([]);
  async function run() { const r = await api.get<{ data: Job[] }>(`/jobs${q ? `?q=${encodeURIComponent(q)}` : ""}`); setItems(r.data); }
  useEffect(() => { run(); }, []);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Search jobs</h1>
      <div className="flex gap-2">
        <input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === "Enter" && run()}
          placeholder="Title keyword…" className="flex-1 rounded-md border border-stone-200 px-3 py-2 text-sm" />
        <button onClick={run} className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white">Search</button>
      </div>
      <ul className="space-y-2">
        {items.map((j) => (
          <li key={j.id} className="rounded-xl border border-stone-200 bg-white p-4">
            <p className="text-sm font-medium text-brand-700">{j.title}</p>
            <p className="text-xs text-stone-500">{j.company?.name} · {j.country}</p>
          </li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">No jobs stored yet — Scouts fill this from all sources.</p>}
      </ul>
    </div>
  );
}
