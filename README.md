# Resume ATS Tool

A Laravel web app that helps you tailor your resume to job descriptions. Paste a job description, upload your resume, and generate an ATS-optimized version that matches the role.

## Features

- **ATS keyword matching** — Uses relevant keywords from the job description with natural phrasing
- **Two creativity modes**
  - **Normal** — Stays close to your resume; no invented content
  - **Creative** — Can infer typical responsibilities from your experience to better match the job
- **Auth** — Email/password sign up and login (Laravel Breeze)
- **Job description** — Paste and store one job description (latest used for generation)
- **Resume upload** — Upload one resume (PDF, HTML, or TXT); stored locally or in Azure Blob
- **Generated resume** — Download as clean HTML; open in browser and print to PDF
- **Mobile-friendly** — Responsive dashboard

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm (for frontend assets)
- SQLite (default) or MySQL/PostgreSQL
- [OpenAI API key](https://platform.openai.com/api-keys) for resume generation

## Installation

1. Clone the repo and install PHP dependencies:

   ```bash
   composer install
   ```

2. Copy environment file and generate app key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Create the database and run migrations (default is SQLite):

   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

4. Install frontend dependencies and build assets:

   ```bash
   npm install
   npm run build
   ```

5. (Optional) Link storage for local resume uploads:

   ```bash
   php artisan storage:link
   ```

## Environment

- **OpenAI** — Set `OPENAI_API_KEY` in `.env`. Resume generation uses `gpt-4o-mini` by default; override with `OPENAI_CHAT_MODEL` if needed.
- **Resume storage** — Default is local (`storage/app/resumes`). For Azure Blob, set `AZURE_STORAGE_CONNECTION_STRING`, `AZURE_STORAGE_CONTAINER`, and `RESUMES_DISK=resumes_azure` in `.env`.

See `.env.example` for all options (job description length, upload size, etc.).

## Running the app

```bash
php artisan serve
```

For live asset reload during development:

```bash
npm run dev
```

Then open [http://localhost:8000](http://localhost:8000). Register or log in, paste a job description, upload your resume, and generate a tailored version.

## Tech stack

- [Laravel](https://laravel.com) (PHP)
- [Laravel Breeze](https://laravel.com/docs/starter-kits#breeze) (Blade, Tailwind)
- [OpenAI PHP](https://github.com/openai-php/laravel) for resume generation
- Optional: Azure Blob Storage via `league/flysystem-azure-blob-storage`
- PDF text extraction: `smalot/pdfparser`

## License

MIT.
