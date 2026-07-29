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
    </section>
@endsection
