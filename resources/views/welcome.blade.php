@extends('layouts.app')

@section('title', 'Repository health, explained')

@section('meta_description', 'GitHub AI Reviewer combines explainable checks and AI-assisted guidance for public GitHub repositories.')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-20 sm:py-28 lg:px-8 lg:py-36">
        <div class="max-w-3xl">
            <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-sm font-medium text-emerald-200">
                <span class="size-2 rounded-full bg-emerald-300" aria-hidden="true"></span>
                Deterministic checks. Practical guidance.
            </p>

            <h1 class="text-4xl font-semibold tracking-tight text-balance text-white sm:text-6xl">
                Analyze a public GitHub repository with confidence.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl">
                {{ config('app.name') }} turns bounded repository signals into an explainable health report. Deterministic checks own scores; optional AI only clarifies what to improve next.
            </p>
        </div>

        <div class="mt-14 grid gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <div class="mb-5 grid size-10 place-items-center rounded-lg bg-indigo-400/15 text-indigo-200">01</div>
                <h2 class="text-lg font-semibold text-white">Explainable scores</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Clear category scores supported by identifiable checks and findings.</p>
            </article>

            <article class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <div class="mb-5 grid size-10 place-items-center rounded-lg bg-indigo-400/15 text-indigo-200">02</div>
                <h2 class="text-lg font-semibold text-white">Practical guidance</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Recommendations focused on documentation, testing, security, structure, code quality, and Git practices.</p>
            </article>

            <article class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <div class="mb-5 grid size-10 place-items-center rounded-lg bg-indigo-400/15 text-indigo-200">03</div>
                <h2 class="text-lg font-semibold text-white">Responsible AI</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">AI explains and prioritizes; deterministic checks remain the foundation for scoring.</p>
            </article>
        </div>

        <div class="mt-14 max-w-3xl rounded-2xl border border-indigo-300/15 bg-indigo-400/10 p-6 sm:p-8">
            <h2 class="text-xl font-semibold text-white">Start with a public GitHub repository</h2>
            <p class="mt-2 text-sm leading-6 text-indigo-100">Enter a canonical repository page URL. We will validate it before repository analysis is available.</p>

            <form action="{{ route('repositories.submit') }}" method="POST" class="mt-6" data-analyze-form>
                @csrf

                <label for="repository_url" class="text-sm font-medium text-white">GitHub repository URL</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="repository_url"
                        name="repository_url"
                        type="url"
                        value="{{ old('repository_url') }}"
                        placeholder="https://github.com/laravel/laravel"
                        autocomplete="url"
                        required
                        aria-describedby="repository_url_help @error('repository_url') repository_url_error @enderror"
                        @class([
                            'min-w-0 flex-1 rounded-lg border bg-slate-950/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:ring-2',
                            'border-rose-400 focus:border-rose-400 focus:ring-rose-400/30' => $errors->has('repository_url'),
                            'border-white/15 focus:border-indigo-400 focus:ring-indigo-400/30' => ! $errors->has('repository_url'),
                        ])
                    >
                    <button
                        type="submit"
                        data-analyze-submit
                        data-loading-label="Analyzing…"
                        class="inline-flex justify-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        Analyze Repository
                    </button>
                </div>
                <p id="repository_url_help" class="mt-2 text-sm text-indigo-100/80">Example: https://github.com/laravel/laravel</p>
                <p data-analyze-status role="status" aria-live="polite" class="mt-2 min-h-5 text-sm text-indigo-100/80"></p>
                @error('repository_url')
                    <p id="repository_url_error" class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </section>
@endsection
