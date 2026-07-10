<div class="flex flex-col items-start gap-1">
    @foreach ($lines as $line)
        <x-filament::badge :color="$line['color']">
            {{ $line['label'] }}
        </x-filament::badge>
    @endforeach
</div>
