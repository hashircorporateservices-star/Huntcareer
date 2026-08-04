"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type Rec = { id: number; name: string; relationship: string; company?: { name: string } };
export default function RecruitersPage() {
  const [items, setItems] = useState<Rec[]>([]);
  useEffect(() => { api.get<Rec[]>("/recruiters").then(setItems).catch(() => {}); }, []);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Recruiters</h1>
      <ul className="space-y-2">
        {items.map((r) => (
          <li key={r.id} className="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
            <span className="text-sm font-medium">{r.name}<span className="ml-2 text-xs text-stone-400">{r.company?.name}</span></span>
            <span className="rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600">{r.relationship}</span>
          </li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">No recruiters yet.</p>}
      </ul>
    </div>
  );
}
