@extends('layouts.app')

@section('title', $repository->fullName)

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24 lg:px-8" @if(isset($analysis)) data-analysis-id="{{ $analysis->id }}" @endif>

        {{-- ═══════════════ HEADER —╗ --}}
        <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-indigo-200">{{ __('GitHub repository') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl">{{ $repository->fullName }}</h1>
                <p class="mt-5 text-lg leading-8 text-slate-300">{{ $repository->description ?? __('No repository description is available.') }}</p>
                @php
                    $scheme = is_string($repository->url) ? parse_url($repository->url, PHP_URL_SCHEME) : null;
                    $urlIsHttps = $scheme === 'https';
                @endphp
                @if ($urlIsHttps)
                    <a href="{{ $repository->url }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-200 underline decoration-indigo-300/40 underline-offset-4 transition hover:text-white" rel="noopener noreferrer" target="_blank">
                        {{ __('View on GitHub') }}
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </a>
                @else
                    <span class="mt-6 inline-flex text-sm font-semibold text-slate-400">{{ $repository->url }}</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-xl border border-indigo-300/20 bg-indigo-400/10 px-4 py-3 text-sm text-indigo-100">
                    {{ __('Metadata retrieved from GitHub') }}
                </div>
                <a href="{{ route('home') }}" class="inline-flex rounded-xl border border-white/15 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                    {{ __('Analyze another repository') }}
                </a>
            </div>
        </div>

        {{-- ═══════════ RING GAUGE + CATEGORY SCORES —╗ --}}
        <div class="mt-10 rounded-2xl border border-indigo-400/20 bg-gradient-to-br from-indigo-950/40 via-slate-900/60 to-slate-950/80 p-6 sm:p-8 backdrop-blur-sm">
            <div class="flex flex-col gap-8 md:flex-row md:items-center md:justify-between">

                {{-- ··· Ring Gauge ··· --}}
                @php
                    $score = $report->finalScore;
                    $label = match(true) {
                        $score >= 90 => 'Excellent',
                        $score >= 80 => 'Good',
                        $score >= 70 => 'Fair',
                        $score >= 60 => 'Needs Improvement',
                        default => 'Poor',
                    };
                    $ringColor = match(true) {
                        $score >= 80 => '#34d399',
                        $score >= 60 => '#fbbf24',
                        default => '#fb7185',
                    };
                    // circumference = 2*pi*r = 2*3.14159*56 ≈ 351.858
                    $radius = 56;
                    $circumference = 2 * 3.1415926535 * $radius;
                    $offset = $circumference - ($score / 100) * $circumference;
                @endphp
                <div class="flex shrink-0 flex-col items-center gap-4" data-ring-container>
                    <p class="text-base sm:text-lg font-semibold uppercase tracking-wider text-indigo-200">{{ __('Overall Repository Health Score') }}</p>
                    <p class="-mt-1 text-sm text-slate-400">{{ __('Deterministic score') }}</p>
                    <div class="relative size-40 sm:size-48" data-ring-svg>
                        <svg class="size-full -rotate-90" viewBox="0 0 128 128" aria-hidden="true">
                            {{-- track --}}
                            <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="rgb(51 65 85 / 0.5)" stroke-width="12" />
                            {{-- active arc --}}
                            <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="12"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $circumference }}"
                                data-ring-arc
                                style="color:{{ $ringColor }}; --ring-offset:{{ $offset }}; --ring-circumference:{{ $circumference }};" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-extrabold text-white sm:text-6xl" data-ring-score>{{ $score }}</span>
                            <span class="-mt-1 text-sm font-medium text-slate-400">/ 100</span>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-4 py-1.5 text-base font-semibold {{ $score >= 80 ? 'border-emerald-400/30 bg-emerald-400/15 text-emerald-300' : ($score >= 60 ? 'border-amber-400/30 bg-amber-400/15 text-amber-300' : 'border-rose-400/30 bg-rose-400/15 text-rose-300') }}">
                        {{ __($label) }}
                    </span>
                    <div class="flex gap-5 text-sm text-slate-400">
                        <span class="text-emerald-300">{{ $report->summary['pass'] ?? 0 }} {{ __('passed') }}</span>
                        <span class="text-amber-300">{{ $report->summary['improvement'] ?? 0 }} {{ __('improvement') }}</span>
                        <span class="text-slate-400">{{ $report->summary['unknown'] ?? 0 }} {{ __('unknown') }}</span>
                    </div>
                </div>

                {{-- ··· Category Scores ··· --}}
                <div class="flex-1 md:pl-4">
                    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-200">{{ __('Category Scores') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Deterministic — authoritative</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($report->categoryScores as $categoryName => $catScore)
                            @php
                                $displayScore = min(100, max(0, (int) $catScore));
                                $catLabel = match(true) {
                                    $displayScore >= 80 => 'color-emerald',
                                    $displayScore >= 60 => 'color-amber',
                                    default => 'color-rose',
                                };
                            @endphp
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4 transition hover:border-white/20 hover:bg-white/[0.07]">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-white truncate">{{ $categoryName }}</span>
                                    <span class="shrink-0 text-lg font-bold {{ $catLabel === 'color-emerald' ? 'text-emerald-300' : ($catLabel === 'color-amber' ? 'text-amber-300' : 'text-rose-300') }}">{{ $displayScore }}%</span>
                                </div>
                                <div class="mt-3 h-3 w-full rounded-full bg-slate-800/60" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $displayScore }}" aria-label="{{ $categoryName }} score {{ $displayScore }} out of 100">
                                    <div class="h-3 rounded-full transition-all duration-700 ease-out {{ $catLabel === 'color-emerald' ? 'bg-emerald-400' : ($catLabel === 'color-amber' ? 'bg-amber-400' : 'bg-rose-400') }}" style="width: {{ max($displayScore, 4) }}%;" data-category-bar></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ REPOSITORY SUMMARY —╗ --}}
        <div class="mt-10 rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-7">
            <div class="flex flex-wrap items-center gap-x-8 gap-y-4 text-sm sm:text-base">
                {{-- owner --}}
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2Z"/></svg>
                    <span class="text-white font-medium">{{ $repository->owner }}</span>
                </span>
                {{-- language --}}
                @if ($repository->primaryLanguage)
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 21 5-18 5 18"/><path d="M5 15h14"/></svg>
                    <span class="text-white font-medium">{{ $repository->primaryLanguage }}</span>
                </span>
                @endif
                {{-- stars --}}
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span class="text-white font-medium">{{ number_format($repository->starsCount) }}</span>
                </span>
                {{-- forks --}}
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><line x1="8.7" y1="8.7" x2="15.3" y2="14.3"/></svg>
                    <span class="text-white font-medium">{{ number_format($repository->forksCount) }}</span>
                </span>
                {{-- license --}}
                @if ($repository->licenseName)
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span class="text-white font-medium">{{ $repository->licenseName }}</span>
                </span>
                @endif
                {{-- updated_at --}}
                @if ($repository->pushedAt)
                <span class="inline-flex items-center gap-2 text-slate-400">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="text-white font-medium">{{ $repository->pushedAt->diffForHumans() }}</span>
                </span>
                @endif
                {{-- archived / fork badge --}}
                @if ($repository->isArchived)
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-400/15 px-2.5 py-0.5 text-xs font-medium text-amber-200">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                        Archived
                    </span>
                @endif
                @if ($repository->isFork)
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-400/15 px-2.5 py-0.5 text-xs font-medium text-slate-200">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><line x1="8.7" y1="8.7" x2="15.3" y2="14.3"/></svg>
                        Fork
                    </span>
                @endif
            </div>
            {{-- topics --}}
                        @if (count($repository->topics))
                            <div class="mt-5 flex flex-wrap gap-2 border-t border-white/10 pt-5">
                                @foreach ($repository->topics as $topic)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-400/15 px-3 py-1 text-sm font-medium text-indigo-200">#{{ $topic }}</span>
                                @endforeach
                           </div>
                        @endif
        </div>

        {{-- ═══════════ PRIORITY ACTIONS —╗ --}}
        @php
            $priorityActions = collect($findings)
                ->filter(fn ($finding) => $finding->status->value === 'improvement' && $finding->recommendation)
                ->sortBy(fn ($finding) => match ($finding->severity->value) {
                    'high' => 0,
                    'medium' => 1,
                    'low' => 2,
                    default => 3,
                })
                ->values();
        @endphp

        @if ($priorityActions->isNotEmpty())
            <section class="mt-14" data-priority-actions>
                <p class="text-sm font-semibold text-indigo-200">Action plan</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Start with these deterministic improvements</h2>
                <p class="mt-2 text-sm text-slate-400">Ordered by severity from the deterministic checks above.</p>
                <div class="mt-6 space-y-4">
                    @foreach ($priorityActions as $index => $finding)
                        @php
                            $sevColor = match($finding->severity->value) {
                                'high' => 'bg-rose-400/15 border-rose-400/30 text-rose-200',
                                'medium' => 'bg-amber-400/15 border-amber-400/30 text-amber-200',
                                'low' => 'bg-sky-400/15 border-sky-400/30 text-sky-200',
                                default => 'bg-slate-400/15 border-slate-400/30 text-slate-200',
                            };
                            $sevBadge = match($finding->severity->value) {
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low',
                                default => 'Info',
                            };
                            $sevBar = match($finding->severity->value) {
                                'high' => 'bg-rose-400 shadow-[0_0_6px_1px_rgba(251,113,133,0.3)]',
                                'medium' => 'bg-amber-400 shadow-[0_0_6px_1px_rgba(251,191,36,0.3)]',
                                'low' => 'bg-sky-400 shadow-[0_0_6px_1px_rgba(56,189,248,0.3)]',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <div class="relative flex gap-5 rounded-2xl border border-white/10 bg-white/5 p-5 pl-6 transition hover:border-white/20 hover:bg-white/[0.07]">
                            {{-- severity bar --}}
                            <span class="absolute left-0 top-3 bottom-3 w-1.5 rounded-r-full {{ $sevBar }}" aria-hidden="true"></span>
                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full bg-indigo-400/15 text-sm font-bold text-indigo-200" aria-hidden="true">{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <p class="text-base font-semibold text-white">{{ $finding->title }}</p>
                                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $sevColor }}">{{ $sevBadge }}</span>
                                </div>
                                <p class="mt-1.5 text-sm leading-6 text-indigo-200/80">{{ $finding->recommendation }}</p>
                                <p class="mt-2 text-xs text-slate-500">
                                    <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-indigo-300">{{ $finding->ruleIdentifier }}</code>
                                    &middot; {{ $finding->category->value }}
                                    &middot; Source: deterministic check
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ═══════════ DETERMINISTIC FINDINGS —╗ --}}
        @php
            $categories = collect($findings)->map(fn ($finding) => $finding->category->value)->unique()->sort()->values();
            $severities = collect($findings)->map(fn ($finding) => $finding->severity->value)->unique()->values();
            $statuses = collect($findings)->map(fn ($finding) => $finding->status->value)->unique()->sort()->values();
        @endphp

        <section class="mt-14">
            <p class="text-sm font-semibold text-indigo-200">Deterministic checks</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Repository checks</h2>
            <p class="mt-2 text-sm text-slate-400">File and configuration signals only. No numeric score or source-code execution.</p>

            <x-repositories.finding-filters :categories="$categories" :severities="$severities" :statuses="$statuses" />

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                @foreach ($findings as $finding)
                    @php
                        $sevColor = match($finding->severity->value) {
                            'high' => 'border-rose-400/30 bg-rose-400/15 text-rose-200',
                            'medium' => 'border-amber-400/30 bg-amber-400/15 text-amber-200',
                            'low' => 'border-sky-400/30 bg-sky-400/15 text-sky-200',
                            default => 'border-slate-400/30 bg-slate-400/15 text-slate-200',
                        };
                        $sevBadge = match($finding->severity->value) {
                            'high' => 'High',
                            'medium' => 'Medium',
                            'low' => 'Low',
                            default => 'Info',
                        };
                    @endphp
                    <article
                        data-filter-target="finding"
                        data-category="{{ $finding->category->value }}"
                        data-severity="{{ $finding->severity->value }}"
                        data-status="{{ $finding->status->value }}"
                        class="rounded-2xl border border-white/10 bg-white/5 p-6 transition hover:border-white/20 hover:bg-white/[0.07]"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $sevColor }}">{{ $sevBadge }}</span>
                            <span class="rounded-full border border-white/15 px-2.5 py-0.5 text-xs text-slate-300">{{ $finding->category->value }}</span>
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $finding->status->value === 'pass' ? 'text-emerald-300' : ($finding->status->value === 'improvement' ? 'text-amber-300' : 'text-slate-400') }}">{{ $finding->status->value }}</span>
                            <button type="button"
                                data-copy-rule-id="{{ $finding->ruleIdentifier }}"
                                class="ml-auto font-mono text-xs text-slate-500 underline decoration-white/20 underline-offset-2 transition hover:text-indigo-300 hover:decoration-indigo-300/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/30 rounded"
                                aria-label="Copy rule ID {{ $finding->ruleIdentifier }}"
                            >{{ $finding->ruleIdentifier }}</button>
                        </div>
                        <h3 class="mt-3 font-medium text-white">{{ $finding->title }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-300">{{ $finding->message }}</p>
                        @if ($finding->evidence)<p class="mt-2 text-xs text-slate-400">Evidence: {{ $finding->evidence }}</p>@endif
                        @if ($finding->recommendation)<p class="mt-2 text-xs text-indigo-200/80">Recommendation: {{ $finding->recommendation }}</p>@endif
                        <p class="mt-3 text-xs text-slate-500">Source: deterministic check</p>
                    </article>
                @endforeach
            </div>

            <p data-finding-empty hidden aria-live="polite" class="mt-6 flex items-center justify-center gap-2 rounded-2xl border border-dashed border-white/10 bg-white/[0.03] p-8 text-sm text-slate-400">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                No matching repository checks for the selected filters.
            </p>
        </section>

        {{-- ═══════════ AI QUALITATIVE REVIEW —╗ --}}
        <section class="mt-14">
            <div class="flex items-center gap-3">
                <p class="text-sm font-semibold text-indigo-200">{{ $aiReview->sourceLabel }}</p>
                @if ($aiReview->notice)
                    <span class="rounded-full border border-amber-400/30 bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-200">{{ $aiReview->notice }}</span>
                @endif
            </div>
            <h2 class="mt-2 text-2xl font-semibold text-white">Qualitative review</h2>
            <p class="mt-2 text-sm text-slate-400">Optional AI enrichment. {{ __('Deterministic score') }}s and findings remain authoritative.</p>

            @php
                $aiSections = collect($aiReview->sections)
                    ->sortBy(fn (array $section) => $section['title'] === 'Prioritized Recommendations' ? 0 : 1)
                    ->values();
            @endphp

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @foreach ($aiSections as $section)
                    <div @class([
                        'rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6',
                        'lg:col-span-2 border-indigo-300/20 bg-indigo-400/10' => $section['title'] === 'Prioritized Recommendations',
                    ])>
                        {{-- section header --}}
                        <h3 class="text-lg font-semibold text-white">
                            {{ $section['title'] }}
                        </h3>

                        @if ($section['title'] === 'Prioritized Recommendations')
                            {{-- Per-entry with severity badge + rule chip + recommendation --}}
                            <div class="mt-4 space-y-4">
                                @foreach ($section['entries'] as $entry)
                                    @php
                                        preg_match('/^\[([A-Z]+-[A-Z]+-\d+)\]\s+(.*)/s', $entry, $m);
                                        $ruleId = $m[1] ?? null;
                                        $body = $m[2] ?? $entry;
                                        // Match severity from deterministic finding per entry
                                        $matchedFinding = $ruleId ? collect($findings)->firstWhere('ruleIdentifier', $ruleId) : null;
                                        $aiSev = $matchedFinding?->severity?->value ?? 'info';
                                        $aiSevColor = match($aiSev) {
                                            'high' => 'border-rose-400/30 bg-rose-400/15 text-rose-200',
                                            'medium' => 'border-amber-400/30 bg-amber-400/15 text-amber-200',
                                            'low' => 'border-sky-400/30 bg-sky-400/15 text-sky-200',
                                            default => 'border-slate-400/30 bg-slate-400/15 text-slate-200',
                                        };
                                        $aiSevBadge = match($aiSev) {
                                            'high' => 'High',
                                            'medium' => 'Medium',
                                            'low' => 'Low',
                                            default => 'Info',
                                        };
                                    @endphp
                                    <div class="rounded-xl border border-white/10 bg-white/[0.04] p-4 transition hover:bg-white/[0.07]">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($ruleId)
                                                <button type="button"
                                                    data-copy-rule-id="{{ $ruleId }}"
                                                    class="inline-flex items-center gap-1 rounded-md bg-slate-800 px-2 py-1 font-mono text-xs text-indigo-300 transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/30"
                                                    aria-label="Copy rule ID {{ $ruleId }}"
                                                >
                                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                    {{ $ruleId }}
                                                </button>
                                            @endif
                                            <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $aiSevColor }}">{{ $aiSevBadge }}</span>
                                        </div>
                                        <p class="mt-2.5 text-sm leading-6 text-slate-200">{{ $body }}</p>
                                        @if ($ruleId && $matchedFinding?->recommendation)
                                            <div class="mt-2 flex items-start gap-1.5 rounded-lg bg-indigo-400/5 px-3 py-2 text-xs text-indigo-200/80">
                                                <svg class="mt-0.5 size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h1m8-9v1m0 16v1m8-9h1M5.6 5.6l.7.7m12.1-.7-.7.7"/><circle cx="12" cy="12" r="3"/></svg>
                                                <span>{{ $matchedFinding->recommendation }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Regular section: prose list with rule chips --}}
                            <ul class="mt-4 space-y-3">
                                @foreach ($section['entries'] as $entry)
                                    @php
                                        preg_match('/^\[([A-Z]+-[A-Z]+-\d+)\]\s+(.*)/s', $entry, $m);
                                        $ruleId = $m[1] ?? null;
                                        $body = $m[2] ?? $entry;
                                    @endphp
                                    <li class="flex gap-3 text-sm leading-6 text-slate-300">
                                        @if ($ruleId)
                                            <span class="shrink-0 mt-0.5">
                                                <button type="button"
                                                    data-copy-rule-id="{{ $ruleId }}"
                                                    class="inline-flex items-center gap-1 rounded-md bg-slate-800 px-1.5 py-0.5 font-mono text-[11px] text-indigo-300 transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/30"
                                                    aria-label="Copy rule ID {{ $ruleId }}"
                                                >
                                                    <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                    {{ $ruleId }}
                                                </button>
                                            </span>
                                        @endif
                                        <span>{{ $body }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </section>
@endsection