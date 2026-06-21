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

        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div class="w-full max-w-xs">
                <label for="examDateFilter" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Tanggal Ujian
                </label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="examDateFilter"
                        type="date"
                        wire:model.live="examDate"
                    />
                </x-filament::input.wrapper>
            </div>

            <a
                href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl() }}"
                class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
            >
                Lihat semua pendaftaran →
            </a>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}
    </x-filament::section>
</x-filament-widgets::widget>
