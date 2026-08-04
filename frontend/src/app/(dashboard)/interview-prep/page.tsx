"use client";

import { useState } from "react";
import { api } from "@/lib/api";

type Turn = { role: "interviewer" | "candidate"; text: string };

export default function InterviewPrepPage() {
  const [jobId, setJobId] = useState("");
  const [history, setHistory] = useState<Turn[]>([]);
  const [answer, setAnswer] = useState("");
  const [feedback, setFeedback] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function turn(nextHistory: Turn[]) {
    setBusy(true);
    try {
      const res = await api.post<{ feedback: string | null; question: string; done: boolean }>(
        "/interview/roleplay",
        { job_id: Number(jobId), history: nextHistory }
      );
      setFeedback(res.feedback);
      setHistory([...nextHistory, { role: "interviewer", text: res.question }]);
    } finally {
      setBusy(false);
    }
  }

  const start = () => turn([]);
  const send = () => {
    const next = [...history, { role: "candidate" as const, text: answer }];
    setHistory(next);
    setAnswer("");
    turn(next);
  };

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <header>
        <h1 className="text-xl font-semibold tracking-tight">Interview roleplay</h1>
        <p className="mt-1 text-sm text-stone-500">
          The interviewer asks, you answer, and you get feedback on each reply.
        </p>
      </header>

      {history.length === 0 ? (
        <div className="flex gap-2">
          <input
            value={jobId}
            onChange={(e) => setJobId(e.target.value)}
            placeholder="Job ID to interview for"
            className="flex-1 rounded-md border border-stone-200 px-3 py-2 text-sm"
          />
          <button
            onClick={start}
            disabled={!jobId || busy}
            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
          >
            Start
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="space-y-3">
            {history.map((t, i) => (
              <div
                key={i}
                className={t.role === "interviewer" ? "text-stone-900" : "text-stone-500"}
              >
                <span className="text-xs font-semibold uppercase tracking-wide text-brand-600">
                  {t.role === "interviewer" ? "Interviewer" : "You"}
                </span>
                <p className="mt-0.5 text-sm">{t.text}</p>
              </div>
            ))}
          </div>

          {feedback && (
            <div className="rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
              <strong>Feedback:</strong> {feedback}
            </div>
          )}

          <div className="flex gap-2">
            <input
              value={answer}
              onChange={(e) => setAnswer(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && answer && send()}
              placeholder="Type your answer…"
              className="flex-1 rounded-md border border-stone-200 px-3 py-2 text-sm"
            />
            <button
              onClick={send}
              disabled={!answer || busy}
              className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              {busy ? "…" : "Answer"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
