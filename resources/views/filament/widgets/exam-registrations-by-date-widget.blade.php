<x-filament-widgets::widget class="fi-wi-table fi-resource-exam-registrations">
    <x-filament::section heading="Manajemen Ujian">
        <style>
            .fi-resource-exam-registrations .exam-registrations-wrap-cell {
                white-space: normal;
            }

            @include('filament.partials.exam-registration-student-column-styles')

            .fi-resource-exam-registrations .exam-registrations-date-cell {
                max-width: 6.5rem;
                min-width: 4.5rem;
            }

            .fi-resource-exam-registrations .exam-registrations-examiner-cell {
                white-space: nowrap;
                min-width: 7rem;
                max-width: 14rem;
            }

            .fi-resource-exam-registrations .exam-registrations-examiner-cell .fi-ta-text {
                min-width: 0;
            }

            .fi-resource-exam-registrations .fi-ta-actions-cell > div {
                white-space: normal;
            }

            .fi-resource-exam-registrations .fi-ta-actions {
                flex-wrap: wrap;
                justify-content: flex-end;
                row-gap: 0.375rem;
                column-gap: 0.5rem;
                max-width: 7.5rem;
            }

            .fi-resource-exam-registrations .fi-ta-actions-header-cell {
                white-space: normal;
            }

            .exam-calendar {
                width: 100%;
                max-width: 26rem;
            }

            .exam-calendar-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .exam-calendar-selects {
                display: grid;
                flex: 1;
                grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
                gap: 0.5rem;
                min-width: 0;
            }

            .exam-calendar-select-label {
                display: block;
                margin-bottom: 0.25rem;
                font-size: 0.7rem;
                font-weight: 700;
                color: rgb(107 114 128);
            }

            .dark .exam-calendar-select-label {
                color: rgb(156 163 175);
            }

            .exam-calendar-selects .fi-input-wrp {
                min-width: 0;
            }

            .exam-calendar-nav-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 0.5rem;
                border: 1px solid rgb(209 213 219);
                background: rgb(255 255 255);
                color: rgb(55 65 81);
                transition: background-color 0.15s ease, border-color 0.15s ease;
            }

            .exam-calendar-nav-btn:hover {
                background: rgb(243 244 246);
                border-color: rgb(156 163 175);
            }

            .dark .exam-calendar-nav-btn {
                border-color: rgb(75 85 99);
                background: rgb(31 41 55);
                color: rgb(229 231 235);
            }

            .dark .exam-calendar-nav-btn:hover {
                background: rgb(55 65 81);
            }

            .exam-calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.25rem;
            }

            .exam-calendar-weekday {
                text-align: center;
                font-size: 0.7rem;
                font-weight: 700;
                color: rgb(107 114 128);
                padding: 0.25rem 0;
            }

            .exam-calendar-day {
                position: relative;
                aspect-ratio: 1;
                min-height: 2.5rem;
                border: 1px solid transparent;
                border-radius: 0.625rem;
                background: transparent;
                color: rgb(17 24 39);
                cursor: pointer;
                transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                padding: 0.2rem;
            }

            .exam-calendar-day:hover {
                background: rgb(239 246 255);
                border-color: rgb(191 219 254);
            }

            .exam-calendar-day.is-outside {
                color: rgb(156 163 175);
            }

            .exam-calendar-day.is-today {
                border-color: rgb(59 130 246);
            }

            .exam-calendar-day.is-selected {
                background: rgb(37 99 235);
                border-color: rgb(37 99 235);
                color: rgb(255 255 255);
            }

            .exam-calendar-day.is-selected.is-outside {
                color: rgb(255 255 255);
            }

            .exam-calendar-day.is-selected .exam-calendar-badge {
                background: rgb(255 255 255);
                color: rgb(220 38 38);
            }

            .dark .exam-calendar-day {
                color: rgb(243 244 246);
            }

            .dark .exam-calendar-day.is-outside {
                color: rgb(107 114 128);
            }

            .dark .exam-calendar-day:hover {
                background: rgb(30 58 138 / 0.35);
                border-color: rgb(59 130 246);
            }

            .exam-calendar-day-inner {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.15rem;
                width: 100%;
                height: 100%;
            }

            .exam-calendar-day-number {
                font-size: 0.82rem;
                font-weight: 700;
                line-height: 1;
            }

            .exam-calendar-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.15rem;
                height: 1.15rem;
                padding: 0 0.2rem;
                border-radius: 9999px;
                background: rgb(220 38 38);
                color: rgb(255 255 255);
                font-size: 0.62rem;
                font-weight: 800;
                line-height: 1;
            }

            .exam-calendar-footer {
                margin-top: 0.75rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .exam-calendar-selected {
                font-size: 0.82rem;
                color: rgb(75 85 99);
            }

            .dark .exam-calendar-selected {
                color: rgb(209 213 219);
            }

            .exam-calendar-legend {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.75rem;
                color: rgb(107 114 128);
            }

            @media (min-width: 1024px) {
                .fi-resource-exam-registrations .exam-registrations-student-cell,
                .fi-resource-exam-registrations .exam-registrations-date-cell,
                .fi-resource-exam-registrations .exam-registrations-examiner-cell {
                    max-width: none;
                }

                .fi-resource-exam-registrations .fi-ta-actions {
                    max-width: none;
                }
            }
        </style>

        <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
            <div class="exam-calendar">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Tanggal Ujian
                </label>

                <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="exam-calendar-nav">
                        <button
                            type="button"
                            class="exam-calendar-nav-btn"
                            wire:click="previousMonth"
                            aria-label="Bulan sebelumnya"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>

                        <div class="exam-calendar-selects">
                            <div>
                                <label for="examCalendarMonth" class="exam-calendar-select-label">Bulan</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select id="examCalendarMonth" wire:model.live="calendarMonthNum">
                                        @foreach ($this->calendarMonthOptions as $value => $label)
                                            <option value="{{ $value }}">{{ ucfirst($label) }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>

                            <div>
                                <label for="examCalendarYear" class="exam-calendar-select-label">Tahun</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select id="examCalendarYear" wire:model.live="calendarYear">
                                        @foreach ($this->calendarYearOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="exam-calendar-nav-btn"
                            wire:click="nextMonth"
                            aria-label="Bulan berikutnya"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="exam-calendar-grid" role="grid" aria-label="Kalender ujian">
                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                            <div class="exam-calendar-weekday" role="columnheader">{{ $weekday }}</div>
                        @endforeach

                        @foreach ($this->calendarWeeks as $week)
                            @foreach ($week as $day)
                                <button
                                    type="button"
                                    role="gridcell"
                                    wire:key="exam-calendar-day-{{ $day['date'] }}"
                                    wire:click="selectExamDate('{{ $day['date'] }}')"
                                    @class([
                                        'exam-calendar-day',
                                        'is-outside' => ! $day['inMonth'],
                                        'is-today' => $day['isToday'],
                                        'is-selected' => $day['isSelected'],
                                    ])
                                    aria-label="{{ \Carbon\Carbon::parse($day['date'])->locale(app()->getLocale())->isoFormat('D MMMM YYYY') . ($day['count'] > 0 ? ', ' . $day['count'] . ' ujian' : '') }}"
                                    aria-pressed="{{ $day['isSelected'] ? 'true' : 'false' }}"
                                >
                                    <span class="exam-calendar-day-inner">
                                        <span class="exam-calendar-day-number">{{ $day['day'] }}</span>

                                        @if ($day['count'] > 0)
                                            <span class="exam-calendar-badge" aria-hidden="true">{{ $day['count'] }}</span>
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="exam-calendar-footer">
                        <p class="exam-calendar-selected">
                            Dipilih: <strong>{{ $this->selectedDateLabel }}</strong>
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="exam-calendar-legend">
                                <span class="exam-calendar-badge">3</span>
                                jumlah ujian
                            </span>

                            <button
                                type="button"
                                class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                wire:click="goToToday"
                            >
                                Hari ini
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <a
                href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl() }}"
                class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
            >
                Lihat semua pendaftaran →
            </a>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

        {{-- data-grid-fit="rows" (adaptive-grid-script.blade.php): dua baris
             tetap, tidak ikut tinggi layar seperti grid lain — kolom tetap
             menyesuaikan lebar layar (CSS auto-fill, custom-styles.blade.php). --}}
        <div data-grid-fit="rows" data-grid-fit-rows="2">
            {{ $this->table }}
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}
    </x-filament::section>
</x-filament-widgets::widget>
