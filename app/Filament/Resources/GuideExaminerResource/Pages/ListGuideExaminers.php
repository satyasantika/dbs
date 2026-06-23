<?php

namespace App\Filament\Resources\GuideExaminerResource\Pages;

use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\GuideExaminerResource\Concerns\RemembersListTableState;
use Closure;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ListGuideExaminers extends ListRecords
{
    use RemembersListTableState;

    protected static string $resource = GuideExaminerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('sortSempro')
                    ->label(fn (): string => $this->examScheduleSortLabel('Sempro', 'proposal_date'))
                    ->icon('heroicon-m-calendar-days')
                    ->action(fn () => $this->sortTable('proposal_date')),
                Actions\Action::make('sortSemhas')
                    ->label(fn (): string => $this->examScheduleSortLabel('Semhas', 'seminar_date'))
                    ->icon('heroicon-m-calendar-days')
                    ->action(fn () => $this->sortTable('seminar_date')),
                Actions\Action::make('sortSidang')
                    ->label(fn (): string => $this->examScheduleSortLabel('Sidang', 'thesis_date'))
                    ->icon('heroicon-m-calendar-days')
                    ->action(fn () => $this->sortTable('thesis_date')),
            ])
                ->label('Urutkan Jadwal')
                ->icon('heroicon-o-bars-arrow-down')
                ->color('gray')
                ->button(),
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (Model $record, Table $table): ?string {
            $resource = static::getResource();

            if (! $resource::hasPage('edit') || ! $resource::canEdit($record)) {
                return null;
            }

            return $this->appendListTableStateToUrl(
                $resource::getUrl('edit', ['record' => $record]),
            );
        };
    }

    protected function examScheduleSortLabel(string $label, string $column): string
    {
        if ($this->tableSortColumn !== $column) {
            return $label;
        }

        return match ($this->tableSortDirection) {
            'desc' => "{$label} ↓",
            default => "{$label} ↑",
        };
    }
}
