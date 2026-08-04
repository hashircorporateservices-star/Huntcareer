# Repo structure

`[provided]` = a file I wrote, drop it in as-is.
`[scaffold]` = created by the framework installer (see the two commands at the bottom); don't hand-write these.

```
huntcareer/
├── .gitignore                              [provided]
├── README.md                               [provided]
├── DEPLOY.md                               [provided]
├── STRUCTURE.md                            [provided]  (this file)
│
├── backend/                                # Laravel 12 API
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   ├── RunAutoApplyCommand.php     [provided]
│   │   │   └── IngestEmailJobsCommand.php  [provided]
│   │   ├── Http/Controllers/
│   │   │   ├── Controller.php              [scaffold]
│   │   │   └── Api/
│   │   │       ├── AutoApplyRuleController.php   [provided]
│   │   │       └── ReviewQueueController.php     [provided]
│   │   ├── Models/                         [provided]  (12 files)
│   │   │   ├── User.php  Job.php  JobMatch.php  Company.php
│   │   │   ├── Resume.php  CoverLetter.php  Application.php
│   │   │   ├── ApplicationEvent.php  AutoApplyRule.php
│   │   │   ├── AutoApplyQueue.php  AuditLog.php  Setting.php
│   │   ├── Services/                       [provided]  (6 files)
│   │   │   ├── JobMatchingService.php  AutoApplyService.php
│   │   │   ├── ResumeTailoringService.php  CoverLetterService.php
│   │   │   ├── AtsSubmissionService.php  GmailJobAlertService.php
│   │   │   └── Providers/AppServiceProvider.php  [scaffold]
│   │   └── ...
│   ├── bootstrap/app.php                    [scaffold]
│   ├── config/
│   │   ├── copilot.php                      [provided]  ← your domain config
│   │   ├── services.php                     [scaffold, then add ai/ats/google keys — see README]
│   │   └── ... (app, database, queue, ...)  [scaffold]
│   ├── database/
│   │   └── migrations/                      [provided]  (5 core migrations)
│   │       ├── 2025_01_01_000001_create_core_tables.php
│   │       ├── 2025_01_01_000002_create_document_tables.php
│   │       ├── 2025_01_01_000003_create_pipeline_tables.php
│   │       ├── 2025_01_01_000004_create_auto_apply_tables.php
│   │       └── 2025_01_01_000005_create_system_tables.php
│   ├── routes/
│   │   ├── api.php                          [provided]
│   │   ├── console.php                      [provided]
│   │   └── web.php                          [scaffold]
│   ├── public/index.php                     [scaffold]
│   ├── artisan                              [scaffold]
│   ├── composer.json                        [scaffold]
│   └── .env.example                         [scaffold, then fill in — see README]
│
└── frontend/                               # Next.js (App Router)
    ├── src/
    │   ├── app/
    │   │   ├── layout.tsx                    [scaffold]
    │   │   ├── globals.css                   [scaffold]
    │   │   └── (dashboard)/                  [provided]
    │   │       ├── layout.tsx
    │   │       ├── dashboard/page.tsx
    │   │       ├── auto-apply/page.tsx
    │   │       └── review-queue/page.tsx
    │   ├── components/Sidebar.tsx            [provided]
    │   └── lib/api.ts                        [provided]
    ├── package.json                          [scaffold]
    ├── next.config.js                        [scaffold]
    ├── tsconfig.json                         [scaffold]
    ├── tailwind.config.ts                    [scaffold]
    ├── postcss.config.js                     [scaffold]
    └── .env.local                            (not committed — set NEXT_PUBLIC_API_URL)
```

## Filling in the [scaffold] files

Run these once, then copy the `[provided]` files over the top:

```bash
# Laravel skeleton (into backend/)
composer create-project laravel/laravel backend
cd backend && composer require laravel/sanctum google/apiclient && cd ..

# Next.js skeleton (into frontend/)
npx create-next-app@latest frontend --ts --tailwind --app --src-dir --eslint
```
```
