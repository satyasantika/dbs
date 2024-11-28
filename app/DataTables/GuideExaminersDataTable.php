<?php

namespace App\DataTables;

use App\Models\ViewGuideExaminer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class GuideExaminersDataTable extends DataTable
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
                $action .= ' <a href="'.route('guideexaminers.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
                $action .= ' <a href="'.route('registrations.show.student',$row->user_id).'" class="btn btn-success btn-sm action">U</a> ';
                return $action;
            })
            ->editColumn('penguji_1', function($row) {
                $ketua = $row->penguji_1 == $row->ketua ? "*" : "";
                return $row->penguji_1.$ketua;
            })
            ->editColumn('penguji_2', function($row) {
                $ketua = $row->penguji_2 == $row->ketua ? "*" : "";
                return $row->penguji_2.$ketua;
            })
            ->editColumn('penguji_3', function($row) {
                $ketua = $row->penguji_3 == $row->ketua ? "*" : "";
                return $row->penguji_3.$ketua;
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewGuideExaminer $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('guideexaminers-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons([
                        // Button::make('add'),
                        Button::make('excel'),
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
            Column::make('npm'),
            Column::make('mahasiswa'),
            // Column::make('ketua'),
            Column::make('penguji_1')->title('P1'),
            Column::make('penguji_2')->title('P2'),
            Column::make('penguji_3')->title('P3'),
            Column::make('penguji_4')->title('P4'),
            Column::make('penguji_5')->title('P5'),
            // Column::make('year_generation')->title('angkatan'),
            Column::make('proposal_date')->title('proposal'),
            Column::make('seminar_date')->title('seminar'),
            Column::make('thesis_date')->title('thesis'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'GuideExaminers_' . date('YmdHis');
    }
}
