"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api, type QueueItem } from "@/lib/api";

/**
 * The "We found N matching jobs in the last 24h" hero (JobCopilot Image 1 layout),
 * built from the real Review Queue — pending items ARE the matches ready to apply.
 */
export default function MatchesToday() {
  const [items, setItems] = useState<QueueItem[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<{ data: QueueItem[]; total: number }>("/auto-apply/queue")
      .then((res) => {
        setItems(res.data.slice(0, 4));
        setTotal(res.total);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <p className="text-sm text-stone-500">Loading your matches…</p>;

  const remaining = Math.max(0, total - items.length);

  return (
    <section className="space-y-6">
      <div className="text-center">
        <span className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700">
          ✓ Scout active
        </span>
        <h2 className="mt-4 text-2xl font-semibold tracking-tight">
          We found <span className="figure text-brand-600">{total.toLocaleString()} matching jobs</span> in the last 24h
        </h2>
        <p className="mt-1 text-sm text-stone-500">
          Your Scout prepares tailored applications daily — you review and send.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        {items.map((item) => (
          <div key={item.id} className="rounded-xl border border-stone-200 bg-white p-5">
            <h3 className="text-[15px] font-semibold text-brand-600">{item.job.title}</h3>
            <p className="mt-1 text-sm text-stone-700">{item.job.company?.name ?? "Confidential"}</p>
            <div className="mt-3 flex items-center gap-4 text-sm text-stone-400">
              <span>{item.job.country}</span>
              <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">
                <span className="figure">{item.match_score}%</span> match
              </span>
            </div>
          </div>
        ))}
      </div>

      {remaining > 0 && (
        <p className="text-center text-sm">
          <span className="font-semibold">+{remaining.toLocaleString()} more matches</span>{" "}
          <span className="text-stone-500">ready to review</span>
        </p>
      )}

      <div className="text-center">
        <Link
          href="/review-queue"
          className="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-6 py-3 text-sm font-medium text-white hover:bg-brand-700"
        >
          Review &amp; apply →
        </Link>
      </div>
    </section>
  );
}
