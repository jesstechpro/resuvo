# Resume ATS Tool — Design

This document describes the architecture, data flow, and where reusable variables live so they stay consistent across the app.

---

## Overview

The app lets users **paste a job description**, **upload their resume**, and **generate an ATS-optimized resume** tailored to that job. Content is produced by OpenAI; the output is rendered with a single HTML template and can be opened in the browser and printed to PDF.

- **Stack:** Laravel (Blade), Tailwind, OpenAI PHP client, optional Azure Blob Storage.
- **Auth:** Laravel Breeze (email/password).
- **Storage:** Resumes (original + generated) on a configurable disk: local (`resumes`) or Azure Blob (`resumes_azure`).

---

## Architecture

### High-level flow

1. **Dashboard** — User sees a 3-step flow: paste job description → upload resume → generate.
2. **Job description** — Stored in `job_descriptions`; the **latest** one per user is used for generation.
3. **Original resume** — One per user (latest upload); stored on the resumes disk; PDF/HTML/TXT supported; PDF text extracted via `smalot/pdfparser`.
4. **Generation** — `ResumeBuilder` calls OpenAI with system + user prompts from `ResumePromptBuilder`, parses JSON, normalizes to a fixed structure, then renders `resume/template.blade.php` and stores the HTML on the same disk.
5. **Download** — User opens generated HTML (optionally with `?print=1` for auto-print); they use the browser’s Print to save as PDF.

### Layer roles

| Layer | Role |
|-------|------|
| **Controllers** | HTTP, validation, redirects; delegate to services. |
| **Services** | `ResumeBuilder` (OpenAI + normalize), `ResumePromptBuilder` (prompts), `ResumeStorage` (disk I/O), `ResumeTextExtractor` (PDF/HTML/TXT → text). |
| **Models** | `User`, `JobDescription`, `Resume`; relationships and scopes. |
| **Views** | Single resume output: `resources/views/resume/template.blade.php`; dashboard and auth are Blade + Tailwind. |

All **reusable variables** (limits, disk names, model names, section labels) are defined in **config** or **model/class constants** and referenced from one place (see “Reusable variables” below).

---

## Data model

- **User** — Has many `JobDescription`, many `Resume`; `latestJobDescription()` (latest one); `originalResume()` (latest resume with `type = original`).
- **JobDescription** — `user_id`, `content`; used as the “target job” for generation.
- **Resume** — `user_id`, optional `job_description_id`, `type` (`original` | `generated`), `path`, `filename`. Originals go under `originals/{user_id}/`; generated under `generated/{user_id}/{uniqid}_{filename}`.

Resume types are constants on the model: `Resume::TYPE_ORIGINAL`, `Resume::TYPE_GENERATED`. Use these everywhere (e.g. scopes, policies, `JobDescription::generatedResumes()`) instead of string literals.

---

## Resume content pipeline

1. **Input** — Job description text + original resume file content.
2. **Text extraction** — `ResumeTextExtractor`: PDF → parser text; HTML → `strip_tags`; TXT → as-is. Minimum length enforced after extraction (see config).
3. **Prompts** — `ResumePromptBuilder` builds system (mode-specific) + user prompt. Modes: `MODE_NORMAL`, `MODE_BEST_FIT` (constants on `ResumePromptBuilder`).
4. **OpenAI** — Single chat completion; model from `config('openai.chat.model')`.
5. **Parse** — Response may be in markdown code block or raw JSON; `ResumeBuilder::extractJson()` then `json_decode`.
6. **Normalize** — `ResumeBuilder::normalizeStructure()` produces a fixed shape: `name`, `contact`, `summary`, `experience`, `education`, `ats_highlights`. Experience/education entries have consistent keys; bullets are arrays of strings; markdown emphasis stripped where needed.
7. **Template** — Blade view receives the normalized `$resume` array; sections: Summary, Experience, Education, ATS highlights (headings from data). Output is standalone HTML with inline CSS for print.

---

## Storage

- **Config key:** `config('filesystems.resumes_disk')` — either `resumes` (local) or `resumes_azure` (Azure Blob). All resume read/write goes through this disk.
- **Local disk** — `config/filesystems.php` → `resumes` disk, root `storage_path('app/resumes')`.
- **Azure** — Same config file → `resumes_azure` disk; connection/container/prefix from env. Set `RESUMES_DISK=resumes_azure` and Azure env vars to use it.
- **ResumeStorage** — Uses `config('filesystems.resumes_disk')` and `config('resume.*')` for defaults (e.g. generated filename pattern); no hardcoded disk names or paths in the service.

---

## Reusable variables

These are the single sources of truth. Use them everywhere instead of literals.

### Config (env + config files)

| Purpose | Config key | Env / default |
|--------|------------|----------------|
| Resumes disk | `config('filesystems.resumes_disk')` | `RESUMES_DISK` → `resumes` |
| OpenAI model | `config('openai.chat.model')` | `OPENAI_CHAT_MODEL` → `gpt-4o-mini` |
| Job description max length | `config('resume.job_description.max_length')` | `RESUME_JOB_DESCRIPTION_MAX_LENGTH` → `50000` |
| Resume upload max size (KB) | `config('resume.upload.max_size')` | `RESUME_UPLOAD_MAX_SIZE` → `10240` |
| Resume upload allowed mimes | `config('resume.upload.mimes')` | — → `['pdf', 'html', 'htm', 'txt']` |
| Min extracted text length | `config('resume.min_extracted_text_length')` | `RESUME_MIN_EXTRACTED_TEXT_LENGTH` → `50` |
| Default generated filename | `config('resume.generated_filename')` | — → `resume.html` (pattern uses date in controller) |

Validation rules and `ResumeStorage::storeGenerated()` default filename use these so limits and formats stay consistent. The dashboard receives `resumeUploadMaxMb` from config so the displayed max size matches. (e.g. dashboard “Max 10 MB” and allowed file types match config).

### Model / service constants

| Constant | Value | Use |
|----------|--------|-----|
| `Resume::TYPE_ORIGINAL` | `'original'` | Storing/querying original uploads. |
| `Resume::TYPE_GENERATED` | `'generated'` | Storing/querying generated resumes; use in `JobDescription::generatedResumes()` and scopes. |
| `ResumePromptBuilder::MODE_NORMAL` | `'normal'` | Conservative rewrite, no invented content. |
| `ResumePromptBuilder::MODE_BEST_FIT` | `'best_fit'` | Role-aligned, evidence in bullets; may reframe titles. |

Form inputs and route handling should use these constants (e.g. `mode` validation `in:best_fit` or a list derived from constants).

### Template structure keys

The normalized resume array and the Blade template use the same keys. Do not duplicate these as magic strings; the template expects:

- `name`, `contact` (array of strings or `{ label, url }`)
- `summary` (string, may contain markdown)
- `experience` (array of `{ title, company, dates, bullets }`)
- `education` (array of `{ degree, institution, dates }`)
- `ats_highlights` (array of `{ heading, items }`)

Section headings in the UI (“Summary”, “Experience”, “Education”) are in the template; “Highlights” is the default for ats_highlights sections without a heading. If you add a shared config for section labels later, the template should read from there.

---

## Security and policies

- **ResumePolicy** — Users may only view/download their own resumes; controller uses `$this->authorize('view', $resume)` before serving content.
- **File handling** — Upload validation (type, size) uses the same config as above; filenames are stored but path is server-controlled (no user path in `store()`).
- **OpenAI** — API key from env only; no key in repo.

---

## Error handling

- **Generation** — Failures (e.g. parse error, API error) are caught in `DashboardController::generate()`; user sees a generic message (or detailed in debug). Logs include exception and trace.
- **Missing prerequisites** — No job description or no original resume: validation redirect with clear message.
- **Text extraction too short** — Redirect with message suggesting PDF or pasted text; threshold from `config('resume.min_extracted_text_length')`.
- **Missing file on download** — 404 if the stored path does not exist on the disk.

---

## Summary

- **Single template** — One Blade view for resume output; AI only fills the normalized structure.
- **Config and constants** — All reusable variables (disk, model, limits, mimes, types, modes) live in config or class constants; controllers and services reference them.
- **Resume types** — Always use `Resume::TYPE_ORIGINAL` and `Resume::TYPE_GENERATED` in code and relationships.
- **Prompts** — All prompt text and output shape are in `ResumePromptBuilder` and the normalize step in `ResumeBuilder`; template expects the normalized keys above.
