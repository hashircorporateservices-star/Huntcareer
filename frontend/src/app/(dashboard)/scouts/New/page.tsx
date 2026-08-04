"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

/* Client mirrors of backend config/copilot.php lookups */
const JOB_TYPES = [
  ["fulltime", "Full-time"],
  ["part_time", "Part-time"],
  ["contractor", "Contractor / Temp"],
  ["internship", "Internship"],
] as const;

const SENIORITY = [
  ["entry", "Entry Level"],
  ["associate", "Associate Level"],
  ["mid_senior", "Mid-to-Senior Level"],
  ["director", "Director Level and above"],
] as const;

const TIERS = [
  [50, "50% — cast wide"],
  [75, "75% — strong match"],
  [100, "100% — perfect only"],
] as const;

const AVAILABILITY = [
  ["immediately", "Immediately"],
  ["1_week", "In 1 week"],
  ["2_weeks", "In 2 weeks"],
  ["1_month", "In 1 month"],
  ["2_months", "In 2 months"],
] as const;

const STEPS = ["Jobs & Location", "Filters", "Your Profile", "How it works"];

const COUNTRIES: Record<string, string> = {
  GB: "United Kingdom", IE: "Ireland", MT: "Malta",
  AU: "Australia", NZ: "New Zealand", DE: "Germany", US: "United States",
};

export default function NewScoutWizard() {
  const router = useRouter();
  const [step, setStep] = useState(0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [scout, setScout] = useState({
    label: "",
    job_titles: [] as string[],
    remote: true,
    remote_locations: ["Worldwide"] as string[],
    onsite: false,
    onsite_locations: [] as string[],
    job_types: ["fulltime"] as string[],
    min_match_score: 75,
    require_visa_sponsorship: false,
    include_below_threshold: false,
    seniority_levels: ["mid_senior"] as string[],
    time_zones: [] as string[],
    run_at: "08:00",
    run_days: ["mon", "tue", "wed", "thu", "fri"],
    timezone: "Asia/Dubai",
    country_schedules: [] as { country: string; run_at: string }[],
    mode: "manual_review",
    writing_style: "",
  });

  const [profile, setProfile] = useState({
    mobile: "",
    based_country: "",
    based_city: "",
    current_title: "",
    availability: "immediately",
    requires_visa: false,
    nationalities: [] as string[],
    expected_salary: "",
    salary_currency: "AED",
    linkedin_url: "",
    experience_summary: "",
    generate_cover_letter: true,
  });

  const [titleInput, setTitleInput] = useState("");

  function toggle<T>(list: T[], v: T): T[] {
    return list.includes(v) ? list.filter((x) => x !== v) : [...list, v];
  }
  function addTitle() {
    const t = titleInput.trim();
    if (t && scout.job_titles.length < 5 && !scout.job_titles.includes(t)) {
      setScout({ ...scout, job_titles: [...scout.job_titles, t], label: scout.label || t });
    }
    setTitleInput("");
  }

  async function save() {
    setSaving(true);
    setError(null);
    try {
      await api.post("/job-profile", { ...profile, generate_cover_letter: undefined });
      await api.post("/auto-apply/rules", {
        ...scout,
        generate_cover_letter: profile.generate_cover_letter,
      });
      router.push("/scouts");
    } catch (e) {
      setError((e as Error).message);
      setSaving(false);
    }
  }

  const canNext =
    step === 0 ? scout.job_titles.length > 0 && scout.job_types.length > 0 : true;

  return (
    <div className="mx-auto max-w-2xl space-y-8">
      <header className="text-center">
        <h1 className="text-lg font-semibold tracking-tight">New Scout</h1>
        <p className="mt-1 text-sm text-stone-500">
          Step {step + 1} of {STEPS.length} · {STEPS[step]}
        </p>
        <div className="mx-auto mt-4 flex max-w-sm gap-1.5">
          {STEPS.map((_, i) => (
            <div
              key={i}
              className={`h-1.5 flex-1 rounded-full ${i <= step ? "bg-brand-600" : "bg-stone-200"}`}
            />
          ))}
        </div>
      </header>

      {error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      )}

      <div className="rounded-xl border border-stone-200 bg-white p-6">
        {/* STEP 1 — Jobs & Location */}
        {step === 0 && (
          <div className="space-y-6">
            <div>
              <label className="text-sm font-medium">Job titles (up to 5, any field)</label>
              <div className="mt-2 flex gap-2">
                <input
                  value={titleInput}
                  onChange={(e) => setTitleInput(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && (e.preventDefault(), addTitle())}
                  placeholder="e.g. Finance Manager, Product Manager…"
                  className="flex-1 rounded-md border border-stone-200 px-3 py-2 text-sm"
                />
                <button onClick={addTitle} className="rounded-md bg-stone-900 px-3 py-2 text-sm text-white">
                  Add
                </button>
              </div>
              <div className="mt-2 flex flex-wrap gap-2">
                {scout.job_titles.map((t) => (
                  <span key={t} className="rounded-full bg-brand-600 px-3 py-1 text-xs font-medium text-white">
                    {t}{" "}
                    <button onClick={() => setScout({ ...scout, job_titles: scout.job_titles.filter((x) => x !== t) })}>
                      ×
                    </button>
                  </span>
                ))}
              </div>
            </div>

            <div className="space-y-3">
              <label className="flex items-center gap-2 text-sm font-medium">
                <input
                  type="checkbox"
                  checked={scout.remote}
                  onChange={(e) => setScout({ ...scout, remote: e.target.checked })}
                />
                Remote jobs
              </label>
              <label className="flex items-center gap-2 text-sm font-medium">
                <input
                  type="checkbox"
                  checked={scout.onsite}
                  onChange={(e) => setScout({ ...scout, onsite: e.target.checked })}
                />
                On-site / Hybrid
              </label>
            </div>

            <div>
              <label className="text-sm font-medium">Job types</label>
              <div className="mt-2 flex flex-wrap gap-2">
                {JOB_TYPES.map(([k, l]) => (
                  <Chip key={k} on={scout.job_types.includes(k)} onClick={() => setScout({ ...scout, job_types: toggle(scout.job_types, k) })}>
                    {l}
                  </Chip>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* STEP 2 — Filters */}
        {step === 1 && (
          <div className="space-y-6">
            <div>
              <label className="text-sm font-medium">Match tier — only apply at or above</label>
              <p className="mt-1 text-xs text-stone-500">
                Full tailored applications are prepared only for jobs scoring at or above this.
                Nothing below 50% is ever auto-applied.
              </p>
              <div className="mt-3 flex flex-wrap gap-2">
                {TIERS.map(([k, l]) => (
                  <Chip key={k} on={scout.min_match_score === k} onClick={() => setScout({ ...scout, min_match_score: k })}>
                    {l}
                  </Chip>
                ))}
              </div>
            </div>

            <label className="flex items-start gap-2 text-sm">
              <input
                type="checkbox"
                checked={scout.include_below_threshold}
                onChange={(e) => setScout({ ...scout, include_below_threshold: e.target.checked })}
                className="mt-0.5"
              />
              <span>
                Also surface <strong>below-50% matches for optional review</strong> (not tailored,
                not auto-applied — just listed so you can look).
              </span>
            </label>

            <label className="flex items-start gap-2 text-sm">
              <input
                type="checkbox"
                checked={scout.require_visa_sponsorship}
                onChange={(e) => setScout({ ...scout, require_visa_sponsorship: e.target.checked })}
                className="mt-0.5"
              />
              <span>
                <strong>Only visa-sponsoring roles</strong> — match jobs that state sponsorship /
                work-permit support. (Best-effort: matches jobs that explicitly mention it.)
              </span>
            </label>

            <div>
              <label className="text-sm font-medium">Seniority</label>
              <div className="mt-2 flex flex-wrap gap-2">
                {SENIORITY.map(([k, l]) => (
                  <Chip key={k} on={scout.seniority_levels.includes(k)} onClick={() => setScout({ ...scout, seniority_levels: toggle(scout.seniority_levels, k) })}>
                    {l}
                  </Chip>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* STEP 3 — Profile / screening */}
        {step === 2 && (
          <div className="space-y-5">
            <p className="text-xs text-stone-500">
              Your Scout uses these to answer common application questions on your behalf.
            </p>

            <Field label="Cover letter">
              <div className="flex gap-2">
                <Chip on={profile.generate_cover_letter} onClick={() => setProfile({ ...profile, generate_cover_letter: true })}>
                  Auto-generate per job
                </Chip>
                <Chip on={!profile.generate_cover_letter} onClick={() => setProfile({ ...profile, generate_cover_letter: false })}>
                  Use my generic one
                </Chip>
              </div>
            </Field>

            <div className="grid grid-cols-2 gap-4">
              <Input label="Mobile" value={profile.mobile} onChange={(v) => setProfile({ ...profile, mobile: v })} />
              <Input label="Current job title" value={profile.current_title} onChange={(v) => setProfile({ ...profile, current_title: v })} />
              <Input label="Based — country" value={profile.based_country} onChange={(v) => setProfile({ ...profile, based_country: v })} />
              <Input label="Based — city" value={profile.based_city} onChange={(v) => setProfile({ ...profile, based_city: v })} />
              <Input label="Expected salary" value={profile.expected_salary} onChange={(v) => setProfile({ ...profile, expected_salary: v })} />
              <Input label="LinkedIn URL" value={profile.linkedin_url} onChange={(v) => setProfile({ ...profile, linkedin_url: v })} />
            </div>

            <Field label="Availability">
              <div className="flex flex-wrap gap-2">
                {AVAILABILITY.map(([k, l]) => (
                  <Chip key={k} on={profile.availability === k} onClick={() => setProfile({ ...profile, availability: k })}>
                    {l}
                  </Chip>
                ))}
              </div>
            </Field>

            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={profile.requires_visa}
                onChange={(e) => setProfile({ ...profile, requires_visa: e.target.checked })}
              />
              I will need visa sponsorship now or in future
            </label>

            <Field label="Experience summary (max 500 chars)">
              <textarea
                maxLength={500}
                rows={4}
                value={profile.experience_summary}
                onChange={(e) => setProfile({ ...profile, experience_summary: e.target.value })}
                className="w-full rounded-md border border-stone-200 px-3 py-2 text-sm"
              />
            </Field>
          </div>
        )}

        {/* STEP 4 — Behaviour */}
        {step === 3 && (
          <div className="space-y-6">
            <label className="text-sm font-medium">How your Scout works</label>

            <label className="flex cursor-pointer gap-3 rounded-lg border border-brand-200 bg-brand-50 p-4">
              <input
                type="radio"
                checked={scout.mode === "manual_review"}
                onChange={() => setScout({ ...scout, mode: "manual_review" })}
                className="mt-1"
              />
              <span className="text-sm">
                <strong>Auto-Save &amp; Review</strong> — fills applications but doesn't submit them. You
                review and send. <em className="text-brand-600">Recommended.</em>
              </span>
            </label>

            <label className="flex cursor-pointer gap-3 rounded-lg border border-stone-200 p-4">
              <input
                type="radio"
                checked={scout.mode === "auto_ats"}
                onChange={() => setScout({ ...scout, mode: "auto_ats" })}
                className="mt-1"
              />
              <span className="text-sm">
                <strong>Auto-submit — official ATS only</strong> — automatically submits to jobs on
                integrated ATS platforms (Greenhouse, Lever). Job-board applications still go through
                review, to keep your account and email reputation safe.
              </span>
            </label>

            <div>
              <label className="text-sm font-medium">Apply schedule</label>
              <p className="mt-1 text-xs text-stone-500">
                Default run time (your timezone), used for any country without its own time below.
              </p>
              <input
                type="time"
                value={scout.run_at}
                onChange={(e) => setScout({ ...scout, run_at: e.target.value })}
                className="mt-2 rounded-md border border-stone-200 px-3 py-2 text-sm"
              />

              <p className="mt-4 text-xs font-medium text-stone-600">Per-country times (each in its local timezone)</p>
              <div className="mt-2 space-y-2">
                {scout.country_schedules.map((cs, i) => (
                  <div key={i} className="flex items-center gap-2">
                    <select
                      value={cs.country}
                      onChange={(e) => {
                        const next = [...scout.country_schedules];
                        next[i] = { ...next[i], country: e.target.value };
                        setScout({ ...scout, country_schedules: next });
                      }}
                      className="rounded-md border border-stone-200 px-2 py-2 text-sm"
                    >
                      {Object.entries(COUNTRIES).map(([code, name]) => (
                        <option key={code} value={code}>{name}</option>
                      ))}
                    </select>
                    <input
                      type="time"
                      value={cs.run_at}
                      onChange={(e) => {
                        const next = [...scout.country_schedules];
                        next[i] = { ...next[i], run_at: e.target.value };
                        setScout({ ...scout, country_schedules: next });
                      }}
                      className="rounded-md border border-stone-200 px-3 py-2 text-sm"
                    />
                    <button
                      onClick={() =>
                        setScout({ ...scout, country_schedules: scout.country_schedules.filter((_, j) => j !== i) })
                      }
                      className="text-stone-400 hover:text-red-500"
                    >
                      ×
                    </button>
                  </div>
                ))}
                <button
                  onClick={() =>
                    setScout({ ...scout, country_schedules: [...scout.country_schedules, { country: "GB", run_at: "09:00" }] })
                  }
                  className="rounded-md border border-stone-200 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  + Add country time
                </button>
              </div>
            </div>

            <Field label="Writing style (optional)">
              <textarea
                rows={3}
                placeholder="e.g. concise, professional, first-person…"
                value={scout.writing_style}
                onChange={(e) => setScout({ ...scout, writing_style: e.target.value })}
                className="w-full rounded-md border border-stone-200 px-3 py-2 text-sm"
              />
            </Field>
          </div>
        )}
      </div>

      {/* Nav */}
      <div className="flex items-center justify-between">
        <button
          onClick={() => setStep((s) => Math.max(0, s - 1))}
          disabled={step === 0}
          className="rounded-md border border-stone-200 px-4 py-2 text-sm text-stone-600 disabled:opacity-40"
        >
          Back
        </button>
        {step < STEPS.length - 1 ? (
          <button
            onClick={() => setStep((s) => s + 1)}
            disabled={!canNext}
            className="rounded-md bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-40"
          >
            Next
          </button>
        ) : (
          <button
            onClick={save}
            disabled={saving}
            className="rounded-md bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-40"
          >
            {saving ? "Saving…" : "Save & activate Scout"}
          </button>
        )}
      </div>
    </div>
  );
}

/* ---- tiny presentational helpers ---- */
function Chip({ on, onClick, children }: { on: boolean; onClick: () => void; children: React.ReactNode }) {
  return (
    <button
      onClick={onClick}
      className={[
        "rounded-full px-3 py-1.5 text-xs font-medium",
        on ? "bg-brand-600 text-white" : "bg-stone-100 text-stone-600 hover:bg-stone-200",
      ].join(" ")}
    >
      {children}
    </button>
  );
}
function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="text-sm font-medium">{label}</label>
      <div className="mt-2">{children}</div>
    </div>
  );
}
function Input({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
  return (
    <label className="block">
      <span className="text-xs font-medium text-stone-600">{label}</span>
      <input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="mt-1 w-full rounded-md border border-stone-200 px-3 py-2 text-sm"
      />
    </label>
  );
}
