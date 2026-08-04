"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type Resume = { id: number; label: string; is_base: boolean };
export default function ResumesPage() {
  const [items, setItems] = useState<Resume[]>([]);
  useEffect(() => { api.get<Resume[]>("/resumes").then(setItems).catch(() => {}); }, []);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Resumes</h1>
      <ul className="space-y-2">
        {items.map((r) => (
          <li key={r.id} className="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
            <span className="text-sm font-medium">{r.label}</span>
            {r.is_base && <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700">Base</span>}
          </li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">No resumes yet. Upload one to get started.</p>}
      </ul>
    </div>
  );
}
