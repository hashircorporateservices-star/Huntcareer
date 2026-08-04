# JobCopilot feature parity → HuntCareer

Every feature from the three screenshots, mapped to what exists vs. what this batch adds.
"Scout" = HuntCareer's name for one auto-apply agent (JobCopilot calls it a "Copilot").

| JobCopilot feature (from screenshots) | HuntCareer | Where |
|---|---|---|
| "We found N matching jobs in 24h" dashboard | **added** | `frontend .../dashboard/page.tsx` (matches view) |
| Auto-fill applications daily **for review** | already built | `AutoApplyService` → Review Queue |
| Job match cards (title, company, type, location) | already built | Review Queue + matches view |
| "+N more ready to apply" | **added** | matches view |
| Find jobs automatically 24/7 | already built | scheduler + `GmailJobAlertService` + source adapters (todo) |
| Apply with **tailored** applications | already built | `ResumeTailoringService` + `CoverLetterService` |
| Multiple Copilots (1 / 3 by plan) → **Scouts** | **added** | `auto_apply_rules` = a Scout; count capped by plan (`config/plans.php`) |
| Up to 20 / 50 job matches daily | already built (caps) | `config/copilot.php` prep/submit caps + per-plan `daily_match_cap` |
| Tailor resume for **every** application (Elite) | already built | plan flag `tailor_every_application` |
| Hiring Manager Contacts | **added** | `hiring_manager_contacts` table + credit reveal |
| Contact employers directly / reach before posted | **added** (data model) | `hiring_manager_contacts` |
| Job Application Tracker | already built | `applications` + `application_events` |
| Credits (12 / 20 per month) | **added** | `credit_transactions` ledger + monthly grant |
| Chrome Extension (the autofill mechanism) | **spec'd** | see "Chrome extension" below — thin MV3 client of the API |
| AI Resume Builder | already built | `ResumeTailoringService` (extend to from-scratch) |
| AI Cover Letter Builder | already built | `CoverLetterService` |
| AI Interview Roleplay | partial → **extend** | `interview_questions` exists; add interactive roleplay endpoint |
| AI Career Tools | bucket | salary estimator, follow-up generator (from your original spec) |
| Weekly / Monthly / Quarterly pricing toggle | **added** | `config/plans.php` + pricing page |
| Premium AED 99 / Elite AED 129 | **added** (your numbers) | `config/plans.php` |
| Billing | **added** (model) | `subscriptions` table; wire to Lemon Squeezy (you know it from HuntPDF) |

## Credits — what they're for
JobCopilot's screenshots don't spell it out, so HuntCareer defines it explicitly:
**1 credit = reveal one hiring-manager contact.** Premium grants 12/mo, Elite 20/mo.
This keeps a real cost on the most valuable action and matches the "Contacts" upsell.

## Chrome extension (the "automate applications" piece)
A thin MV3 extension is the honest way to do JobCopilot-style autofill without a
server-side bot: it reads the approved queue item from the HuntCareer API, and when
you're on the employer's application page it fills the fields. **You** click submit.
That's browser-assisted apply — same as JobCopilot, same as Simplify/Huntr — and it's
what keeps you clear of "unattended mass submission" ToS bans. Build order below.

## Suggested build order
1. Google OAuth + Sanctum (unlocks Gmail ingestion + login).
2. Billing (Lemon Squeezy) + plan gating + monthly credit grant.
3. First job-source adapter (Greenhouse — official API, also submittable).
4. Chrome extension (autofill client).
5. Interview roleplay endpoint + Career Tools (salary estimator, follow-up generator).

## Build-order status (updated)
1. Auth (Google/Microsoft/Facebook + Gmail scope) — DONE
2. Billing (Lemon Squeezy checkout + webhook + credit grant) + plan gating — DONE
3. Job sources (Adzuna + Greenhouse + Gmail) — DONE
4. Chrome extension (autofill client) — DONE (browser-extension/)
5. Interview roleplay + Career Tools (salary estimator, follow-up) — DONE

Remaining stubs (not in the build order): CRUD controllers for jobs / resumes /
applications / recruiters / analytics — routes exist, controllers still to write.

## Visa focus + match tiers + 10-feature checklist (latest)

**Visa / work-permit focus:** `VisaSponsorshipDetector` flags jobs that state
sponsorship on ingest (`jobs.visa_sponsorship`). A Scout can require sponsorship
(`require_visa_sponsorship`) to match only confirmed-sponsoring roles. Best-effort:
it matches jobs that *explicitly* mention sponsorship, not a guarantee the employer will.

**Match tiers (50 / 75 / 100):** the Scout wizard picks a tier.
- score >= tier -> full tailored application prepared for review
- 50..tier      -> borderline: listed for optional review (not tailored/auto-applied)
- below 50      -> only shown if "include below-threshold" is on; never auto-applied
Match scores are AI estimates, so "100%" means the model's top confidence, not certainty.

**The 10-feature checklist:**
1. Tailor resume for every application — DONE (quality + country-convention prompt)
2. Automate applications — DONE (review-first / official-ATS auto-submit)
3. Save job applications for review — DONE (Review Queue)
4. Hiring Manager Contacts — DONE (list + credit reveal + page)
5. Job Application Tracker — DONE (Applications + status transitions)
6. Chrome Extension — DONE (browser-extension/)
7. AI Resume Builder — DONE (tailor + from-scratch build, quality guidance baked in)
8. AI Cover Letter Builder — DONE (quality + anti-AI-tone guidance)
9. AI Interview Roleplay — DONE
10. AI Career Tools — DONE (salary estimator + follow-up generator)
