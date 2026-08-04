"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

type RefData = { code: string; link: string; total: number; rewarded: number };

export default function ReferralsPage() {
  const [data, setData] = useState<RefData | null>(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    api.get<RefData>("/referrals").then(setData).catch(() => {});
  }, []);

  function copy() {
    if (!data) return;
    navigator.clipboard.writeText(data.link);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  }

  return (
    <div className="mx-auto max-w-2xl space-y-8">
      <header>
        <h1 className="text-xl font-semibold tracking-tight">Refer friends, earn free months</h1>
        <p className="mt-1 text-sm text-stone-500">
          Share your link. When someone signs up with it, you get <strong>1 free month</strong>.
        </p>
      </header>

      {!data ? (
        <p className="text-sm text-stone-500">Loading…</p>
      ) : (
        <>
          <div className="rounded-xl border border-stone-200 bg-white p-5">
            <p className="text-xs font-medium text-stone-600">Your referral link</p>
            <div className="mt-2 flex gap-2">
              <input readOnly value={data.link} className="flex-1 rounded-md border border-stone-200 bg-stone-50 px-3 py-2 text-sm" />
              <button onClick={copy} className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                {copied ? "Copied!" : "Copy"}
              </button>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="rounded-xl border border-stone-200 bg-white p-5">
              <div className="figure text-2xl font-semibold">{data.total}</div>
              <div className="mt-1 text-sm text-stone-500">Signups referred</div>
            </div>
            <div className="rounded-xl border border-stone-200 bg-white p-5">
              <div className="figure text-2xl font-semibold">{data.rewarded}</div>
              <div className="mt-1 text-sm text-stone-500">Free months earned</div>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
