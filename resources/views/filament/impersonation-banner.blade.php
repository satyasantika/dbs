@if (is_impersonating())
    <div class="flex items-center gap-3 rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-800 ring-1 ring-warning-200 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-500/40">
        <span>
            Anda masuk sebagai <strong>{{ auth()->user()->name }}</strong>
        </span>
        <a
            href="{{ route('impersonate.leave') }}"
            class="font-semibold underline hover:no-underline"
        >
            Kembali ke Admin
        </a>
    </div>
@endif
