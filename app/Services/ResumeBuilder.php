<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class ResumeBuilder
{
    public function __construct(
        protected ResumePromptBuilder $promptBuilder
    ) {
    }

    /**
     * Build resume content from job description, original resume text, and mode.
     * Returns structured data for the template (summary string, experience bullets as arrays).
     *
     * @return array{name: string, contact: array, summary: string, experience: array, education: array, ats_highlights: array}
     */
    public function build(
        string $jobDescription,
        string $originalResumeText,
        string $mode = ResumePromptBuilder::MODE_NORMAL
    ): array {
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($mode);
        $userPrompt = $this->promptBuilder->buildUserPrompt($jobDescription, $originalResumeText, $mode);

        $response = OpenAI::chat()->create([
            'model' => config('openai.chat.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $rawContent = $response->choices[0]->message->content;
        $json = $this->extractJson($rawContent);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse resume JSON: ' . json_last_error_msg());
        }

        return $this->normalizeStructure($data);
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/^[\s\n]*(\{[\s\S]*\})[\s\n]*$/', $content, $m)) {
            return $m[1];
        }

        return $content;
    }

    /**
     * Normalize API response: summary as string, each experience description as list of bullet strings.
     *
     * @param array<string, mixed> $data
     * @return array{name: string, contact: array, summary: string, experience: array, education: array, ats_highlights: array}
     */
    private function normalizeStructure(array $data): array
    {
        $summary = $data['summary'] ?? '';
        $summary = is_array($summary) ? implode(' ', $summary) : (string) $summary;

        $experience = [];
        foreach ($data['experience'] ?? [] as $job) {
            $job = is_array($job) ? $job : [];
            $desc = $job['description'] ?? '';
            $bullets = is_array($desc)
                ? array_values(array_filter(array_map('trim', $desc)))
                : array_values(array_filter(array_map('trim', explode("\n", (string) $desc))));
            $bullets = array_map([$this, 'stripLeadingBullet'], $bullets);
            $experience[] = [
                'title' => $job['title'] ?? '',
                'company' => $job['company'] ?? '',
                'dates' => $job['dates'] ?? '',
                'bullets' => $bullets,
            ];
        }

        $education = [];
        foreach ($data['education'] ?? [] as $edu) {
            $edu = is_array($edu) ? $edu : [];
            $education[] = [
                'degree' => $edu['degree'] ?? '',
                'institution' => $edu['institution'] ?? '',
                'dates' => $edu['dates'] ?? '',
            ];
        }

        $contact = $this->normalizeContact($data);
        $atsHighlights = $this->normalizeAtsHighlights($data);

        return [
            'name' => $data['name'] ?? '',
            'contact' => $contact,
            'summary' => $summary,
            'experience' => $experience,
            'education' => $education,
            'ats_highlights' => $atsHighlights,
        ];
    }

    /**
     * Build contact array: email, phone, then links. Supports API returning "contact" or legacy "email"/"phone" + "contact_links".
     *
     * @param array<string, mixed> $data
     * @return array<int, string|array{label: string, url: string}>
     */
    private function normalizeContact(array $data): array
    {
        if (!empty($data['contact']) && is_array($data['contact'])) {
            $out = [];
            foreach ($data['contact'] as $item) {
                if (is_array($item) && isset($item['url'])) {
                    $out[] = [
                        'label' => $this->stripMarkdownEmphasis($item['label'] ?? $item['url']),
                        'url' => trim((string) $item['url']),
                    ];
                } elseif (is_string($item) && trim($item) !== '') {
                    $out[] = $this->stripMarkdownEmphasis($item);
                }
            }
            return $out;
        }
        $contact = [];
        if (!empty($data['email'])) {
            $contact[] = trim((string) $data['email']);
        }
        if (!empty($data['phone'])) {
            $contact[] = trim((string) $data['phone']);
        }
        foreach ($data['contact_links'] ?? [] as $link) {
            if (is_array($link) && !empty($link['url'])) {
                $contact[] = [
                    'label' => $this->stripMarkdownEmphasis($link['label'] ?? $link['url']),
                    'url' => trim((string) $link['url']),
                ];
            }
        }
        return $contact;
    }

    /**
     * Build ats_highlights: array of { heading, items }. Supports API returning "ats_highlights" or legacy "skills".
     *
     * @param array<string, mixed> $data
     * @return array<int, array{heading: string, items: array<int, string>}>
     */
    private function normalizeAtsHighlights(array $data): array
    {
        if (!empty($data['ats_highlights']) && is_array($data['ats_highlights'])) {
            $out = [];
            foreach ($data['ats_highlights'] as $section) {
                if (!is_array($section)) {
                    continue;
                }
                $heading = $section['heading'] ?? $section['title'] ?? 'Highlights';
                $items = $section['items'] ?? [];
                $items = is_array($items) ? array_map([$this, 'stripMarkdownEmphasis'], $items) : [];
                $items = array_values(array_filter(array_map('trim', $items)));
                if ($heading !== '' || !empty($items)) {
                    $out[] = ['heading' => trim((string) $heading), 'items' => $items];
                }
            }
            return $out;
        }
        $skills = array_map([$this, 'stripMarkdownEmphasis'], $data['skills'] ?? []);
        $skills = array_values(array_filter(array_map('trim', $skills)));
        if (empty($skills)) {
            return [];
        }
        return [['heading' => 'Skills', 'items' => $skills]];
    }

    /**
     * Remove leading markdown/list bullet so the template's <li> doesn't double-render bullets.
     */
    private function stripLeadingBullet(string $line): string
    {
        return trim(preg_replace('/^[\s]*[-*•]\s+/u', '', $line));
    }

    /**
     * Strip ** and * emphasis from plain text (e.g. skills) so export doesn't show raw asterisks.
     */
    private function stripMarkdownEmphasis(mixed $skill): string
    {
        $s = is_string($skill) ? $skill : (string) $skill;
        $s = str_replace('**', '', $s);
        $s = preg_replace('/\*([^*]+)\*/', '$1', $s);

        return trim($s);
    }
}
