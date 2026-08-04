"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const NAV = [
  { href: "/dashboard", label: "Dashboard" },
  { href: "/search", label: "Search Jobs" },
  { href: "/saved", label: "Saved Jobs" },
  { href: "/applications", label: "Applications" },
  { href: "/review-queue", label: "Review Queue" },
  { href: "/scouts", label: "Scouts" },
  { href: "/resumes", label: "Resume" },
  { href: "/cover-letters", label: "Cover Letters" },
  { href: "/interview-prep", label: "Interview Prep" },
  { href: "/career-tools", label: "Career Tools" },
  { href: "/recruiters", label: "Recruiters" },
  { href: "/contacts", label: "Contacts" },
  { href: "/analytics", label: "Analytics" },
  { href: "/pricing", label: "Plans" },
  { href: "/settings", label: "Settings" },
];

export default function Sidebar() {
  const pathname = usePathname();

  return (
    <aside className="flex h-screen w-60 shrink-0 flex-col border-r border-stone-200 bg-white">
      <div className="px-5 py-5">
        <span className="text-[15px] font-semibold tracking-tight text-stone-900">
          AI Job Copilot
        </span>
      </div>

      <nav className="flex-1 space-y-0.5 px-3">
        {NAV.map(({ href, label }) => {
          const active = pathname === href || pathname.startsWith(href + "/");
          return (
            <Link
              key={href}
              href={href}
              className={[
                "block rounded-md px-3 py-2 text-sm transition-colors",
                active
                  ? "bg-stone-100 font-medium text-stone-900"
                  : "text-stone-600 hover:bg-stone-50 hover:text-stone-900",
              ].join(" ")}
            >
              {label}
            </Link>
          );
        })}
      </nav>

      <div className="border-t border-stone-200 px-5 py-4 text-xs text-stone-400">
        Personal use · single operator
      </div>
    </aside>
  );
}
