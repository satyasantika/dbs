@php
    $sidebarFooterUser = auth()->user();
    $sidebarFooterOptions = \App\Support\RolePanelDirectory::optionsForUser($sidebarFooterUser);
    $sidebarFooterIsImpersonating = function_exists('is_impersonating') && is_impersonating();
    $sidebarFooterHasExtras = $sidebarFooterIsImpersonating || count($sidebarFooterOptions) > 1;

    $sidebarFooterCurrentPanelId = filament()->getId();
    $sidebarFooterRoleLabel = collect($sidebarFooterOptions)->firstWhere('panel', $sidebarFooterCurrentPanelId)['label'] ?? null;
    $sidebarFooterRoleLabel = $sidebarFooterRoleLabel ? \Illuminate\Support\Str::after($sidebarFooterRoleLabel, 'Portal ') : null;

    // Mahasiswa already has a dedicated password page registered as its
    // panel profile page (MahasiswaEditProfile) — reuse it instead of
    // registering a second one. Every other panel gets the generic one.
    $sidebarFooterChangePasswordUrl = $sidebarFooterCurrentPanelId === 'mahasiswa'
        ? filament()->getProfileUrl()
        : \App\Filament\Shared\Pages\ChangePassword::getUrl();
@endphp

<div
    {{-- Marker class only (see filament.shared.custom-styles) — px-3 doesn't
         leave enough room for the Keluar button inside the 3.5rem collapsed
         rail otherwise. --}}
    x-bind:class="$store.sidebar.isOpen ? 'px-3' : 'fi-sidebar-footer-collapsed'"
    class="fi-sidebar-footer shrink-0 border-t border-gray-950/5 py-3 dark:border-white/10"
>
    <div class="flex items-center justify-center gap-x-2">
        {{-- Identity: only shown while the sidebar is expanded. While
             minimized it's hidden here and shown in the topbar instead
             (see filament-panels/components/topbar/index.blade.php),
             since there's no room for name/role next to a bare icon rail. --}}
        <div
            class="min-w-0 flex-1"
            x-show="$store.sidebar.isOpen"
            x-transition:enter="lg:transition lg:delay-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
            <x-filament::dropdown placement="top-start" teleport class="w-full">
                <x-slot name="trigger">
                    <button
                        type="button"
                        class="fi-sidebar-identity-trigger flex w-full items-center gap-x-2 rounded-lg px-2 py-2 text-start outline-none transition duration-75"
                    >
                        <x-filament-panels::avatar.user :user="$sidebarFooterUser" size="md" />

                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-sm font-semibold text-white">
                                {{ filament()->getUserName($sidebarFooterUser) }}
                            </span>

                            @if ($sidebarFooterRoleLabel)
                                <span class="fi-sidebar-identity-role truncate text-xs">
                                    {{ $sidebarFooterRoleLabel }}
                                </span>
                            @endif
                        </span>
                    </button>
                </x-slot>

                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item
                        tag="a"
                        :href="\App\Filament\Shared\Pages\EditProfile::getUrl()"
                        icon="heroicon-o-user-circle"
                    >
                        Edit Profil
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item
                        tag="a"
                        :href="$sidebarFooterChangePasswordUrl"
                        icon="heroicon-o-key"
                    >
                        Ubah Password
                    </x-filament::dropdown.list.item>
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        </div>

        {{-- Keluar: icon only, no label, in every state. Function is exactly
             as before — a dropdown with "Kembali ke Admin"/"Ganti Peran" when
             either applies, otherwise straight to the confirmation modal. --}}
        <div class="shrink-0">
            @if ($sidebarFooterHasExtras)
                <x-filament::dropdown placement="top-start" teleport>
                    <x-slot name="trigger">
                        <button
                            type="button"
                            aria-label="Keluar"
                            class="fi-sidebar-item-button flex items-center justify-center rounded-lg p-2 outline-none transition duration-75"
                        >
                            <x-filament::icon
                                icon="heroicon-o-arrow-right-on-rectangle"
                                class="fi-sidebar-item-icon h-6 w-6"
                            />
                        </button>
                    </x-slot>

                    @if ($sidebarFooterIsImpersonating)
                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item
                                tag="a"
                                :href="route('impersonate.leave')"
                                icon="heroicon-o-arrow-uturn-left"
                            >
                                Kembali ke Admin
                            </x-filament::dropdown.list.item>
                        </x-filament::dropdown.list>
                    @endif

                    @if (count($sidebarFooterOptions) > 1)
                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item
                                tag="a"
                                :href="route('role.gate')"
                                icon="heroicon-o-arrow-path"
                            >
                                Ganti Peran
                            </x-filament::dropdown.list.item>
                        </x-filament::dropdown.list>
                    @endif

                    <x-filament::dropdown.list>
                        {{-- Dipicu lewat modal konfirmasi (bukan langsung submit). --}}
                        <x-filament::dropdown.list.item
                            tag="button"
                            x-data
                            x-on:click="$dispatch('open-modal', { id: 'sidebar-logout-modal' })"
                            color="danger"
                            icon="heroicon-o-arrow-right-on-rectangle"
                        >
                            Keluar
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @else
                {{-- Tidak ada "Kembali ke Admin"/"Ganti Peran" untuk ditampilkan —
                     langsung buka modal konfirmasi, tanpa dropdown perantara. --}}
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', { id: 'sidebar-logout-modal' })"
                    aria-label="Keluar"
                    class="fi-sidebar-item-button flex items-center justify-center rounded-lg p-2 outline-none transition duration-75"
                >
                    <x-filament::icon
                        icon="heroicon-o-arrow-right-on-rectangle"
                        class="fi-sidebar-item-icon h-6 w-6"
                    />
                </button>
            @endif
        </div>
    </div>
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
