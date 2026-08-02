<div class="mt-6 flex flex-col gap-4 rounded-2xl border border-white/10 bg-white/5 p-5 sm:flex-row sm:items-end" data-repository-filters>
    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <label for="filter_category" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Filter by category</label>
        <select id="filter_category" data-filter-key="category" class="rounded-lg border border-white/15 bg-slate-950/50 px-3 py-2 text-sm text-white outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}">{{ $category }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <label for="filter_severity" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Filter by severity</label>
        <select id="filter_severity" data-filter-key="severity" class="rounded-lg border border-white/15 bg-slate-950/50 px-3 py-2 text-sm text-white outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30">
            <option value="">All severities</option>
            @foreach ($severities as $severity)
                <option value="{{ $severity }}">{{ ucfirst($severity) }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
        <label for="filter_status" class="text-xs font-semibold uppercase tracking-wider text-slate-400">Filter by status</label>
        <select id="filter_status" data-filter-key="status" class="rounded-lg border border-white/15 bg-slate-950/50 px-3 py-2 text-sm text-white outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <button type="button" data-filter-clear class="inline-flex justify-center rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
        Clear filters
    </button>

    <p data-filter-status role="status" aria-live="polite" class="min-h-5 text-sm text-slate-400"></p>
</div>
