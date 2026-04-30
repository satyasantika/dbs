<?php

namespace App\DataTables;

use App\Models\SelectionGuide;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Services\DataTable;

class SelectionGuidesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->filterColumn('mahasiswa', function ($query, $keyword) {
                $query->where('stu.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('group_id', function ($query, $keyword) {
                $query->where('gg.group', 'like', "%{$keyword}%");
            })
            ->filterColumn('pasangan', function ($query, $keyword) {
                $query->where('selection_guides.pair_order', 'like', "%{$keyword}%");
            })
            ->filterColumn('pembimbing', function ($query, $keyword) {
                $query->where('selection_guides.guide_order', 'like', "%{$keyword}%");
            })
            ->filterColumn('dosen', function ($query, $keyword) {
                $query->where('lec.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->whereRaw("CASE WHEN selection_guides.approved = 1 THEN 'disetujui' WHEN selection_guides.approved = 0 THEN 'ditolak' ELSE 'proses' END LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('keterangan', function ($query, $keyword) {
                $query->where('selection_guides.information', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function($row){
                $action = '';
                $action .= ' <a href="'.route('selectionguides.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
                return $action;
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at->format('Y-m-d H:i:s');
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SelectionGuide $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'selection_guides.*',
                DB::raw('stu.name AS mahasiswa'),
                DB::raw('gg.`group` AS group_id'),
                DB::raw('selection_guides.pair_order AS pasangan'),
                DB::raw('selection_guides.guide_order AS pembimbing'),
                DB::raw('lec.name AS dosen'),
                DB::raw('selection_guides.information AS keterangan'),
                DB::raw("CASE WHEN selection_guides.approved = 1 THEN 'disetujui' WHEN selection_guides.approved = 0 THEN 'ditolak' ELSE 'proses' END AS status"),
            ])
            ->join('selection_stages AS ss', 'selection_guides.selection_stage_id', '=', 'ss.id')
            ->join('users AS stu', 'ss.user_id', '=', 'stu.id')
            ->leftJoin('guide_groups AS gg', 'selection_guides.guide_group_id', '=', 'gg.id')
            ->leftJoin('users AS lec', 'selection_guides.user_id', '=', 'lec.id');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('selectionguides-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('add'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
            Column::make('mahasiswa'),
            Column::make('group_id'),
            Column::make('pasangan'),
            Column::make('pembimbing'),
            Column::make('dosen'),
            Column::make('status'),
            Column::make('keterangan'),
            Column::make('updated_at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'SelectionGuides_' . date('YmdHis');
    }
}
