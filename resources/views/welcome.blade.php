@extends('layouts.app')

@section('title', 'Repository health, explained')

@section('meta_description', 'GitHub AI Reviewer combines explainable checks and AI-assisted guidance for public GitHub repositories.')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-20 sm:py-28 lg:px-8 lg:py-36">
        <div class="max-w-3xl">
            <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-sm font-medium text-emerald-200">
                <span class="size-2 rounded-full bg-emerald-300"></span>
                Currently in development
            </p>

            <h1 class="text-4xl font-semibold tracking-tight text-balance text-white sm:text-6xl">
                Repository health, made clear.
            </h1>

            <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl">
                {{ config('app.name') }} will help developers understand the strengths and improvement opportunities in public GitHub repositories—through transparent checks and practical, AI-assisted guidance.
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

        <div class="mt-14 rounded-2xl border border-indigo-300/15 bg-indigo-400/10 p-6 text-sm leading-6 text-indigo-100 sm:p-8">
            Repository analysis will be introduced in the next development phase. This landing page is intentionally informational for now.
        </div>
    </section>
@endsection
