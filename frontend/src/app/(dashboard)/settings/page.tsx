"use client";
import { useEffect, useState } from "react";
import { auth, type Me } from "@/lib/api";
export default function SettingsPage() {
  const [me, setMe] = useState<Me | null>(null);
  useEffect(() => { auth.me().then(setMe).catch(() => {}); }, []);
  return (
    <div className="mx-auto max-w-lg space-y-6">
      <h1 className="text-xl font-semibold tracking-tight">Settings</h1>
      <div className="rounded-xl border border-stone-200 bg-white p-6 text-sm">
        <p><span className="text-stone-500">Name:</span> {me?.name ?? "—"}</p>
        <p className="mt-2"><span className="text-stone-500">Email:</span> {me?.email ?? "—"}</p>
        <p className="mt-2"><span className="text-stone-500">Admin (unlimited):</span> {me?.is_admin ? "Yes" : "No"}</p>
      </div>
      <button onClick={() => auth.logout().then(() => (window.location.href = "/login"))}
        className="rounded-md border border-stone-200 px-4 py-2 text-sm text-stone-600 hover:bg-stone-50">
        Sign out
      </button>
    </div>
  );
}
