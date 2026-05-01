<x-filament-widgets::widget>
    <x-filament::section heading="Manajemen Ujian">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <a href="{{ route('guideexaminers.index') }}"
               class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
                    <x-heroicon-o-user-group class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Penjadwalan</p>
                    <p class="text-xs text-gray-500">Menu penjadwalan ujian</p>
                </div>
            </a>

            <a href="{{ route('examregistrations.index') }}"
               class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <x-heroicon-o-calendar-days class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Jadwal Ujian</p>
                    <p class="text-xs text-gray-500">Seluruh jadwal ujian terkini</p>
                </div>
            </a>

            <a href="{{ route('get.examinerscoringyet') }}"
               class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-warning-500 hover:shadow-md">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-100 text-warning-600">
                    <x-heroicon-o-clock class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Belum Menilai</p>
                    <p class="text-xs text-gray-500">Penguji yang belum menilai</p>
                </div>
            </a>

            <a href="{{ route('get.setscoringtoexamineryet') }}"
               class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-danger-500 hover:shadow-md">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-100 text-danger-600">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Set ke Penguji</p>
                    <p class="text-xs text-gray-500">Registrasi belum diset ke penguji</p>
                </div>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
