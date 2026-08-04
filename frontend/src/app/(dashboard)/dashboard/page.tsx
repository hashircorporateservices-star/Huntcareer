import Link from "next/link";

const STATS = [
  { label: "Awaiting review", value: "—", href: "/review-queue", accent: true },
  { label: "Applied", value: "—", href: "/applications" },
  { label: "Interviews", value: "—", href: "/applications?status=interview" },
  { label: "Offers", value: "—", href: "/applications?status=offer" },
];

export default function DashboardPage() {
  return (
    <div className="space-y-8">
      <header className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold tracking-tight">Dashboard</h1>
          <p className="mt-1 text-sm text-stone-500">
            Your job search across GB · IE · MT · AU · NZ · DE · US
          </p>
        </div>
        <Link
          href="/auto-apply"
          className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Configure auto-apply
        </Link>
      </header>

      <section className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {STATS.map((s) => (
          <Link
            key={s.label}
            href={s.href}
            className={[
              "rounded-xl border p-5 transition-colors",
              s.accent
                ? "border-brand-200 bg-brand-50 hover:bg-brand-100"
                : "border-stone-200 bg-white hover:bg-stone-50",
            ].join(" ")}
          >
            <div className="text-2xl font-semibold tabular-nums">{s.value}</div>
            <div className="mt-1 text-sm text-stone-500">{s.label}</div>
          </Link>
        ))}
      </section>

      <section className="rounded-xl border border-stone-200 bg-white p-6">
        <h2 className="text-sm font-medium text-stone-900">How auto-apply works here</h2>
        <p className="mt-2 max-w-2xl text-sm leading-relaxed text-stone-600">
          At the time you set, the copilot searches your target countries, scores each job
          against your resume, and prepares a tailored resume plus a cover letter. Prepared
          applications land in the <strong>Review Queue</strong>. Nothing is sent to an
          employer until you approve it — approving either opens the pre-filled application in
          your browser, or submits through an official ATS where one is connected.
        </p>
      </section>
    </div>
  );
}
