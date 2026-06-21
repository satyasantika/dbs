<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
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

    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
