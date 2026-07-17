@php
    $sidebarFooterOptions = \App\Support\RolePanelDirectory::optionsForUser(auth()->user());
    $sidebarFooterIsImpersonating = function_exists('is_impersonating') && is_impersonating();
@endphp

<div class="fi-sidebar-footer shrink-0 space-y-1 border-t border-gray-950/5 px-3 py-3 dark:border-white/10">
    @if ($sidebarFooterIsImpersonating)
        <a
            href="{{ route('impersonate.leave') }}"
            class="flex items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-semibold text-gray-700 outline-none transition duration-75 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
        >
            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
            Kembali ke Admin
        </a>
    @endif

    @if (count($sidebarFooterOptions) > 1)
        <a
            href="{{ route('role.gate') }}"
            class="flex items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-semibold text-gray-700 outline-none transition duration-75 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
        >
            <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
            Ganti Peran
        </a>
    @endif

    <button
        type="button"
        x-data
        x-on:click="$dispatch('open-modal', { id: 'sidebar-logout-modal' })"
        class="flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-semibold text-danger-600 outline-none transition duration-75 hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-500/10"
    >
        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="h-5 w-5" />
        Keluar
    </button>
</div>

{{-- The sidebar is translated on/off screen via CSS transforms, which creates a new
     containing block for any `position: fixed` descendant — without teleporting, the
     modal overlay would be clipped to the sidebar's box instead of covering the page. --}}
@teleport('body')
    <x-filament::modal id="sidebar-logout-modal" icon="heroicon-o-arrow-right-on-rectangle" icon-color="danger" width="sm">
        <x-slot name="heading">
            Keluar dari aplikasi?
        </x-slot>

        <x-slot name="description">
            Anda perlu masuk kembali untuk mengakses portal ini.
        </x-slot>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="close">
                Batal
            </x-filament::button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-filament::button type="submit" color="danger">
                    Ya, Keluar
                </x-filament::button>
            </form>
        </x-slot>
    </x-filament::modal>
@endteleport
