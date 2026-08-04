"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type App = { id: number; status: string; job: { title: string; country: string } };
const STAGES = ["saved","applied","assessment","interview","offer","rejected","accepted"];
export default function ApplicationsPage() {
  const [items, setItems] = useState<App[]>([]);
  const [filter, setFilter] = useState("");
  useEffect(() => {
    api.get<{ data: App[] }>("/applications" + (filter ? `?status=${filter}` : ""))
      .then((r) => setItems(r.data)).catch(() => {});
  }, [filter]);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Applications</h1>
      <div className="flex flex-wrap gap-2">
        {["", ...STAGES].map((s) => (
          <button key={s} onClick={() => setFilter(s)}
            className={`rounded-full px-3 py-1 text-xs font-medium ${filter === s ? "bg-brand-600 text-white" : "bg-stone-100 text-stone-600"}`}>
            {s || "All"}
          </button>
        ))}
      </div>
      <ul className="space-y-2">
        {items.map((a) => (
          <li key={a.id} className="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
            <span className="text-sm font-medium">{a.job?.title ?? "—"}</span>
            <span className="figure rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">{a.status}</span>
          </li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">No applications yet.</p>}
      </ul>
    </div>
  );
}
