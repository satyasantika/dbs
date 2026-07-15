<?php

namespace App\DataTables;

use App\Models\NuirProposal;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NuirProposalsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('student_name', fn (NuirProposal $row) => $row->submission?->user?->name ?? '-')
            ->addColumn('year_generation', fn (NuirProposal $row) => $row->submission?->year_generation ?? '-')
            ->addColumn('version', fn (NuirProposal $row) => $row->submission?->version ?? '-')
            ->addColumn('guide1_name', fn (NuirProposal $row) => $row->guide1?->name ?? '-')
            ->addColumn('guide2_name', fn (NuirProposal $row) => $row->guide2?->name ?? '-')
            ->editColumn('final', fn (NuirProposal $row) => $row->final ? 'Ya' : 'Tidak')
            ->addColumn('action', function (NuirProposal $row) {
                if ($row->guide1_status === 'accepted' && $row->guide2_status === 'accepted' && ! $row->final) {
                    return '<form method="POST" action="'.route('nuir.proposals.finalize', $row->id).'">'
                        .csrf_field().method_field('PUT')
                        .'<button class="btn btn-sm btn-warning">Force Finalize</button></form>';
                }

                return '-';
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(NuirProposal $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['submission.user', 'guide1', 'guide2'])
            ->when(request('year_generation'), fn ($q, $year) => $q->whereHas(
                'submission',
                fn ($sub) => $sub->where('year_generation', $year)
            ))
            ->when(request('status'), function ($q, $status) {
                $q->where(function ($inner) use ($status) {
                    $inner->where('guide1_status', $status)->orWhere('guide2_status', $status);
                });
            })
            ->latest();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('nuir-proposals-table')
            ->columns($this->getColumns())
            ->minifiedAjax(url()->current())
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('reload'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('action')->exportable(false)->printable(false)->width(120),
            Column::make('student_name')->title('Mahasiswa')->orderable(false),
            Column::make('year_generation')->title('Angkatan')->orderable(false),
            Column::make('version')->title('Versi')->orderable(false),
            Column::make('guide1_name')->title('Guide 1')->orderable(false),
            Column::make('guide2_name')->title('Guide 2')->orderable(false),
            Column::make('guide1_status')->title('Status 1'),
            Column::make('guide2_status')->title('Status 2'),
            Column::make('final')->title('Final'),
            Column::make('created_at')->title('Tanggal'),
        ];
    }

    protected function filename(): string
    {
        return 'NuirProposals_'.date('YmdHis');
    }
}
