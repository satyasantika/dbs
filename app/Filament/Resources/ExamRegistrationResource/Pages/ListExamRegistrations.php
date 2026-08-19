<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\SetScoringToExaminerResource;
use App\Services\Examination\SintesysExamRegistrationImporter;
use App\Services\Sintesys\SintesysException;
use App\Support\SintesysExamTypeMap;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExamRegistrations extends ListRecords
{
    protected static string $resource = ExamRegistrationResource::class;

    protected static string $view = 'filament.resources.exam-registration-resource.pages.list-exam-registrations';

    protected function getHeaderActions(): array
    {
        $pendingSetCount = SetScoringToExaminerResource::pendingCount();

        return [
            Actions\Action::make('setScoringToExaminerYet')
                ->label('Set Penguji (' . $pendingSetCount . ')')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->url(SetScoringToExaminerResource::getUrl())
                ->visible($pendingSetCount > 0),
            Actions\Action::make('syncSintesys')
                ->label('Sinkronisasi Sintesys')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->modalHeading('Sinkronisasi Sintesys')
                ->modalDescription('Pilih tanggal, lihat preview baru/duplikat, lalu sinkronkan ke pendaftaran ujian.')
                ->modalSubmitActionLabel('Sinkronkan')
                ->modalWidth('5xl')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\ToggleButtons::make('mode')
                                ->label('Pilihan tanggal')
                                ->options([
                                    'harian' => 'Harian',
                                    'rentang' => 'Rentang hari',
                                ])
                                ->inline()
                                ->default('rentang')
                                ->live()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    if ($state === 'harian') {
                                        $set('tanggal_selesai', $get('tanggal_mulai'));
                                    }

                                    $this->refreshSintesysPreview($get, $set);
                                }),
                            Forms\Components\Select::make('jenis_ujian_id')
                                ->label('Jenis ujian')
                                ->options(SintesysExamTypeMap::options())
                                ->placeholder('Semua jenis')
                                ->nullable()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    $this->refreshSintesysPreview($get, $set);
                                }),
                            $this->withSintesysDateNav(
                                Forms\Components\DatePicker::make('tanggal_mulai')
                                    ->label(fn (Get $get): string => $get('mode') === 'harian' ? 'Tanggal ujian' : 'Tanggal mulai')
                                    ->native(false)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        if ($get('mode') === 'harian') {
                                            $set('tanggal_selesai', $state);
                                        }

                                        $this->refreshSintesysPreview($get, $set);
                                    }),
                            ),
                            $this->withSintesysDateNav(
                                Forms\Components\DatePicker::make('tanggal_selesai')
                                    ->label('Tanggal selesai')
                                    ->native(false)
                                    ->required(fn (Get $get): bool => $get('mode') !== 'harian')
                                    ->visible(fn (Get $get): bool => $get('mode') !== 'harian')
                                    ->live()
                                    ->minDate(fn (Get $get) => $get('tanggal_mulai'))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        $this->refreshSintesysPreview($get, $set);
                                    })
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, $value, $fail) use ($get): void {
                                            $mulai = $get('tanggal_mulai');
                                            $selesai = $get('mode') === 'harian' ? $mulai : $value;

                                            if (! $mulai || ! $selesai) {
                                                return;
                                            }

                                            $start = Carbon::parse($mulai)->startOfDay();
                                            $end = Carbon::parse($selesai)->startOfDay();

                                            if ($end->lt($start)) {
                                                $fail('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                                            }

                                            if ($start->diffInDays($end) > SintesysExamRegistrationImporter::MAX_RANGE_DAYS) {
                                                $fail('Rentang tanggal maksimal '.SintesysExamRegistrationImporter::MAX_RANGE_DAYS.' hari.');
                                            }
                                        };
                                    }),
                            ),
                        ]),
                    Forms\Components\ViewField::make('preview')
                        ->hiddenLabel()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $this->sintesysPreviewShouldShow($get))
                        ->view('filament.resources.exam-registration-resource.pages.sintesys-preview'),
                ])
                ->action(function (array $data, SintesysExamRegistrationImporter $importer): void {
                    [$mulai, $selesai] = $this->resolvedSintesysDates($data);

                    try {
                        $result = $importer->persist($mulai, $selesai, filled($data['jenis_ujian_id'] ?? null) ? (int) $data['jenis_ujian_id'] : null);
                    } catch (SintesysException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Sinkronisasi Sintesys gagal')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    $body = "Baru: {$result['created']} · Diperbarui: {$result['updated']} · Dilewati: {$result['skipped']}";

                    if ($result['errors'] !== []) {
                        $body .= "\n".implode("\n", array_slice($result['errors'], 0, 5));
                    }

                    Notification::make()
                        ->success()
                        ->title('Sinkronisasi Sintesys selesai')
                        ->body($body)
                        ->send();
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    protected function resolvedSintesysDates(array $data): array
    {
        $mulai = (string) ($data['tanggal_mulai'] ?? '');
        $selesai = (($data['mode'] ?? 'rentang') === 'harian')
            ? $mulai
            : (string) ($data['tanggal_selesai'] ?? $mulai);

        return [$mulai, $selesai];
    }

    protected function sintesysPreviewShouldShow(Get $get): bool
    {
        if (($get('mode') ?? 'rentang') === 'harian') {
            return filled($get('tanggal_mulai'));
        }

        return filled($get('tanggal_selesai'));
    }

    protected function withSintesysDateNav(Forms\Components\DatePicker $picker): Forms\Components\DatePicker
    {
        $field = $picker->getName();

        return $picker
            ->prefixAction(
                Forms\Components\Actions\Action::make($field.'_prev_month')
                    ->icon('heroicon-m-chevron-left')
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Bulan sebelumnya')
                    ->action(fn (Get $get, Set $set) => $this->nudgeSintesysDate($field, -1, $get, $set)),
            )
            ->suffixActions([
                Forms\Components\Actions\Action::make($field.'_today')
                    ->label('Hari ini')
                    ->color('gray')
                    ->action(fn (Get $get, Set $set) => $this->setSintesysDate($field, Carbon::today(), $get, $set)),
                Forms\Components\Actions\Action::make($field.'_next_month')
                    ->icon('heroicon-m-chevron-right')
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Bulan berikutnya')
                    ->action(fn (Get $get, Set $set) => $this->nudgeSintesysDate($field, 1, $get, $set)),
            ]);
    }

    protected function nudgeSintesysDate(string $field, int $months, Get $get, Set $set): void
    {
        $current = $get($field);
        $date = filled($current) ? Carbon::parse($current) : Carbon::today();

        $this->setSintesysDate($field, $date->copy()->addMonthsNoOverflow($months), $get, $set);
    }

    protected function setSintesysDate(string $field, Carbon $date, Get $get, Set $set): void
    {
        $value = $date->toDateString();
        $set($field, $value);

        if ($field === 'tanggal_mulai' && ($get('mode') ?? 'rentang') === 'harian') {
            $set('tanggal_selesai', $value);
        }

        $this->refreshSintesysPreview($get, $set);
    }

    protected function refreshSintesysPreview(Get $get, Set $set): void
    {
        if (! $this->sintesysPreviewShouldShow($get)) {
            $set('preview', null);

            return;
        }

        [$mulai, $selesai] = $this->resolvedSintesysDates([
            'mode' => $get('mode'),
            'tanggal_mulai' => $get('tanggal_mulai'),
            'tanggal_selesai' => $get('tanggal_selesai'),
        ]);

        if ($mulai === '' || $selesai === '') {
            $set('preview', null);

            return;
        }

        try {
            $set('preview', app(SintesysExamRegistrationImporter::class)->preview(
                $mulai,
                $selesai,
                filled($get('jenis_ujian_id')) ? (int) $get('jenis_ujian_id') : null,
            ));
        } catch (SintesysException $e) {
            $set('preview', [
                'rows' => [],
                'total' => 0,
                'baru' => 0,
                'duplikat' => 0,
                'error' => 0,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
