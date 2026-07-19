<x-filament-widgets::widget class="fi-wi-table" id="dosen-belum-menilai">
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

    {{-- data-grid-fit="rows" (adaptive-grid-script.blade.php): dua baris
         tetap, tidak ikut tinggi layar — kolom tetap menyesuaikan lebar
         layar (CSS auto-fill, custom-styles.blade.php). --}}
    <div data-grid-fit="rows" data-grid-fit-rows="2">
        {{ $this->table }}
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}
</x-filament-widgets::widget>
