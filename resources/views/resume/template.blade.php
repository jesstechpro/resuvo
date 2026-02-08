<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume – {{ $resume['name'] ?? 'Resume' }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; font-size: 11pt; line-height: 1.4; max-width: 700px; margin: 0 auto; padding: 1rem; color: #1a1a1a; }
        h1 { font-size: 1.5rem; margin: 0 0 0.25rem 0; font-weight: 600; }
        .contact { font-size: 0.95rem; color: #444; margin-bottom: 1rem; }
        .contact .contact-item + .contact-item::before { content: ' · '; }
        .contact a { color: #444; text-decoration: none; }
        .contact a:hover { text-decoration: underline; }
        h2 { font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #333; margin: 1rem 0 0.4rem 0; padding-bottom: 0.2rem; }
        .summary { margin-bottom: 0.5rem; }
        .experience-item ul { list-style: disc; padding-left: 1.25rem; margin: 0 0 0.5rem 0; }
        .experience-item li { margin-bottom: 0; line-height: 1; }
        .experience-item, .education-item { margin-bottom: 0.75rem; }
        .experience-item h3, .education-item h3 { font-size: 1rem; margin: 0 0 0.2rem 0; font-weight: 600; }
        .meta { font-size: 0.9rem; color: #555; margin-bottom: 0.25rem; }
        .ats-highlights-section { margin-bottom: 0.75rem; }
        .ats-highlights-section:last-child { margin-bottom: 0; }
        .ats-highlights-items { margin-top: 0.25rem; }
        .ats-highlights-items span { display: inline-block; margin-right: 0.5rem; margin-bottom: 0.25rem; }
        @media print {
            body { padding: 0; }
            .contact a { color: #1a1a1a; }
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $resume['name'] ?? '' }}</h1>
        @if(!empty($resume['contact']))
        <div class="contact">
            @foreach($resume['contact'] as $item)
            <span class="contact-item">
                @if(is_array($item) && isset($item['url']))
                    <a href="{{ e($item['url']) }}" target="_blank" rel="noopener noreferrer">{{ e($item['url']) }}</a>
                @else
                    {{ e(is_array($item) ? ($item['label'] ?? '') : $item) }}
                @endif
            </span>
            @endforeach
        </div>
        @endif
    </header>

    @if(!empty($resume['summary']))
    <section>
        <h2>Summary</h2>
        <p class="summary">{!! \Illuminate\Support\Str::markdown($resume['summary']) !!}</p>
    </section>
    @endif

    @if(!empty($resume['experience']))
    <section>
        <h2>Experience</h2>
        @foreach($resume['experience'] as $job)
        <div class="experience-item">
            <h3>{{ $job['title'] ?? '' }}</h3>
            <div class="meta">{{ $job['company'] ?? '' }} @if(!empty($job['dates'])) &mdash; {{ $job['dates'] }} @endif</div>
            <ul>
                @foreach($job['bullets'] ?? [] as $line)
                    <li>{!! \Illuminate\Support\Str::markdown($line) !!}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </section>
    @endif

    @if(!empty($resume['education']))
    <section>
        <h2>Education</h2>
        @foreach($resume['education'] as $edu)
        <div class="education-item">
            <h3>{{ $edu['degree'] ?? '' }}</h3>
            <div class="meta">{{ $edu['institution'] ?? '' }} @if(!empty($edu['dates'])) &mdash; {{ $edu['dates'] }} @endif</div>
        </div>
        @endforeach
    </section>
    @endif

    @if(!empty($resume['ats_highlights']))
    <section>
        @foreach($resume['ats_highlights'] as $section)
        <div class="ats-highlights-section">
            <h2>{{ $section['heading'] ?? 'Highlights' }}</h2>
            <div class="ats-highlights-items">
                @foreach($section['items'] ?? [] as $item)
                <span>{{ $item }}</span>@if(!$loop->last) · @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </section>
    @endif

</body>
</html>
