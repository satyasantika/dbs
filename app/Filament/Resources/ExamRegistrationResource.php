<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamRegistrationResource\Pages;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\ExamType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

class ExamRegistrationResource extends Resource
{
    protected static ?string $model = ExamRegistration::class;


    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Pendaftaran Ujian';

    protected static ?string $pluralModelLabel = 'Pendaftaran Ujian';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Ujian')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Mahasiswa')
                            ->options(fn () => User::role('mahasiswa')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('exam_type_id')
                            ->label('Jenis Ujian')
                            ->options(fn () => \App\Models\ExamType::orderBy('id')->pluck('name', 'id'))
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('registration_order')
                            ->label('Ujian Ke-')
                            ->options(function (Forms\Get $get, ?ExamRegistration $record): array {
                                $userId     = $get('user_id');
                                $examTypeId = $get('exam_type_id');
                                $max        = 3;

                                $used = ExamRegistration::where('user_id', $userId)
                                    ->where('exam_type_id', $examTypeId)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->pluck('registration_order')
                                    ->toArray();

                                $available = [];
                                for ($i = 1; $i <= $max; $i++) {
                                    if (!in_array($i, $used)) {
                                        $available[$i] = (string) $i;
                                    }
                                }

                                return $available ?: [1 => '1'];
                            })
                            ->hidden(fn (Forms\Get $get): bool => !$get('exam_type_id'))
                            ->required(),
                        Forms\Components\DatePicker::make('exam_date')
                            ->label('Tanggal Ujian')
                            ->hidden(fn (Forms\Get $get): bool => !$get('exam_type_id')),
                        Forms\Components\TimePicker::make('exam_time')
                            ->label('Waktu Ujian')
                            ->hidden(fn (Forms\Get $get): bool => !$get('exam_type_id')),
                        Forms\Components\Select::make('room')
                            ->label('Ruangan')
                            ->options([
                                '1' => 'Ruang Ujian 1',
                                '2' => 'Ruang Ujian 2',
                                '3' => 'Ruang Ujian 3',
                                '4' => 'Ruang Ujian 4',
                            ])
                            ->hidden(fn (Forms\Get $get): bool => !$get('exam_type_id')),
                    ])->columns(2),

                Forms\Components\Section::make('Pembimbing')
                    ->schema([
                        Forms\Components\Select::make('guide1_id')
                            ->label('Pembimbing 1')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('guide2_id')
                            ->label('Pembimbing 2')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Penguji')
                    ->description('Tombol ↑/↓ untuk menyusun ulang urutan. Tombol "Set Ketua" untuk menetapkan Ketua Penguji.')
                    ->schema([
                        Forms\Components\Hidden::make('chief_id'),

                        Forms\Components\Select::make('examiner1_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id')
                                ? new HtmlString('Penguji 1 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 1')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_1')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner1_id'))),
                                Forms\Components\Actions\Action::make('swap_down_1')
                                    ->label('↓')->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-down')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner2_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id')
                                ? new HtmlString('Penguji 2 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 2')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_2')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner2_id'))),
                                Forms\Components\Actions\Action::make('swap_up_2')
                                    ->label('↑')->tooltip('Tukar dengan Penguji 1')
                                    ->icon('heroicon-m-arrow-up')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                                Forms\Components\Actions\Action::make('swap_down_2')
                                    ->label('↓')->tooltip('Tukar dengan Penguji 3')
                                    ->icon('heroicon-m-arrow-down')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner3_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id')
                                ? new HtmlString('Penguji 3 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 3')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_3')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner3_id'))),
                                Forms\Components\Actions\Action::make('swap_up_3')
                                    ->label('↑')->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-up')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Detail Skripsi')
                    ->schema([
                        Forms\Components\Textarea::make('title')
                            ->label('Judul Skripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('ipk')
                            ->label('IPK')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Textarea::make('online_link')
                            ->label('Link Online')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('online_user')
                            ->label('User Meeting')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('online_password')
                            ->label('Password Meeting')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Hasil Ujian')
                    ->schema([
                        Forms\Components\Placeholder::make('grade_display')
                            ->label('Nilai Akhir')
                            ->content(fn (?ExamRegistration $record): string => $record?->grade !== null
                                ? number_format((float) $record->grade, 2)
                                : '—'),
                        Forms\Components\Placeholder::make('letter_display')
                            ->label('Huruf Mutu')
                            ->content(fn (?ExamRegistration $record): string => $record?->letter ?? '—'),
                        Forms\Components\Placeholder::make('pass_display')
                            ->label('Status Kelulusan')
                            ->content(fn (?ExamRegistration $record): HtmlString|string => match (true) {
                                is_null($record?->pass_exam) => '—',
                                (bool) $record->pass_exam    => new HtmlString('<span class="font-semibold text-success-600">✓ Lulus</span>'),
                                default                      => new HtmlString('<span class="font-semibold text-danger-600">✗ Belum Lulus</span>'),
                            }),
                        Forms\Components\Placeholder::make('sent_display')
                            ->label('Pesan Hasil ke Mahasiswa')
                            ->content(function (?ExamRegistration $record): HtmlString|string {
                                if (!$record) return '—';
                                if ($record->sent_at) {
                                    $tgl = $record->sent_at->locale('id')->isoFormat('D MMMM Y, [pukul] HH.mm');
                                    return new HtmlString('<span class="text-success-700 font-medium">✓ Dikabari pada ' . e($tgl) . '</span>');
                                }
                                $pending = \App\Models\ExamScore::where('exam_registration_id', $record->id)
                                    ->whereNull('grade')->count();
                                if ($pending > 0) {
                                    return new HtmlString('<span class="text-warning-600">Menunggu selesai penilaian <strong>' . $pending . '</strong> penguji</span>');
                                }
                                return new HtmlString('<span class="text-primary-600">Penilaian sudah lengkap — pesan belum dikirim</span>');
                            }),
                    ])->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'examScores:id,exam_registration_id,user_id,examiner_order,grade,pass_approved',
            'examScores.lecture:id,name',
            'examiner1:id,name',
            'examiner2:id,name',
            'examiner3:id,name',
            'guide1:id,name',
            'guide2:id,name',
            'student:id,name,phone',
            'examtype:id,name',
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Mahasiswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('examtype.name')
                    ->label('Jenis Ujian')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_order')
                    ->label('Ke-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_date')
                    ->label('Tgl Ujian')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_time')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('H:i') : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('penguji')
                    ->label('Penguji')
                    ->getStateUsing(fn (ExamRegistration $record): string => static::buildExaminerHtml($record))
                    ->html()
                    ->wrap()
                    ->sortable(false)
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($q) use ($search) {
                                foreach (['examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2'] as $rel) {
                                    $q->orWhereHas($rel, fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                                }
                            });
                        }
                    ),
                Tables\Columns\TextColumn::make('pass_exam')
                    ->label('Lulus')
                    ->getStateUsing(fn (ExamRegistration $record): string => static::buildPassSendHtml($record))
                    ->html()
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Jenis Ujian')
                    ->relationship('examtype', 'name'),
                Tables\Filters\TernaryFilter::make('pass_exam')
                    ->label('Status Kelulusan'),
                Tables\Filters\Filter::make('exam_date')
                    ->label('Tanggal Ujian')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q, $d) => $q->whereDate('exam_date', '>=', $d))
                            ->when($data['until'], fn ($q, $d) => $q->whereDate('exam_date', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from']  ?? null) $indicators[] = Tables\Filters\Indicator::make('Dari: '    . $data['from']);
                        if ($data['until'] ?? null) $indicators[] = Tables\Filters\Indicator::make('Sampai: ' . $data['until']);
                        return $indicators;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\Action::make('view_scores')
                    ->label('Rincian Penilaian')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->iconButton()
                    ->modalHeading(function (ExamRegistration $record): string {
                        $record->loadMissing(['examtype', 'student']);
                        $type  = $record->examtype?->name ?? 'Ujian';
                        $name  = $record->student?->name ?? '';
                        return "Penilaian {$type} (ke-{$record->registration_order}) — {$name}";
                    })
                    ->modalContent(fn (ExamRegistration $record) => view('filament.modals.exam-scores-detail', ['recordId' => $record->id]))
                    ->modalWidth(\Filament\Support\Enums\MaxWidth::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\Action::make('notify_student')
                    ->label(fn (ExamRegistration $record): string => $record->sent_at ? 'Kirim ulang' : 'Kabari')
                    ->icon('heroicon-o-paper-airplane')
                    ->color(fn (ExamRegistration $record) => $record->sent_at ? 'gray' : 'success')
                    ->iconButton()
                    ->tooltip(fn (ExamRegistration $record): string => $record->sent_at
                        ? 'Kirim ulang via WhatsApp (waktu terkirim diperbarui). Terakhir: '.$record->sent_at->locale('id')->isoFormat('D MMM Y, HH.mm')
                        : 'Kabari mahasiswa: buka WhatsApp di tab baru dan tandai terkirim')
                    ->action(fn (ExamRegistration $record, $livewire) => static::kabariMahasiswaLewatWhatsapp($record, $livewire))
                    ->visible(function (ExamRegistration $record): bool {
                        $activeIds = array_values(array_filter([
                            $record->examiner1_id, $record->examiner2_id, $record->examiner3_id,
                            $record->guide1_id, $record->guide2_id,
                        ]));
                        $scores = $record->examScores->whereIn('user_id', $activeIds);

                        return $scores->count() > 0 && $scores->whereNull('grade')->count() === 0;
                    }),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->hidden(fn (ExamRegistration $record) => $record->examScores->whereNotNull('grade')->isNotEmpty()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('exam_date', 'desc');
    }

    private static function buildExaminerHtml(ExamRegistration $record): string
    {
        $activeIds = array_values(array_filter([
            $record->examiner1_id, $record->examiner2_id, $record->examiner3_id,
            $record->guide1_id, $record->guide2_id,
        ]));

        $scores = $record->examScores->whereIn('user_id', $activeIds);

        if ($scores->isEmpty()) {
            // Fallback: show from direct fields when exam_scores not yet created
            $slots = array_filter([
                ['no' => 1, 'id' => $record->examiner1_id, 'name' => $record->examiner1?->name],
                ['no' => 2, 'id' => $record->examiner2_id, 'name' => $record->examiner2?->name],
                ['no' => 3, 'id' => $record->examiner3_id, 'name' => $record->examiner3?->name],
                ['no' => 4, 'id' => $record->guide1_id,    'name' => $record->guide1?->name],
                ['no' => 5, 'id' => $record->guide2_id,    'name' => $record->guide2?->name],
            ], fn ($s) => !empty($s['id']));

            if (empty($slots)) {
                return '<span style="font-size:11px;color:#9ca3af;font-style:italic">Belum diset</span>';
            }

            $html = '';
            foreach ($slots as $slot) {
                $isChief = $record->chief_id && $slot['id'] == $record->chief_id;
                $html .= '<div style="font-size:11px;line-height:1.6;color:#6b7280">'
                    . $slot['no'] . '. ' . ($isChief ? '★ ' : '') . e($slot['name'] ?? '(?)')
                    . '</div>';
            }
            return $html;
        }

        $html = '';
        foreach ($scores as $score) {
            $name    = e($score->lecture?->name ?? '(?)');
            $isChief = $record->chief_id && $score->user_id == $record->chief_id;
            $prefix  = $isChief ? '★ ' : '';
            $color   = $score->grade !== null ? '#16a34a' : '#dc2626';
            $html   .= '<div style="font-size:11px;line-height:1.6;color:' . $color . '">'
                . $score->examiner_order . '. ' . $prefix . $name
                . '</div>';
        }

        return $html;
    }

    public static function buildPassSendHtml(ExamRegistration $record): string
    {
        $activeIds = array_values(array_filter([
            $record->examiner1_id, $record->examiner2_id, $record->examiner3_id,
            $record->guide1_id, $record->guide2_id,
        ]));

        $scores    = $record->examScores->whereIn('user_id', $activeIds);
        $total     = $scores->count();
        $allScored = $total > 0 && $scores->whereNull('grade')->count() === 0;

        $approvedCount = $allScored ? $scores->filter(fn ($s) => $s->pass_approved == 1)->count() : 0;
        $passed        = $allScored && $approvedCount >= 3;
        $failed        = $allScored && !$passed;

        $passIcon = $passed
            ? '<span style="color:#16a34a;font-weight:700">✓</span>'
            : ($failed
                ? '<span style="color:#dc2626;font-weight:700">✗</span>'
                : '<span style="color:#9ca3af">—</span>');

        if (!$allScored) {
            return $passIcon;
        }

        if ($record->sent_at) {
            $tgl      = e($record->sent_at->locale('id')->isoFormat('D MMM Y, HH.mm'));
            $sentIcon = '<span title="Dikabari ' . $tgl . '" style="margin-left:4px;color:#16a34a;vertical-align:middle">'
                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display:inline;vertical-align:middle"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.63 1.2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.86a16 16 0 0 0 6.12 6.12l1.07-.94a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'
                . '</span>';
            return '<div style="display:flex;align-items:center;gap:2px">' . $passIcon . $sentIcon . '</div>';
        }

        return $passIcon;
    }

    public static function buildWaUrl(ExamRegistration $record): ?string
    {
        if (!$record->student?->phone) return null;

        $examtype    = $record->examtype?->name ?? '-';
        $studentName = $record->student->name;
        $examDate    = $record->exam_date?->isoFormat('dddd, D MMMM Y') ?? '-';

        $text = "*INFORMASI Hasil {$examtype}*\n\n"
            . "Saudara *{$studentName}*, Kami informasikan bahwa masing-masing dosen penguji "
            . "telah menuliskan revisi {$examtype} ({$examDate}) dan dapat dicetak pada sistem DBS berikut.\n\n"
            . route('exam.result') . "\n"
            . "(jika eror saat buka link di handphone, pastikan awalannya http:// bukan https://)"
            . ($record->exam_type_id == 3
                ? "\n\nTerakhir, harap laporkan hasil ujian Anda pada laman "
                  . "(siapkan lembar revisi yang sudah ditandatangani dan foto ujian):\nhttps://forms.gle/umUKgAcXLnhowgpw7"
                : '')
            . "\n\nDemikian informasi ini Kami sampaikan. Atas perhatian Anda, Kami ucapkan terima kasih.\n"
            . "(ttd.) *Kajur Pendidikan Matematika*";

        return 'https://api.whatsapp.com/send/?phone=62' . $record->student->phone . '&text=' . rawurlencode($text);
    }

    /**
     * Menyetel sent_at ke sekarang, membuka WhatsApp di tab baru — sama seperti tombol kabari
     * di halaman penilaian lama. Mendukung kirim ulang (sent_at selalu diperbarui).
     */
    public static function kabariMahasiswaLewatWhatsapp(ExamRegistration $record, mixed $livewire): void
    {
        $record->loadMissing(['student', 'examtype']);
        $waUrl = static::buildWaUrl($record);

        $record->update(['sent_at' => now()]);

        if ($waUrl) {
            $livewire->js('window.open('.Js::from($waUrl).', "_blank")');
        } else {
            Notification::make()
                ->warning()
                ->title('Nomor telepon mahasiswa tidak ada')
                ->body('Hasil tetap ditandai terkirim. Lengkapi nomor HP mahasiswa untuk WhatsApp.')
                ->send();
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamRegistrations::route('/'),
            'create' => Pages\CreateExamRegistration::route('/create'),
            'edit' => Pages\EditExamRegistration::route('/{record}/edit'),
        ];
    }
}
