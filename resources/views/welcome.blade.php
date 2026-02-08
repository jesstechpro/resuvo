<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} — ATS-Optimized Resumes for Every Job</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">
        @endif
    </head>
    <body class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] font-sans antialiased">
        {{-- Nav --}}
        <header class="w-full border-b border-gray-200 dark:border-[#3E3E3A]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</a>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            {{-- Hero --}}
            <section class="text-center mb-16 sm:mb-20">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                    Tailor your resume to every job
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 dark:text-[#A1A09A] max-w-2xl mx-auto mb-10">
                    Paste the job description, upload your resume, and get an ATS-optimized, one-page resume that matches the role. Keyword-aligned and recruiter-ready.
                </p>
                @guest
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Get started — it's free
                        </a>
                    @endif
                @endguest
            </section>

            {{-- How it works (3 steps from DESIGN) --}}
            <section class="mb-16 sm:mb-20">
                <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-8">How it works</h2>
                <ol class="space-y-8 sm:space-y-10">
                    <li class="flex gap-4 sm:gap-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-semibold text-sm">1</span>
                        <div>
                            <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Paste the job description</h3>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A]">We use the role, requirements, and keywords to tailor your resume. Your latest job description is saved and used for each generation.</p>
                        </div>
                    </li>
                    <li class="flex gap-4 sm:gap-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-semibold text-sm">2</span>
                        <div>
                            <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Upload your resume</h3>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A]">PDF, HTML, or TXT. We extract the text and use it as the source of truth—no invented employers or dates.</p>
                        </div>
                    </li>
                    <li class="flex gap-4 sm:gap-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-semibold text-sm">3</span>
                        <div>
                            <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Generate your optimized resume</h3>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A]">AI rewrites your resume for this role: best-fit bullets, ATS keywords, and a clean one-page layout. Open in the browser and print to PDF.</p>
                        </div>
                    </li>
                </ol>
            </section>

            {{-- Features (from DESIGN.md) --}}
            <section class="mb-16 sm:mb-20">
                <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-8">Why use {{ config('app.name') }}</h2>
                <ul class="grid sm:grid-cols-2 gap-6">
                    <li class="flex gap-3 p-4 rounded-lg bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3E3E3A] shadow-sm">
                        <svg class="h-5 w-5 shrink-0 text-indigo-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">ATS keyword matching</span>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A] mt-0.5">Relevant job terms and synonyms woven into summary, bullets, and skills so ATS systems recognize your fit.</p>
                        </div>
                    </li>
                    <li class="flex gap-3 p-4 rounded-lg bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3E3E3A] shadow-sm">
                        <svg class="h-5 w-5 shrink-0 text-indigo-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Best-fit mode</span>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A] mt-0.5">Every experience bullet maps to a job requirement. Evidence-first, no filler—written for human recruiters.</p>
                        </div>
                    </li>
                    <li class="flex gap-3 p-4 rounded-lg bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3E3E3A] shadow-sm">
                        <svg class="h-5 w-5 shrink-0 text-indigo-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div>
                            <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">One-page, print to PDF</span>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A] mt-0.5">Structured template keeps the resume to one page. Open in the browser and use Print to save as PDF—no extra tools.</p>
                        </div>
                    </li>
                    <li class="flex gap-3 p-4 rounded-lg bg-white dark:bg-[#161615] border border-gray-200 dark:border-[#3E3E3A] shadow-sm">
                        <svg class="h-5 w-5 shrink-0 text-indigo-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <div>
                            <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Mobile-friendly</span>
                            <p class="text-sm text-gray-600 dark:text-[#A1A09A] mt-0.5">Dashboard and flow work on small screens. Tailor your resume from anywhere.</p>
                        </div>
                    </li>
                </ul>
            </section>

            {{-- CTA --}}
            @guest
            <section class="text-center py-12 sm:py-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50">
                <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Ready to land more interviews?</h2>
                <p class="text-gray-600 dark:text-[#A1A09A] mb-6 max-w-md mx-auto">Create a free account. Paste a job, upload your resume, and get your first ATS-optimized resume in minutes.</p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Register — free
                    </a>
                @endif
            </section>
            @endguest
        </main>

        <footer class="border-t border-gray-200 dark:border-[#3E3E3A] mt-auto">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500 dark:text-[#A1A09A]">
                {{ config('app.name') }} — ATS resume optimization. Your data stays yours.
            </div>
        </footer>
    </body>
</html>
