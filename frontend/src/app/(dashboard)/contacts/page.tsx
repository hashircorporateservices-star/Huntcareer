"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

type Contact = {
  id: number;
  name: string;
  title: string | null;
  email: string | null;
  linkedin_url: string | null;
  revealed: boolean;
  locked?: boolean;
  company?: { name: string };
};

export default function ContactsPage() {
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [credits, setCredits] = useState(0);
  const [busy, setBusy] = useState<number | null>(null);
  const [err, setErr] = useState<string | null>(null);

  async function load() {
    const r = await api.get<{ credits: number; contacts: Contact[] }>("/contacts");
    setCredits(r.credits);
    setContacts(r.contacts);
  }
  useEffect(() => {
    load().catch(() => {});
  }, []);

  async function reveal(id: number) {
    setBusy(id);
    setErr(null);
    try {
      const r = await api.post<{ contact: Contact; credits: number }>(`/contacts/${id}/reveal`);
      setCredits(r.credits);
      setContacts((cs) => cs.map((c) => (c.id === id ? r.contact : c)));
    } catch {
      setErr("Out of credits — upgrade or wait for your monthly grant.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <div className="space-y-6">
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold tracking-tight">Hiring manager contacts</h1>
        <span className="figure rounded-full bg-brand-50 px-3 py-1 text-sm text-brand-700">
          {credits === Number.MAX_SAFE_INTEGER ? "∞" : credits} credits
        </span>
      </header>

      {err && <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{err}</div>}

      <ul className="space-y-2">
        {contacts.map((c) => (
          <li key={c.id} className="flex items-center justify-between rounded-xl border border-stone-200 bg-white p-4">
            <div>
              <p className="text-sm font-medium">
                {c.name}
                {c.title && <span className="ml-2 text-xs text-stone-400">{c.title}</span>}
              </p>
              <p className="mt-0.5 text-xs text-stone-500">{c.company?.name}</p>
              {c.revealed ? (
                <p className="mt-1 text-xs text-brand-700">{c.email} · {c.linkedin_url}</p>
              ) : (
                <p className="mt-1 text-xs text-stone-400">Contact details hidden</p>
              )}
            </div>
            {!c.revealed && (
              <button
                onClick={() => reveal(c.id)}
                disabled={busy === c.id}
                className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
              >
                {busy === c.id ? "…" : "Reveal (1 credit)"}
              </button>
            )}
          </li>
        ))}
        {contacts.length === 0 && (
          <p className="text-sm text-stone-500">No contacts yet. These populate from a contacts data source (to integrate).</p>
        )}
      </ul>
    </div>
  );
}
