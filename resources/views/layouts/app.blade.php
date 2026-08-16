<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta
            name="description"
            content="@yield('meta_description', 'An explainable health reviewer for public GitHub repositories.')"
        >

        <title>@hasSection('title')@yield('title') ?? @endif{{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen">
        <div class="relative isolate flex min-h-screen flex-col overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-500/25 via-slate-950 to-slate-950"></div>

            <header class="border-b border-white/10">
                <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5 lg:px-8" aria-label="Main navigation">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 font-semibold tracking-tight text-white">
                        <span class="grid size-9 place-items-center rounded-xl bg-indigo-500 shadow-lg shadow-indigo-500/25" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current stroke-2" aria-hidden="true">
                                <path d="M12 3 3.5 7.5 12 12l8.5-4.5L12 3Z" />
                                <path d="m3.5 12 8.5 4.5 8.5-4.5M3.5 16.5 12 21l8.5-4.5" />
                            </svg>
                        </span>
                        <span>{{ config('app.name') }}</span>
                    </a>

                    <div class="flex items-center gap-3">
                        <!-- Language Switcher Toggle -->
                        <div class="flex items-center rounded-full border border-white/15 bg-slate-900/80 p-0.5 text-xs shadow-inner">
                            <a
                                href="{{ route('lang.switch', 'id') }}"
                                class="flex items-center gap-1 rounded-full px-2.5 py-1 font-medium transition {{ app()->getLocale() === 'id' ? 'bg-indigo-600 text-white shadow-sm font-semibold' : 'text-slate-400 hover:text-white' }}"
                                title="Bahasa Indonesia"
                            >
                                <span>????????</span>
                                <span>ID</span>
                            </a>
                            <a
                                href="{{ route('lang.switch', 'en') }}"
                                class="flex items-center gap-1 rounded-full px-2.5 py-1 font-medium transition {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white shadow-sm font-semibold' : 'text-slate-400 hover:text-white' }}"
                                title="English"
                            >
                                <span>????????</span>
                                <span>EN</span>
                            </a>
                        </div>

                        <span class="hidden rounded-full border border-indigo-300/20 bg-indigo-400/10 px-3 py-1 text-xs font-medium text-indigo-200 sm:inline-block">
                            {{ __('Public repositories') }}
                        </span>
                    </div>
                </nav>
            </header>

            <main class="flex-1">
                @yield('content')
            </main>

            <footer class="border-t border-white/10">
                <div class="mx-auto flex max-w-6xl flex-col gap-2 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                    <p>{{ __('Explainable repository-health guidance for public GitHub projects.') }}</p>
                    <p>{{ __('AI-assisted, not AI-dependent.') }}</p>
                </div>
            </footer>
        </div>
    </body>
</html>
