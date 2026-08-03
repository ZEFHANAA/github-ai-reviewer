@extends('layouts.error')

@section('title', 'Server error')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-20 sm:py-28 lg:px-8">
        <div class="rounded-2xl border border-rose-300/20 bg-rose-400/10 p-8 sm:p-10">
            <p class="text-sm font-semibold text-rose-200">500</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Something went wrong</h1>
            <p class="mt-5 text-base leading-7 text-slate-300">An unexpected error occurred while processing your request. The team has been notified. Please try again in a moment.</p>
            <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                Back to repository review
            </a>
        </div>
    </section>
@endsection
