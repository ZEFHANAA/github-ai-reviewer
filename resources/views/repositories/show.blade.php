@extends('layouts.app')

@section('title', $repository->fullName)

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24 lg:px-8">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-indigo-200">GitHub repository</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-5xl">{{ $repository->fullName }}</h1>
                <p class="mt-5 text-lg leading-8 text-slate-300">{{ $repository->description ?? 'No repository description is available.' }}</p>
                <a href="{{ $repository->url }}" class="mt-6 inline-flex text-sm font-semibold text-indigo-200 underline decoration-indigo-300/40 underline-offset-4 hover:text-white" rel="noopener noreferrer" target="_blank">
                    View on GitHub
                </a>
            </div>

            <div class="rounded-xl border border-indigo-300/20 bg-indigo-400/10 px-4 py-3 text-sm text-indigo-100">
                Metadata retrieved from GitHub
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-indigo-400/20 bg-gradient-to-br from-indigo-950/40 via-slate-900/60 to-slate-950/80 p-6 sm:p-8 backdrop-blur-sm">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wider text-indigo-300">Overall Repository Health Score</p>
                    <div class="mt-2 flex items-baseline gap-3">
                        <span class="text-5xl font-extrabold text-white sm:text-6xl">{{ $report->finalScore }}</span>
                        <span class="text-xl text-slate-400 font-medium">/ 100</span>
                        @php
                            $score = $report->finalScore;
                            $label = match(true) {
                                $score >= 90 => 'Excellent',
                                $score >= 80 => 'Good',
                                $score >= 70 => 'Fair',
                                $score >= 60 => 'Needs Improvement',
                                default => 'Poor',
                            };
                            $badgeColor = match(true) {
                                $score >= 80 => 'bg-emerald-400/15 text-emerald-300 border-emerald-400/30',
                                $score >= 60 => 'bg-amber-400/15 text-amber-300 border-amber-400/30',
                                default => 'bg-rose-400/15 text-rose-300 border-rose-400/30',
                            };
                        @endphp
                        <span class="ml-2 inline-flex items-center rounded-full border px-3 py-1 text-sm font-semibold {{ $badgeColor }}">
                            {{ $label }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 text-sm text-slate-300">
                    <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center">
                        <span class="block text-xl font-bold text-emerald-400">{{ $report->summary['pass'] ?? 0 }}</span>
                        <span class="text-xs text-slate-400">Passed</span>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center">
                        <span class="block text-xl font-bold text-amber-400">{{ $report->summary['improvement'] ?? 0 }}</span>
                        <span class="text-xs text-slate-400">Improvements</span>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center">
                        <span class="block text-xl font-bold text-indigo-300">{{ $report->summary['unknown'] ?? 0 }}</span>
                        <span class="text-xs text-slate-400">Unknown</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-white/10 pt-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-indigo-200">Category Scores</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($report->categoryScores as $categoryName => $catScore)
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-center justify-between text-sm font-medium">
                                <span class="text-white">{{ $categoryName }}</span>
                                <span class="font-bold text-indigo-200">{{ $catScore }}%</span>
                            </div>
                            <div class="mt-2 h-2.5 w-full rounded-full bg-slate-800">
                                <div class="h-2.5 rounded-full {{ $catScore >= 80 ? 'bg-emerald-400' : ($catScore >= 60 ? 'bg-amber-400' : 'bg-rose-400') }}" style="width: {{ max($catScore, 4) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-repositories.stat label="Stars" :value="number_format($repository->starsCount)" />
            <x-repositories.stat label="Forks" :value="number_format($repository->forksCount)" />
            <x-repositories.stat label="Open issues" :value="number_format($repository->openIssuesCount)" />
            <x-repositories.stat label="Size" :value="number_format($repository->sizeKilobytes).' KB'" />
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            <dl class="divide-y divide-white/10 rounded-2xl border border-white/10 bg-white/5 px-6">
                <x-repositories.detail label="Owner" :value="$repository->owner" />
                <x-repositories.detail label="Default branch" :value="$repository->defaultBranch ?? 'Not available'" />
                <x-repositories.detail label="Primary language" :value="$repository->primaryLanguage ?? 'Not available'" />
                <x-repositories.detail label="Visibility" :value="$repository->visibility ?? 'Not available'" />
                <x-repositories.detail label="License" :value="$repository->licenseName ?? 'Not available'" />
            </dl>

            <dl class="divide-y divide-white/10 rounded-2xl border border-white/10 bg-white/5 px-6">
                <x-repositories.detail label="Watchers" :value="number_format($repository->watchersCount)" />
                <x-repositories.detail label="Subscribers" :value="$repository->subscribersCount === null ? 'Not available' : number_format($repository->subscribersCount)" />
                <x-repositories.detail label="Created" :value="$repository->createdAt?->toFormattedDateString() ?? 'Not available'" />
                <x-repositories.detail label="Updated" :value="$repository->updatedAt?->toFormattedDateString() ?? 'Not available'" />
                <x-repositories.detail label="Last pushed" :value="$repository->pushedAt?->toFormattedDateString() ?? 'Not available'" />
            </dl>
        </div>

        <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-6">
            <div class="flex flex-wrap gap-3 text-sm">
                @if ($repository->isArchived)
                    <span class="rounded-full bg-amber-400/15 px-3 py-1 text-amber-200">Archived</span>
                @endif
                @if ($repository->isFork)
                    <span class="rounded-full bg-slate-400/15 px-3 py-1 text-slate-200">Fork</span>
                @endif
                @forelse ($repository->topics as $topic)
                    <span class="rounded-full bg-indigo-400/15 px-3 py-1 text-indigo-200">{{ $topic }}</span>
                @empty
                    <span class="text-slate-400">No topics are available.</span>
                @endforelse
            </div>
        </div>

        <section class="mt-12">
            <p class="text-sm font-semibold text-indigo-200">Deterministic checks</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Repository checks</h2>
            <p class="mt-2 text-sm text-slate-400">These are file and configuration signals only. No numeric score or source-code execution is used.</p>
            @php($groupedFindings = collect($findings)->groupBy('category'))
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @foreach ($groupedFindings as $category => $categoryFindings)
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="font-semibold text-white">{{ $category }}</h3>
                        <div class="mt-4 space-y-4">
                            @foreach ($categoryFindings as $finding)
                                <article class="border-l-2 pl-4 {{ $finding->status->value === 'warning' || $finding->status->value === 'improvement' ? 'border-amber-300' : ($finding->status->value === 'pass' ? 'border-emerald-300' : 'border-indigo-300') }}">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $finding->status->value }}</p>
                                    <h4 class="mt-1 font-medium text-white">{{ $finding->title }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-slate-300">{{ $finding->message }}</p>
                                    @if ($finding->evidence)<p class="mt-2 text-xs text-slate-400">Evidence: {{ $finding->evidence }}</p>@endif
                                    @if ($finding->recommendation)<p class="mt-2 text-xs text-indigo-200">Recommendation: {{ $finding->recommendation }}</p>@endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <div class="flex items-center gap-3">
                <p class="text-sm font-semibold text-indigo-200">{{ $aiReview->sourceLabel }}</p>
                @if ($aiReview->notice)
                    <span class="rounded-full border border-amber-400/30 bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-200">{{ $aiReview->notice }}</span>
                @endif
            </div>
            <h2 class="mt-2 text-2xl font-semibold text-white">Qualitative review</h2>
            <p class="mt-2 text-sm text-slate-400">Optional AI enrichment. Deterministic scores and findings above remain authoritative and unchanged.</p>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @foreach ($aiReview->sections as $section)
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <h3 class="font-semibold text-white">{{ $section['title'] }}</h3>
                        <ul class="mt-3 space-y-3">
                            @foreach ($section['entries'] as $entry)
                                <li class="text-sm leading-6 text-slate-300">{{ $entry }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    </section>
@endsection
