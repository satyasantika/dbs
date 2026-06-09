<x-filament-panels::page class="fi-dosen-graduation-evidence-page">
    <style>
        .fi-dosen-graduation-evidence-page .fi-ta-content-grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 768px) {
            .fi-dosen-graduation-evidence-page .fi-ta-content-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .fi-dosen-graduation-evidence-page .fi-ta-content-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .fi-dosen-graduation-evidence-page .fi-ta-record > div > .flex.w-full.flex-col {
            height: 100%;
        }

        .fi-dosen-graduation-evidence-page .fi-ta-record .fi-ta-actions {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgb(229 231 235);
        }

        .dark .fi-dosen-graduation-evidence-page .fi-ta-record .fi-ta-actions {
            border-top-color: rgb(255 255 255 / 0.1);
        }
    </style>

    <div class="space-y-6">
        @include('filament.dosen.pages.partials.graduation-semester-recap')

        {{ $this->table }}
    </div>
</x-filament-panels::page>
