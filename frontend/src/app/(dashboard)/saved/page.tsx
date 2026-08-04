"use client";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
type App = { id: number; job: { title: string; country: string } };
export default function SavedPage() {
  const [items, setItems] = useState<App[]>([]);
  useEffect(() => { api.get<{ data: App[] }>("/applications?status=saved").then((r) => setItems(r.data)).catch(() => {}); }, []);
  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Saved jobs</h1>
      <ul className="space-y-2">
        {items.map((a) => (
          <li key={a.id} className="rounded-xl border border-stone-200 bg-white p-4 text-sm font-medium">{a.job?.title ?? "—"}</li>
        ))}
        {items.length === 0 && <p className="text-sm text-stone-500">Nothing saved yet.</p>}
      </ul>
    </div>
  );
}
