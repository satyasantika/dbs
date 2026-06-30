<?php

namespace App\Filament\NuirManajer\Concerns;

use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Services\NuirRevisionHistoryService;
use App\Support\NuirTextLimits;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\TextEntry;

trait BuildsManajerNuirSubmissionInfolist
{
    /**
     * @return array<int, Infolists\Components\Component>
     */
    protected function manajerSubmissionRingkasanSchema(?Action $validatorAction = null): array
    {
        $validatorEntry = TextEntry::make('assignment.validator.name')
            ->label('Validator')
            ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : 'Belum ditugaskan')
            ->placeholder('Belum ditugaskan');

        if ($validatorAction !== null) {
            $validatorEntry->hintAction($validatorAction);
        }

        return [
            TextEntry::make('user.name')->label('Mahasiswa'),
            TextEntry::make('year_generation')->label('Angkatan'),
            TextEntry::make('version')->label('Versi'),
            TextEntry::make('status')->label('Status')->badge(),
            $validatorEntry,
            TextEntry::make('dbs_note')
                ->label('Catatan Revisi')
                ->placeholder('—')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Infolists\Components\Component>
     */
    protected function manajerSubmissionKontenSchema(): array
    {
        return [
            Infolists\Components\ViewEntry::make('title_content')
                ->view('filament.nuir-manajer.infolists.content-field')
                ->viewData(fn (NuirSubmission $record): array => self::contentFieldViewData($record, 'title')),
            Infolists\Components\ViewEntry::make('novelty_content')
                ->view('filament.nuir-manajer.infolists.content-field')
                ->viewData(fn (NuirSubmission $record): array => self::contentFieldViewData($record, 'novelty')),
            Infolists\Components\ViewEntry::make('urgency_content')
                ->view('filament.nuir-manajer.infolists.content-field')
                ->viewData(fn (NuirSubmission $record): array => self::contentFieldViewData($record, 'urgency')),
            Infolists\Components\ViewEntry::make('impact_content')
                ->view('filament.nuir-manajer.infolists.content-field')
                ->viewData(fn (NuirSubmission $record): array => self::contentFieldViewData($record, 'impact')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function contentFieldViewData(NuirSubmission $record, string $field): array
    {
        $config = match ($field) {
            'title' => [
                'field' => 'title',
                'label' => 'Judul',
                'badge' => null,
                'icon' => 'heroicon-o-bookmark',
                'accent' => 'primary',
                'hint' => 'Judul topik penelitian yang diajukan mahasiswa.',
            ],
            'novelty' => [
                'field' => 'novelty',
                'label' => 'Novelty',
                'badge' => 'N',
                'icon' => 'heroicon-o-light-bulb',
                'accent' => 'info',
                'hint' => 'Kebaruan penelitian dibanding studi sebelumnya.',
            ],
            'urgency' => [
                'field' => 'urgency',
                'label' => 'Urgency',
                'badge' => 'U',
                'icon' => 'heroicon-o-clock',
                'accent' => 'warning',
                'hint' => 'Urgensi dan keterdesakan permasalahan yang diteliti.',
            ],
            'impact' => [
                'field' => 'impact',
                'label' => 'Impact',
                'badge' => 'I',
                'icon' => 'heroicon-o-arrow-trending-up',
                'accent' => 'success',
                'hint' => 'Dampak dan manfaat yang diharapkan dari penelitian.',
            ],
            default => throw new \InvalidArgumentException("Field tidak dikenal: {$field}"),
        };

        $content = $record->{$field} ?? '';
        $historyService = app(NuirRevisionHistoryService::class);

        return [
            ...$config,
            'content' => filled($content) ? $content : '—',
            'wordMeta' => self::wordCountDescription($record, $field),
            'isEmpty' => blank($content),
            'revisionRound' => $historyService->contentFieldRevisionRound($record, $field),
            'revisionHistory' => $historyService->contentFieldHistory($record, $field)->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function referencesPanelViewData(NuirSubmission $record): array
    {
        $historyService = app(NuirRevisionHistoryService::class);
        $references = $record->references()->orderBy('ref_order')->get();

        return [
            'references' => $references,
            'histories' => $references
                ->pluck('ref_order')
                ->mapWithKeys(fn (int $refOrder) => [
                    $refOrder => $historyService->referenceRevisionHistory($record, $refOrder)->all(),
                ])
                ->all(),
            'revisionRounds' => $references
                ->pluck('ref_order')
                ->mapWithKeys(fn (int $refOrder) => [
                    $refOrder => $historyService->referenceRevisionRound($record, $refOrder),
                ])
                ->all(),
        ];
    }

    public static function wordCountDescription(NuirSubmission $record, string $field): string
    {
        $text = $record->{$field} ?? '';
        $words = NuirTextLimits::wordCount($text);

        if ($field === 'title') {
            return "{$words} kata dikirim";
        }

        $setting = NuirSetting::where('year_generation', $record->year_generation)->first();
        $min = $setting?->{"min_words_{$field}"};
        $max = $setting?->{"max_words_{$field}"};

        $meta = "{$words} kata dikirim";

        if ($min !== null && $max !== null) {
            $meta .= " · batas {$min}–{$max} kata";
        } elseif ($max !== null) {
            $meta .= " · maks. {$max} kata";
        } elseif ($min !== null) {
            $meta .= " · min. {$min} kata";
        }

        return $meta;
    }
}
