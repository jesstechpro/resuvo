<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Services\ResumeBuilder;
use App\Services\ResumePromptBuilder;
use App\Services\ResumeStorage;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{

    public function __construct(
        protected ResumeStorage $resumeStorage,
        protected ResumeTextExtractor $textExtractor,
        protected ResumeBuilder $resumeBuilder
    ) {
    }

    public function index(): View
    {
        $user = auth()->user();
        $jobDescription = $user->latestJobDescription;
        $originalResume = $user->originalResume;
        $generatedResumes = $user->resumes()->generated()->latest()->take(5)->get();

        if (config('app.debug')) {
            Log::debug('Dashboard index', [
                'user_id' => $user->id,
                'original_resume_id' => $originalResume?->id,
                'generated_count' => $generatedResumes->count(),
                'generated_ids' => $generatedResumes->pluck('id')->toArray(),
            ]);
        }

        return view('dashboard', [
            'jobDescription' => $jobDescription,
            'jobContent' => $jobDescription?->content ?? '',
            'originalResume' => $originalResume,
            'generatedResumes' => $generatedResumes,
            'resumeUploadMaxMb' => (int) ceil(config('resume.upload.max_size', 10240) / 1024),
        ]);
    }

    public function storeJobDescription(Request $request): RedirectResponse
    {
        $request->validate([
            'content' => 'required|string|max:' . config('resume.job_description.max_length'),
        ]);

        auth()->user()->jobDescriptions()->create(['content' => $request->input('content')]);

        return redirect()->route('dashboard')->with('status', 'Job description analyzed.');
    }

    public function uploadResume(Request $request): RedirectResponse
    {
        $request->validate([
            'resume' => 'required|file|mimes:' . implode(',', config('resume.upload.mimes')) . '|max:' . config('resume.upload.max_size'),
        ]);

        $user = auth()->user();
        $resume = DB::transaction(fn () => $this->resumeStorage->storeOriginal($user, $request->file('resume')));

        Log::info('Resume uploaded', ['resume_id' => $resume->id, 'user_id' => $user->id]);

        return redirect()->route('dashboard')->with('status', 'Resume confirmed.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->validate(['mode' => 'required|in:best_fit']);

        $user = auth()->user();
        $jobDescription = $user->latestJobDescription;
        $originalResume = $user->originalResume;

        $validationError = $this->validateGenerationPrerequisites($jobDescription, $originalResume);
        if ($validationError) {
            return $validationError;
        }

        try {
            $originalText = $this->textExtractor->extract(
                $this->resumeStorage->getContents($originalResume),
                $originalResume->filename ?? ''
            );
            if (strlen(trim($originalText)) < config('resume.min_extracted_text_length', 50)) {
                return redirect()->route('dashboard')->with('error', 'Could not extract enough text from your resume. Try a PDF or paste the text.');
            }

            $data = $this->resumeBuilder->build($jobDescription->content, $originalText, ResumePromptBuilder::MODE_BEST_FIT);
            $resume = $this->storeGeneratedResume($user, $jobDescription->id, $data);

            Log::info('Resume generated', ['resume_id' => $resume->id, 'user_id' => $user->id]);

            return redirect()->route('dashboard')->withFragment('generated-resumes')->with('status', 'Resume generated.')->with('generated_id', $resume->id);
        } catch (\Throwable $e) {
            Log::error('Resume generation failed', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $message = config('app.debug')
                ? 'Generation failed: ' . $e->getMessage()
                : 'Generation failed. Check your OpenAI API key and try again.';

            return redirect()->route('dashboard')->with('error', $message);
        }
    }

    public function download(Resume $resume): Response
    {
        $this->authorize('view', $resume);

        if (! $this->resumeStorage->exists($resume)) {
            abort(404);
        }

        $content = $this->resumeStorage->getContents($resume);
        $filename = $resume->filename ?? config('resume.generated_filename', 'resume.html');

        if (request()->query('print')) {
            $printScript = '<script>window.onload=function(){window.print();}</script>';
            $content = str_contains($content, '</body>')
                ? str_replace('</body>', $printScript . '</body>', $content)
                : $content . $printScript;
        }

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * @return RedirectResponse|null Null if validation passes
     */
    private function validateGenerationPrerequisites($jobDescription, $originalResume): ?RedirectResponse
    {
        if (! $jobDescription) {
            return redirect()->route('dashboard')->with('error', 'Please paste a job description first.');
        }
        if (! $originalResume || ! $this->resumeStorage->exists($originalResume)) {
            return redirect()->route('dashboard')->with('error', 'Please upload your resume first.');
        }

        return null;
    }

    private function storeGeneratedResume($user, int $jobDescriptionId, array $data): Resume
    {
        $html = view('resume.template', ['resume' => $data])->render();
        $base = pathinfo(config('resume.generated_filename', 'resume.html'), PATHINFO_FILENAME);
        $ext = pathinfo(config('resume.generated_filename', 'resume.html'), PATHINFO_EXTENSION) ?: 'html';
        $filename = $base . '-' . now()->format('Y-m-d-His') . '.' . $ext;

        return DB::transaction(
            fn () => $this->resumeStorage->storeGenerated($user, $jobDescriptionId, $html, $filename)
        );
    }
}
