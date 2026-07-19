{{--
    Makes ->contentGrid() tables (UserResource, RoleResource — see
    filament.shared.custom-styles for the auto-fill column CSS) fill full
    rows AND columns instead of just columns: measures how many card-widths
    fit horizontally (already handled by the CSS auto-fill grid) and how
    many card-heights fit vertically in the space between the toolbar and
    the pagination/footer, then sets Livewire's own `tableRecordsPerPage`
    to (columns × rows) so the fetched page is exactly a filled matrix
    instead of a partial last row.

    This is a client-side approximation — it depends on every card being
    close to the same height, which holds for these two resources but
    isn't guaranteed for arbitrary content. No effect on pages without a
    .fi-ta-content-grid element.
--}}
<script>
    (function () {
        const RESERVED_PX = 24; // breathing room below the last row, above pagination.
        const MIN_ROWS = 1;
        const MAX_PER_PAGE = 100; // sanity cap — never request more than this in one page.

        function debounce(fn, ms) {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };
        }

        function findWireComponent(el) {
            const host = el.closest('[wire\\:id]');
            if (!host || typeof window.Livewire === 'undefined') {
                return null;
            }

            return window.Livewire.find(host.getAttribute('wire:id')) ?? null;
        }

        function countGridColumns(grid) {
            const template = getComputedStyle(grid).gridTemplateColumns;

            if (!template || template === 'none') {
                return 1;
            }

            return template.trim().split(/\s+/).length;
        }

        function fitGridToViewport(grid) {
            const firstCard = grid.querySelector(':scope > .fi-ta-record');

            if (!firstCard) {
                return;
            }

            const component = findWireComponent(grid);

            if (!component) {
                return;
            }

            const columns = countGridColumns(grid);
            const cardHeight = firstCard.getBoundingClientRect().height;

            if (columns < 1 || cardHeight <= 0) {
                return;
            }

            const rowGap = parseFloat(getComputedStyle(grid).rowGap) || 0;
            const gridTop = grid.getBoundingClientRect().top;

            const pagination = document.querySelector('.fi-ta-pagination');
            const footer = document.querySelector('.fi-page-footer');
            const reserved = (pagination?.offsetHeight ?? 0) + (footer?.offsetHeight ?? 0) + RESERVED_PX;

            const availableHeight = window.innerHeight - gridTop - reserved;
            const rowHeight = cardHeight + rowGap;
            const rows = Math.max(MIN_ROWS, Math.floor(availableHeight / rowHeight));

            const perPage = Math.min(MAX_PER_PAGE, columns * rows);

            // $wire is a Proxy — plain properties are read directly (no
            // .get()/.set() methods); $set() is the documented API for
            // writing back to the Livewire component.
            if (component.tableRecordsPerPage !== perPage) {
                component.$set('tableRecordsPerPage', perPage);
            }
        }

        function fitAllGrids() {
            document.querySelectorAll('.fi-ta-content-grid').forEach(fitGridToViewport);
        }

        const debouncedFit = debounce(fitAllGrids, 250);

        document.addEventListener('DOMContentLoaded', () => setTimeout(fitAllGrids, 50));
        document.addEventListener('livewire:navigated', () => setTimeout(fitAllGrids, 50));
        window.addEventListener('resize', debouncedFit);
    })();
</script>
