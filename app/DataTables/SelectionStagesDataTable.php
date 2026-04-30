<?php

namespace App\DataTables;

use App\Models\SelectionStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Services\DataTable;

class SelectionStagesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->filterColumn('tahap', function ($query, $keyword) {
                $query->where('selection_stages.stage_order', 'like', "%{$keyword}%");
            })
            ->filterColumn('npm', function ($query, $keyword) {
                $query->where('stu.username', 'like', "%{$keyword}%");
            })
            ->filterColumn('mahasiswa', function ($query, $keyword) {
                $query->where('stu.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('pembimbing_1', function ($query, $keyword) {
                $query->where('g1.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('pembimbing_2', function ($query, $keyword) {
                $query->where('g2.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('grup1_id', function ($query, $keyword) {
                $query->whereRaw('(SELECT sg.guide_group_id FROM selection_guides sg WHERE sg.selection_stage_id = selection_stages.id AND sg.guide_order = 1 ORDER BY sg.id DESC LIMIT 1) LIKE ?', ["%{$keyword}%"]);
            })
            ->filterColumn('grup2_id', function ($query, $keyword) {
                $query->whereRaw('(SELECT sg.guide_group_id FROM selection_guides sg WHERE sg.selection_stage_id = selection_stages.id AND sg.guide_order = 2 ORDER BY sg.id DESC LIMIT 1) LIKE ?', ["%{$keyword}%"]);
            })
            ->addColumn('action', function($row){
                $action = '';
                $action .= ' <a href="'.route('selectionstages.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
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
    public function query(SelectionStage $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'selection_stages.*',
                DB::raw('selection_stages.stage_order AS tahap'),
                DB::raw('stu.username AS npm'),
                DB::raw('stu.name AS mahasiswa'),
                DB::raw("COALESCE(g1.name, '') AS pembimbing_1"),
                DB::raw("COALESCE(g2.name, '') AS pembimbing_2"),
                DB::raw('(SELECT sg.guide_group_id FROM selection_guides sg WHERE sg.selection_stage_id = selection_stages.id AND sg.guide_order = 1 ORDER BY sg.id DESC LIMIT 1) AS grup1_id'),
                DB::raw('(SELECT sg.guide_group_id FROM selection_guides sg WHERE sg.selection_stage_id = selection_stages.id AND sg.guide_order = 2 ORDER BY sg.id DESC LIMIT 1) AS grup2_id'),
            ])
            ->join('users AS stu', 'selection_stages.user_id', '=', 'stu.id')
            ->leftJoin('users AS g1', 'selection_stages.guide1_id', '=', 'g1.id')
            ->leftJoin('users AS g2', 'selection_stages.guide2_id', '=', 'g2.id');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('selectionstages-table')
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
            Column::make('tahap'),
            Column::make('final'),
            Column::make('npm'),
            Column::make('mahasiswa'),
            Column::make('pembimbing_1')->title('P1'),
            Column::make('pembimbing_2')->title('P2'),
            Column::make('grup1_id')->title('Grup1'),
            Column::make('grup2_id')->title('Grup2'),
            Column::make('updated_at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'SelectionStages_' . date('YmdHis');
    }
}
