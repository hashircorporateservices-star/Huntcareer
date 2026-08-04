"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api, type AutoApplyRule } from "@/lib/api";

const GUIDES = [
  { label: "How Scouts work", href: "/guides/how-scouts-work" },
  { label: "How to train your Scout", href: "/guides/train" },
  { label: "How to apply to external jobs", href: "/guides/external" },
  { label: "FAQ", href: "/guides/faq" },
];

export default function ScoutsPage() {
  const [scouts, setScouts] = useState<AutoApplyRule[]>([]);
  const [loading, setLoading] = useState(true);

  async function load() {
    try {
      setScouts(await api.get<AutoApplyRule[]>("/auto-apply/rules"));
    } finally {
      setLoading(false);
    }
  }
  useEffect(() => {
    load();
  }, []);

  async function toggle(scout: AutoApplyRule) {
    await api.patch(`/auto-apply/rules/${scout.id}`, { active: !scout.active });
    load();
  }

  async function remove(id: number) {
    if (!confirm("Delete this Scout?")) return;
    await api.del(`/auto-apply/rules/${id}`);
    load();
  }

  return (
    <div className="space-y-10">
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold tracking-tight">Scouts</h1>
      </header>

      {loading ? (
        <p className="text-sm text-stone-500">Loading…</p>
      ) : (
        <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {scouts.map((s) => (
            <div key={s.id} className="overflow-hidden rounded-xl border border-stone-200 bg-white">
              <div className="truncate bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-800">
                {s.job_titles?.join(", ") ?? s.label}
              </div>
              <div className="space-y-1.5 px-4 py-4 text-sm text-stone-600">
                <p>🔍 {s.job_titles?.length ?? 0} title(s)</p>
                <p>📍 {s.remote ? "Remote" : ""}{s.onsite ? " · On-site" : ""}</p>
                <p>🎚 Match: {s.match_threshold ?? "higher"}</p>
              </div>
              <div className="flex items-center justify-between border-t border-stone-100 px-4 py-3">
                <button
                  onClick={() => toggle(s)}
                  className={[
                    "rounded-full px-3 py-1 text-xs font-semibold",
                    s.active ? "bg-brand-100 text-brand-700" : "bg-stone-100 text-stone-500",
                  ].join(" ")}
                >
                  {s.active ? "ON" : "OFF"}
                </button>
                <div className="flex items-center gap-3 text-sm">
                  <Link href={`/scouts/${s.id}/edit`} className="text-brand-600 hover:underline">
                    Edit
                  </Link>
                  <button onClick={() => remove(s.id)} className="text-stone-400 hover:text-red-500">
                    🗑
                  </button>
                </div>
              </div>
            </div>
          ))}

          {/* New Scout */}
          <Link
            href="/scouts/new"
            className="flex min-h-[160px] items-center justify-center rounded-xl border border-dashed border-stone-300 bg-white text-sm font-medium text-brand-600 hover:border-brand-300 hover:bg-brand-50"
          >
            + New Scout
          </Link>
        </section>
      )}

      <section>
        <h2 className="mb-3 text-sm font-medium text-stone-900">Guides</h2>
        <div className="grid grid-cols-2 gap-3 rounded-xl border border-stone-200 bg-white p-5 sm:grid-cols-4">
          {GUIDES.map((g) => (
            <Link key={g.href} href={g.href} className="text-sm text-stone-600 hover:text-brand-600">
              {g.label}
            </Link>
          ))}
        </div>
      </section>
    </div>
  );
}
