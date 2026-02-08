# Resume ATS Tool

## Goal
Build a resume tool where users paste a job description, compare it to their resume, and rebuild their resume to match the job.

---

## Features

**ATS matching**
- [x] Match ATS keywords (relevant, not necessarily exact) — *Implemented in `ResumeBuilder` system prompt: "Use relevant keywords from the job description... use synonyms and natural phrasing."*

**Two creativity modes**
1. [x] **Normal** — Match closely to job description; don't invent content
2. [x] **Creative** — Can assume typical roles from given experience to better match the job  
   *Both implemented in `App\Services\ResumeBuilder` with distinct system prompt instructions.*

**Auth & data**
- [x] Email/password login — *Laravel Breeze (Blade); register, login, logout.*
- [x] Paste one job description — *Stored in `job_descriptions` table; latest used for generation.*
- [x] Upload and store one resume (Azure Blob) — *Resume stored on configurable disk: local `resumes` or `resumes_azure` (Azure Blob via league/flysystem-azure-blob-storage). Set `RESUMES_DISK=resumes_azure` and Azure env vars to use Blob.*
- [x] Retrieve generated resume (Azure Blob) — *Generated resumes stored on same disk; download via `/dashboard/resume/{id}/download` (inline HTML).*
- [x] Simple way to get resume in a clean format (prefer no API if possible) — *HTML output; user opens in browser and can print to PDF (no external API).*

**Content & template**
- [x] Use OpenAI to build resume content — *`ResumeBuilder` uses OpenAI chat completions (openai-php/laravel).*
- [x] One output template only — structured, not AI-generated; AI fills it section by section in sequence; requests and feedback are designed for easy parsing into this template — *Single Blade view `resources/views/resume/template.blade.php` with fixed sections (header, summary, experience, education, skills). AI returns one JSON object; normalized and passed to template.*

---

## Technical

- [x] Web app, mobile-optimized — *Responsive layout (Tailwind); dashboard works on small screens.*
- [x] Simple email/password login — *Breeze Blade stack; no email verification required for dashboard.*

---

## Implementation notes

- **Storage:** Default disk for resumes is local (`storage/app/resumes`). For Azure Blob, set `AZURE_STORAGE_CONNECTION_STRING`, `AZURE_STORAGE_CONTAINER`, and `RESUMES_DISK=resumes_azure` in `.env`.
- **Resume input:** PDF, HTML, or TXT upload; PDF text extracted via `smalot/pdfparser` for AI input.
- **OpenAI:** Set `OPENAI_API_KEY` in `.env`. Resume generation uses `gpt-4o-mini` by default.
