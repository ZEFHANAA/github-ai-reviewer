@extends('layouts.error')

@section('title', 'Under maintenance')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-20 sm:py-28 lg:px-8">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-8 sm:p-10">
            <p class="text-sm font-semibold text-indigo-200">503</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Under maintenance</h1>
            <p class="mt-5 text-base leading-7 text-slate-300">The application is undergoing maintenance. Please check back shortly.</p>
            <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                Back to repository review
            </a>
        </div>
    </section>
@endsection
