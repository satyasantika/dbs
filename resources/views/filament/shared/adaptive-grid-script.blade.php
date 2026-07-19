{{--
    Fits ->contentGrid() tables (UserResource, RoleResource — see
    filament.shared.custom-styles for the auto-fill column CSS) to full
    rows AND columns instead of just columns: measures how many
    card-widths fit horizontally (already handled by the CSS auto-fill
    grid) and how many card-heights fit vertically in the space between
    the toolbar and the pagination/footer, then sets Livewire's own
    `tableRecordsPerPage` to (columns × rows) so the fetched page is
    exactly a filled matrix instead of a partial last row. Client-side
    approximation — depends on every card being close to the same
    height. No effect on pages without a .fi-ta-content-grid element.

    The page footer itself is a fixed 64px (custom-styles.blade.php) —
    no measurement/sync needed for it here, unlike an earlier version of
    this script.

    Some grids opt out of the viewport-height fit above via a
    data-grid-fit attribute on the nearest ancestor (wrap the grid/table
    output in a plain <div data-grid-fit="..."> in the page/widget view):
      - data-grid-fit="none": leave tableRecordsPerPage alone entirely —
        used with ->paginated(false) so every record renders, wrapping
        into as many rows as needed (e.g. the public "Jadwal Ujian" grid,
        which intentionally ignores screen height).
      - data-grid-fit="rows" data-grid-fit-rows="N": fixed N rows
        regardless of viewport height, columns still auto-fill by width
        (e.g. dashboard widgets capped at 2 rows).
    No marker present falls back to the original viewport-height fit.
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
            const marker = grid.closest('[data-grid-fit]');
            const fitMode = marker?.dataset.gridFit ?? 'viewport';

            if (fitMode === 'none') {
                return;
            }

            const firstCard = grid.querySelector(':scope > .fi-ta-record');

            if (!firstCard) {
                return;
            }

            const component = findWireComponent(grid);

            if (!component) {
                return;
            }

            const columns = countGridColumns(grid);

            if (columns < 1) {
                return;
            }

            let perPage;

            if (fitMode === 'rows') {
                const fixedRows = parseInt(marker.dataset.gridFitRows, 10) || MIN_ROWS;
                perPage = Math.min(MAX_PER_PAGE, columns * fixedRows);
            } else {
                const cardHeight = firstCard.getBoundingClientRect().height;

                if (cardHeight <= 0) {
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

                perPage = Math.min(MAX_PER_PAGE, columns * rows);
            }

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

        function runAll() {
            fitAllGrids();
        }

        const debouncedRunAll = debounce(runAll, 250);

        document.addEventListener('DOMContentLoaded', () => setTimeout(runAll, 50));
        document.addEventListener('livewire:navigated', () => setTimeout(runAll, 50));
        window.addEventListener('resize', debouncedRunAll);
    })();
</script>
