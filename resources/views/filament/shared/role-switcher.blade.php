@php
    $options = \App\Support\RolePanelDirectory::optionsForUser(auth()->user());
    $currentPanelId = \Filament\Facades\Filament::getCurrentPanel()?->getId();
@endphp

@if (count($options) > 1)
    <label class="sr-only" for="role-switcher">Ganti Portal</label>
    <select
        id="role-switcher"
        onchange="window.location.href = this.value"
        class="fi-input block rounded-lg border-none bg-white py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
    >
        @foreach ($options as $option)
            <option value="{{ $option['url'] }}" @selected($option['panel'] === $currentPanelId)>{{ $option['label'] }}</option>
        @endforeach
    </select>
@endif
