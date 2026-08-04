"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type Data = { funnel: Record<string, number>; total_applications: number };
export default function AnalyticsPage() {
  const [d, setD] = useState<Data | null>(null);
  useEffect(() => { api.get<Data>("/analytics").then(setD).catch(() => {}); }, []);
  if (!d) return <p className="text-sm text-stone-500">Loading…</p>;
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Analytics</h1>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {Object.entries(d.funnel).map(([k, v]) => (
          <div key={k} className="rounded-xl border border-stone-200 bg-white p-5">
            <div className="figure text-2xl font-semibold">{v}</div>
            <div className="mt-1 text-sm capitalize text-stone-500">{k}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
