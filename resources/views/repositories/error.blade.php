@extends('layouts.app')

@section('title', 'Repository unavailable')

@php
    $status = $status ?? null;
    $submittedUrl = $submittedUrl ?? null;

    // Recovery guidance is derived from the failure status so every dead end
    // tells the user what to do next. Deterministic analysis is unaffected.
    $guidance = match ($status) {
        404 => 'Check that the repository exists and is public, then confirm the URL looks like https://github.com/owner/repository.',
        429 => 'Wait a minute before requesting another analysis, then try the same repository again.',
        503 => 'This is a GitHub availability issue, not a problem with your repository. Retrying in a few moments usually works.',
        default => 'Retry the analysis, or submit a different public repository URL.',
    };
@endphp

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-20 sm:py-28 lg:px-8">
        <div class="rounded-2xl border border-rose-300/20 bg-rose-400/10 p-8 sm:p-10">
            <p class="text-sm font-semibold text-rose-200">Repository unavailable</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">We could not retrieve this repository.</h1>
            <p class="mt-5 text-base leading-7 text-slate-300">{{ $message }}</p>
            <p class="mt-3 text-sm leading-6 text-rose-100/90">{{ $guidance }}</p>

            <form action="{{ route('repositories.submit') }}" method="POST" class="mt-8" data-analyze-form>
                @csrf

                <label for="repository_url_retry" class="text-sm font-medium text-white">GitHub repository URL</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="repository_url_retry"
                        name="repository_url"
                        type="url"
                        value="{{ $submittedUrl }}"
                        placeholder="https://github.com/laravel/laravel"
                        autocomplete="url"
                        required
                        class="min-w-0 flex-1 rounded-lg border border-white/15 bg-slate-950/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30"
                    >
                    <button
                        type="submit"
                        data-analyze-submit
                        data-loading-label="Analyzing…"
                        class="inline-flex justify-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        Try again
                    </button>
                </div>
                <p data-analyze-status role="status" aria-live="polite" class="mt-3 min-h-5 text-sm text-rose-100/80"></p>
            </form>

            <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-lg border border-white/20 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                Analyze a different repository
            </a>
        </div>
    </section>
@endsection
