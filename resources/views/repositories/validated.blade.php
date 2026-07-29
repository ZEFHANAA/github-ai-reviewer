@extends('layouts.app')

@section('title', 'Repository URL validated')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-20 sm:py-28 lg:px-8">
        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 p-8 sm:p-10">
            <p class="text-sm font-semibold text-emerald-200">Repository URL validated</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                {{ $repository->owner }}/{{ $repository->name }}
            </h1>
            <p class="mt-5 text-base leading-7 text-slate-300">
                The repository identity was parsed successfully. GitHub data has not been requested yet.
            </p>

            <dl class="mt-8 space-y-4 rounded-xl border border-white/10 bg-slate-950/30 p-5 text-sm">
                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6">
                    <dt class="text-slate-400">Owner</dt>
                    <dd class="font-medium text-white">{{ $repository->owner }}</dd>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6">
                    <dt class="text-slate-400">Repository</dt>
                    <dd class="font-medium text-white">{{ $repository->name }}</dd>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6">
                    <dt class="text-slate-400">Canonical URL</dt>
                    <dd class="break-all font-medium text-indigo-200">{{ $repository->canonicalUrl }}</dd>
                </div>
            </dl>

            <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                Validate another repository
            </a>
        </div>
    </section>
@endsection
