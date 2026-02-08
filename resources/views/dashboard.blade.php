<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-gray-900 tracking-tight">
            {{ __('Resume ATS Optimization') }}
        </h1>
        <p class="mt-0.5 text-sm font-normal text-gray-500">
            Tailor your resume to each job. Optimizes for keyword match, role alignment, and recruiter readability.
        </p>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Step-based flow --}}
            <div class="space-y-0">
                @php
                    $step1Complete = !empty(trim($jobContent ?? ''));
                    $step2Complete = ($originalResume ?? null) && ($originalResume->exists ?? false);
                    $step3Complete = ($generatedResumes ?? collect())->isNotEmpty();
                @endphp

                <form action="{{ route('dashboard.generate') }}" method="POST" enctype="multipart/form-data" class="space-y-0"
                    x-data="{
                        generating: false,
                        contentLength: {{ strlen(trim($jobContent ?? '')) }},
                        wordCount: {{ str_word_count(trim($jobContent ?? '')) }},
                        hasFile: false,
                        fileName: '',
                        replacing: false,
                        get canGenerate() {
                            const hasJob = this.contentLength > 0 || {{ $step1Complete ? 'true' : 'false' }};
                            const hasResume = this.hasFile || {{ $step2Complete ? 'true' : 'false' }};
                            return hasJob && hasResume;
                        }
                    }"
                    @submit="generating = true">
                    @csrf
                    <input type="hidden" name="mode" value="best_fit">

                    {{-- Step 1: Paste Job Description --}}
                    <section class="relative">
                        <div class="flex gap-4">
                            <div class="flex shrink-0 flex-col items-center">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-gray-300 bg-white text-gray-600">
                                    <span class="text-sm font-semibold">1</span>
                                </div>
                                <div class="mt-1 w-px flex-1 bg-gray-200" aria-hidden="true"></div>
                            </div>
                            <div class="min-w-0 flex-1 pb-12">
                                <h2 class="text-base font-semibold text-gray-900">
                                    Paste Job Description
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    We extract keywords, responsibilities, and role intent to tailor your resume.
                                </p>
                                <textarea
                                    name="content"
                                    rows="8"
                                    @input="const v = $event.target.value.trim(); contentLength = v.length; wordCount = v.split(/\s+/).filter(Boolean).length"
                                    class="mt-4 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    placeholder="Paste the full job description here. Example:&#10;&#10;Senior Product Manager — We're looking for a PM with 5+ years experience in B2B SaaS. You'll own the roadmap, work with engineering and design, and drive outcomes through data. Requirements: SQL, A/B testing, stakeholder management…"
                                >{{ old('content', $jobContent ?? '') }}</textarea>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span x-text="wordCount + ' words'"></span>
                                        @if($step1Complete && ($jobDescription ?? null))
                                            <span class="text-emerald-600">· Saved</span>
                                            @if($jobDescription->created_at ?? null)
                                                <span>{{ $jobDescription->created_at->format('M j, Y') }}</span>
                                            @endif
                                        @endif
                                        <span class="text-gray-300">· Role —</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Step 2: Upload Resume --}}
                    <section class="relative">
                        <div class="flex gap-4">
                            <div class="flex shrink-0 flex-col items-center">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-gray-300 bg-white text-gray-600">
                                    <span class="text-sm font-semibold">2</span>
                                </div>
                                <div class="mt-1 w-px flex-1 bg-gray-200" aria-hidden="true"></div>
                            </div>
                            <div class="min-w-0 flex-1 pb-12">
                                <h2 class="text-base font-semibold text-gray-900">
                                    Upload Resume
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    PDF preferred. We'll use this as the base for your tailored version.
                                </p>

                                @if($originalResume ?? null)
                                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $originalResume->filename ?? 'resume' }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ $originalResume->created_at->format('M j, Y g:i A') }}
                                                    @if(str_ends_with(strtolower($originalResume->filename ?? ''), '.pdf'))
                                                        <span class="text-emerald-600">· PDF detected · ATS-safe</span>
                                                    @else
                                                        <span class="text-gray-500">· Uploaded</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <button type="button" @click="replacing = !replacing" class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                                <span x-text="replacing ? 'Cancel' : 'Replace file'"></span>
                                            </button>
                                        </div>
                                        <div x-show="replacing" x-cloak class="mt-3 pt-3 border-t border-gray-200">
                                            <input type="file" name="resume" accept=".pdf,.html,.htm,.txt" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @change="hasFile = $event.target.files.length > 0">
                                        </div>
                                    </div>
                                @else
                                    <label class="mt-4 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50/50 px-6 py-8 text-center hover:border-gray-400 hover:bg-gray-50 transition-colors cursor-pointer">
                                        <input type="file" name="resume" accept=".pdf,.html,.htm,.txt" required class="hidden" @change="hasFile = $event.target.files.length > 0; fileName = $event.target.files[0]?.name || ''">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v20c0 4.418 3.582 8 8 8h16c4.418 0 8-3.582 8-8V14M24 4v28M16 20l8-8 8 8"/></svg>
                                        <span class="mt-2 text-sm text-gray-600" x-text="fileName || 'Drop your resume here or click to browse'"></span>
                                        <span class="mt-0.5 text-xs text-gray-400">PDF, HTML, or TXT · Max {{ $resumeUploadMaxMb ?? 10 }} MB</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- Generate (only button) --}}
                    <section class="relative">
                        <div class="flex gap-4">
                            <div class="flex shrink-0 flex-col items-center">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-gray-300 bg-white text-gray-600">
                                    <span class="text-sm font-semibold">3</span>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 pb-10">
                                <h2 class="text-base font-semibold text-gray-900">
                                    Generate Optimized Resume
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    Tailored to this role. Keyword-aligned. Recruiter-ready.
                                </p>
                                <div class="mt-4">
                                    <button type="submit"
                                        :disabled="generating || !canGenerate"
                                        class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-90 disabled:cursor-wait"
                                        :class="canGenerate && !generating ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2' : 'bg-gray-300'">
                                        <span x-show="!generating">Generate ATS-Optimized Resume</span>
                                        <span x-show="generating" x-cloak class="inline-flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Generating…
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            {{-- Generated resumes --}}
            @if(($generatedResumes ?? collect())->isNotEmpty())
                <section id="generated-resumes" class="mt-12 border-t border-gray-200 pt-10 scroll-mt-6">
                    <h2 class="text-base font-semibold text-gray-900">Your generated resume is ready</h2>
                    <p class="mt-1 text-sm text-gray-500">Click below to open in a new tab, then use your browser’s Print to save as PDF.</p>
                    <ul class="mt-4 space-y-3">
                        @foreach($generatedResumes as $gen)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 {{ (session('generated_id')) === $gen->id ? 'ring-2 ring-indigo-500 ring-offset-2' : '' }}">
                                <span class="text-sm text-gray-700">
                                    {{ $gen->filename ?? 'Resume' }} · {{ $gen->created_at->format('M j, Y g:i A') }}
                                    @if(session('generated_id') === $gen->id)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Just generated</span>
                                    @endif
                                </span>
                                <a href="{{ route('dashboard.resume.download', $gen) }}?print=1" target="_blank" rel="noopener" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                    View &amp; Print / Save as PDF
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
