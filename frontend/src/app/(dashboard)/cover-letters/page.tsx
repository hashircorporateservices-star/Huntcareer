"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type CL = { id: number; label: string | null };
export default function CoverLettersPage() {
  const [items, setItems] = useState<CL[]>([]);
  useEffect(() => { api.get<CL[]>("/cover-letters").then(setItems).catch(() => {}); }, []);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Cover letters</h1>
      <ul className="space-y-2">
        {items.map((c) => (
          <li key={c.id} className="rounded-xl border border-stone-200 bg-white p-4 text-sm font-medium">
            {c.label ?? `Cover letter #${c.id}`}
          </li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">No cover letters yet — Scouts generate these per job.</p>}
      </ul>
    </div>
  );
}
