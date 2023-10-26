<?php

namespace App\DataTables;

use App\Models\SelectionStage;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
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
            ->addColumn('action', function($row){
                $action = ' ';
                $action .= ' <a href="'.route('selectionstages.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
                return $action;
            })
            ->editColumn('user_id', function($row){
                    return $row->student->name;
            })
            ->editColumn('guide1_id', function($row){
                if (isset($row->guide1_id)) {
                    return $row->guide1->name;
                }
            })
            ->editColumn('guide2_id', function($row){
                if (isset($row->guide2_id)) {
                    return $row->guide2->name;
                }
            })
            ->editColumn('examiner1_id', function($row){
                if (isset($row->examiner1_id)) {
                    return $row->examiner1->name;
                }
            })
            ->editColumn('examiner2_id', function($row){
                if (isset($row->examiner2_id)) {
                    return $row->examiner2->name;
                }
            })
            ->editColumn('examiner3_id', function($row){
                if (isset($row->examiner3_id)) {
                    return $row->examiner3->name;
                }
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at->format('d/m/Y H:i:s');
            })
            ->editColumn('created_at', function($row) {
                return $row->created_at->format('d/m/Y H:i:s');
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SelectionStage $model): QueryBuilder
    {
        return $model->newQuery();
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
            Column::make('final'),
            Column::make('user_id')->title('nama'),
            Column::make('stage_order')->title('tahap'),
            Column::make('guide1_id')->title('pembimbing 1'),
            Column::make('guide2_id')->title('pembimbing 2'),
            Column::make('examiner1_id')->title('penguji 1'),
            Column::make('examiner2_id')->title('penguji 2'),
            Column::make('examiner3_id')->title('penguji 3'),
            Column::make('created_at'),
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
