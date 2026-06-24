<?php

namespace App\DataTables;

use App\Models\NuirSetting;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NuirSettingsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('active', fn (NuirSetting $row) => $row->active
                ? '<span class="badge bg-success">Aktif</span>'
                : '<span class="badge bg-secondary">Nonaktif</span>')
            ->editColumn('deadline', fn (NuirSetting $row) => $row->deadline?->format('d-m-Y') ?? '-')
            ->addColumn('action', function (NuirSetting $row) {
                $action = ' <a href="'.route('nuir-settings.edit', $row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
                $action .= ' <form action="'.route('nuir-settings.toggle', $row->id).'" method="POST" class="d-inline">'
                    .csrf_field().method_field('PUT')
                    .'<button type="submit" class="btn btn-outline-secondary btn-sm">T</button></form>';

                return $action;
            })
            ->rawColumns(['active', 'action'])
            ->setRowId('id');
    }

    public function query(NuirSetting $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('year_generation');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('nuir-settings-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('add'),
                Button::make('reload'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(90)
                ->addClass('text-center'),
            Column::make('year_generation')->title('Angkatan'),
            Column::make('stage')->title('Tahap'),
            Column::make('active')->title('Status'),
            Column::make('deadline')->title('Deadline'),
            Column::make('min_references_approved')->title('Min Ref'),
            Column::make('max_chars_novelty')->title('Max Novelty'),
            Column::make('max_chars_urgency')->title('Max Urgency'),
            Column::make('max_chars_impact')->title('Max Impact'),
        ];
    }

    protected function filename(): string
    {
        return 'NuirSettings_'.date('YmdHis');
    }
}
