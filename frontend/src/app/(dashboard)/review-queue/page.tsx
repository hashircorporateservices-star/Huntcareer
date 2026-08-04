"use client";

import { useEffect, useState } from "react";
import { api, type QueueItem } from "@/lib/api";

export default function ReviewQueuePage() {
  const [items, setItems] = useState<QueueItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    setLoading(true);
    try {
      const res = await api.get<{ data: QueueItem[] }>("/auto-apply/queue");
      setItems(res.data);
      setError(null);
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function approveAndSubmit(item: QueueItem) {
    setBusy(item.id);
    try {
      await api.post(`/auto-apply/queue/${item.id}/approve`);
      const res = await api.post<{ result: string; open_url?: string; message?: string }>(
        `/auto-apply/queue/${item.id}/submit`
      );
      if (res.result === "capped") {
        setError(res.message ?? "Daily submit limit reached — approved and ready for tomorrow.");
        return; // keep the item visible; it's approved, not sent
      }
      // Browser-assisted items return the pre-filled apply URL to open in a new tab.
      if (res.open_url) window.open(res.open_url, "_blank", "noopener");
      setItems((prev) => prev.filter((i) => i.id !== item.id));
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(null);
    }
  }

  async function skip(item: QueueItem) {
    setBusy(item.id);
    try {
      await api.post(`/auto-apply/queue/${item.id}/skip`);
      setItems((prev) => prev.filter((i) => i.id !== item.id));
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(null);
    }
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-xl font-semibold tracking-tight">Review Queue</h1>
        <p className="mt-1 text-sm text-stone-500">
          Prepared applications waiting for your approval. Approving opens the pre-filled
          application, or submits via an official ATS when connected.
        </p>
      </header>

      {error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      )}

      {loading ? (
        <p className="text-sm text-stone-500">Loading…</p>
      ) : items.length === 0 ? (
        <div className="rounded-xl border border-dashed border-stone-300 bg-white p-10 text-center">
          <p className="text-sm font-medium text-stone-900">Nothing to review</p>
          <p className="mt-1 text-sm text-stone-500">
            When a rule runs, prepared applications will appear here.
          </p>
        </div>
      ) : (
        <ul className="space-y-3">
          {items.map((item) => (
            <li
              key={item.id}
              className="rounded-xl border border-stone-200 bg-white p-5"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">
                      <span className="figure">{item.match_score}%</span> match
                    </span>
                    <span className="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">
                      {item.submit_method === "ats_api" ? "Official ATS" : "Browser-assisted"}
                    </span>
                  </div>
                  <h3 className="mt-2 truncate text-[15px] font-medium text-stone-900">
                    {item.job.title}
                  </h3>
                  <p className="mt-0.5 text-sm text-stone-500">
                    {item.job.company?.name ?? "Unknown company"} · {item.job.country}
                    {item.job.city ? ` · ${item.job.city}` : ""}
                  </p>
                  <p className="mt-2 text-xs text-stone-400">
                    {item.resume?.label ?? "No resume"}
                    {item.cover_letter ? ` · ${item.cover_letter.label}` : " · no cover letter"}
                  </p>
                </div>

                <div className="flex shrink-0 gap-2">
                  <button
                    onClick={() => skip(item)}
                    disabled={busy === item.id}
                    className="rounded-md border border-stone-200 px-3 py-2 text-sm text-stone-600 hover:bg-stone-50 disabled:opacity-50"
                  >
                    Skip
                  </button>
                  <button
                    onClick={() => approveAndSubmit(item)}
                    disabled={busy === item.id}
                    className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                  >
                    {busy === item.id
                      ? "Working…"
                      : item.submit_method === "ats_api"
                      ? "Approve & submit"
                      : "Approve & open"}
                  </button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
