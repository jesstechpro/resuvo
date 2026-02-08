<?php

namespace App\Services;

class ResumePromptBuilder
{
    public const MODE_NORMAL = 'normal';
    public const MODE_BEST_FIT = 'best_fit';

    public function buildSystemPrompt(string $mode): string
    {
        $modeInstructions = $mode === self::MODE_BEST_FIT
            ? $this->getBestFitInstructions()
            : 'Use only information explicitly stated in the candidate\'s resume. Do not invent or assume any content. Rephrase to better match the job. Keep experience in chronological order (most recent first); do not reorder jobs by relevance.';

        $atsBlock = $mode === self::MODE_BEST_FIT
            ? "\n\n**ATS (Best Fit):** Use the job's exact terms in bullets where they accurately describe the candidate's experience. Every critical requirement from the job must appear as proven evidence inside an experience bullet, not only in summary or skills. No keyword stuffing; every term must be backed by a specific bullet."
            : '';

        $outputFormat = $mode === self::MODE_BEST_FIT
            ? 'Respond with only a single JSON object, no other text. Keys: "name", "contact", "summary", "experience", "education", "ats_highlights". "contact" = array of contact items in ATS/relevance order: each item is a string (e.g. email, phone) or object with "label" and "url". For links use full URLs (https://...) so the resume prints with the complete link. Include email and phone first, then professional links that match the role. "summary" = one short paragraph. "experience" = array of objects with "title", "company", "dates", "description" (string or array of strings—each item is one bullet). List experience in strict chronological order, most recent first. Do not reorder jobs by relevance; keep the candidate\'s actual timeline. Include only bullets that directly prove a job requirement. "education" = array with "degree", "institution", "dates". "ats_highlights" = array of objects with "heading" and "items" (array of strings), ordered by relevance to the job: e.g. Skills, Certifications, Languages. Include only sections that help ATS match the job; order sections and items by job relevance. Use Markdown ** and * for keyword emphasis in summary and bullets (sparingly).'
            : 'Respond with only a single JSON object, no other text. Keys: "name", "contact", "summary", "experience", "education", "ats_highlights". "contact" = array of contact items: each item a string (email, phone) or object with "label" and "url". Use full URLs (https://...) for links so the printed resume shows the complete link. Order: email, phone, then links. "summary" = one short, catchy paragraph (not bulleted). "experience" = array of objects with "title", "company", "dates", "description" where "description" is a multi-line string or array of strings—one bullet per line or element. List experience in strict chronological order, most recent first. Do not reorder jobs by relevance; keep the candidate\'s actual timeline. "education" = array with "degree", "institution", "dates". "ats_highlights" = array of objects with "heading" and "items" (array of strings), e.g. Skills, Certifications, Languages—order sections and items by relevance to the job for ATS. Use Markdown ** and * for keywords (do not overuse).';

        return <<<PROMPT
You are an expert resume writer. Your task is to rewrite the candidate's resume so it matches the job description and performs well with ATS (Applicant Tracking Systems).

**One-page limit:** The resume will be printed and must fit on one page. Do not include everything from the original resume. Prioritize: include only what is most relevant to the job. Keep summary to one short paragraph. Limit experience to the most relevant roles and only bullets that prove job requirements. Prefer fewer, stronger bullets over many weak ones. Include only the most relevant education and ats_highlights. Omit or shorten less relevant content to stay within one printed page.

**ATS matching:** Use relevant keywords from the job description throughout the resume. For ATS, include both exact terms and close synonyms where they fit naturally.{$atsBlock}

**Mode:** {$modeInstructions}

**Output format:** {$outputFormat} Use only the candidate's real information. For contact links use full URLs (e.g. https://linkedin.com/in/username) so the printed resume shows the complete link.
PROMPT;
    }

    public function buildUserPrompt(string $jobDescription, string $originalResumeText, string $mode = self::MODE_NORMAL): string
    {
        $firstLine = trim(explode("\n", $jobDescription)[0] ?? '');
        $targetRoleNote = $firstLine !== ''
            ? "**Target job title / role:** {$firstLine}\n\n"
            : '';

        $atsReminder = $mode === self::MODE_BEST_FIT
            ? "Best-fit: the most recent job should fulfill ~90% of requirements; every requirement must be proven by at least one experience bullet; every bullet must map to a requirement. No filler. Output only the JSON object.\n\n"
            : '';

        return <<<PROMPT
{$targetRoleNote}{$atsReminder}**Job description:**
{$jobDescription}

**Candidate's current resume (raw text):**
{$originalResumeText}

Return only the JSON object, no markdown or explanation.
PROMPT;
    }

    private function getBestFitInstructions(): string
    {
        return <<<'BESTFIT'
You are acting as the hiring manager for the role, not as a copywriter. Your task is to rewrite the resume so that every experience bullet explicitly proves a requirement from the job description. Do not rely on summary or skills list to satisfy requirements—evidence belongs in EXPERIENCE bullets.

**Non-negotiable rules**
• Experience must be listed in strict chronological order, most recent first. Do not reorder jobs by relevance to the role; preserve the candidate's actual timeline.
• The most recent job (first in the experience list) should fulfill roughly 90% of the job requirements. Prioritize evidence from that role; use older roles mainly for the remainder.
• Every job requirement must be supported by at least one bullet in the EXPERIENCE section.
• Every experience bullet must map directly to a specific job requirement. If a bullet does not prove a requirement, remove it.
• Do not invent employers or dates. Job titles may be adjusted to a synonymous or better-fit title when it accurately describes the same role (e.g. "Software Engineer" → "Backend Developer" for a backend-focused role). You may add bullets that can be reasonably assumed from the candidate's role and background to better match the job—candidates often omit these on a generic resume. Only add what is plausible given their stated experience; if a requirement cannot be supported even by reasonable inference, omit it.
• Use specific actions, outcomes, and context—avoid vague language. Write for a real hiring manager, not ATS padding.
• Remove generic filler and duplicated responsibilities. No keyword dumping; no unsupported claims.

**Process to follow**
1. Break the job description into explicit requirements (skills, behaviors, scope, outcomes).
2. Audit the resume to find verifiable evidence for each requirement.
3. Rewrite experience bullets so the evidence is obvious without inference. Each bullet should answer: "Why does this qualify the candidate for this role?"
4. Eliminate bullets that do not support the job. Only include bullets that directly prove job requirements.
5. Keep the resume to one printed page: prefer fewer roles and fewer bullets; omit less relevant experience and trim education/ats_highlights if needed.

**Result:** The resume must be role-aligned, clean, optimized for human review first. If the resume could be sent to five different companies unchanged, it is not best-fit. Use the candidate's real employers, dates, and history; job titles may be reframed with synonymous or better-fit titles when they accurately describe the same role. Do not invent employers or dates.
BESTFIT;
    }
}
