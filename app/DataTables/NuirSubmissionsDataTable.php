<?php

namespace App\DataTables;

use App\Models\NuirSubmission;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NuirSubmissionsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('student_name', fn (NuirSubmission $row) => $row->user?->name ?? '-')
            ->editColumn('status', fn (NuirSubmission $row) => '<span class="badge bg-secondary">'.$row->status.'</span>')
            ->editColumn('updated_at', fn (NuirSubmission $row) => $row->updated_at?->format('d-m-Y H:i'))
            ->addColumn('reviewer_name', fn (NuirSubmission $row) => $row->dbsReviewer?->name ?? '-')
            ->addColumn('action', fn (NuirSubmission $row) => '<a href="'.route('nuir.review.show', $row->id).'" class="btn btn-outline-primary btn-sm">Lihat</a>')
            ->rawColumns(['status', 'action'])
            ->setRowId('id');
    }

    public function query(NuirSubmission $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['user', 'dbsReviewer'])
            ->when(request('year_generation'), fn ($q, $year) => $q->where('year_generation', $year))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('updated_at');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('nuir-submissions-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('reload'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('action')->exportable(false)->printable(false)->width(70),
            Column::make('student_name')->title('Mahasiswa')->orderable(false),
            Column::make('year_generation')->title('Angkatan'),
            Column::make('version')->title('Versi'),
            Column::make('status')->title('Status'),
            Column::make('updated_at')->title('Tgl Submit'),
            Column::make('reviewer_name')->title('Reviewer')->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'NuirSubmissions_'.date('YmdHis');
    }
}
