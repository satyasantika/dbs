{{-- position:fixed, always pinned to the viewport bottom regardless of
     scroll — its `left` offset tracks the sidebar's expanded/collapsed
     width and its 64px height matches .fi-sidebar-footer's own, all
     handled in filament.shared.custom-styles (see the comment there for
     why the height and .fi-main's internal-scroll capping both matter).
     Same dark gradient as the sidebar/topbar instead of a plain gray bar. --}}
<footer class="fi-page-footer">
    <div class="fi-page-footer-hairline"></div>

    <div class="fi-page-footer-inner">
        <div class="fi-page-footer-brand">
            <span class="fi-page-footer-dot" aria-hidden="true"></span>
            <span class="fi-page-footer-brand-name">Sistem DBS</span>
            <span class="fi-page-footer-divider" aria-hidden="true">&middot;</span>
            <span>&copy; 2024 Satya Santika</span>
        </div>

        <div class="fi-page-footer-credit">
            <x-filament::icon
                icon="heroicon-o-academic-cap"
                class="fi-page-footer-credit-icon"
            />
            <span>Tim DBS &mdash; S1 Pendidikan Matematika, Universitas Siliwangi</span>
        </div>
    </div>
</footer>
