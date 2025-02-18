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

class InformationPassDataTable extends DataTable
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
                $action .= ' <a href="'.$row->doc.'" target="_blank" class="btn btn-outline-primary btn-sm action">Bukti</a>';
                return $action;
            })
            ->AddColumn('status', function($row){
                $cek_guide1 = ($row->guide1_id == auth()->id());
                $cek_guide2 = ($row->guide2_id == auth()->id());
                if ($cek_guide1 || $cek_guide2) {
                    return 'pembimbing';
                }else {
                    return 'penguji';
                }
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewGuideExaminer $model): QueryBuilder
    {
        return $model->whereRaw('(guide1_id='.auth()->id().
                            ' OR guide2_id='.auth()->id().
                            ' OR examiner1_id='.auth()->id().
                            ' OR examiner2_id='.auth()->id().
                            ' OR examiner3_id='.auth()->id().
                            ') AND thesis_date IS NOT NULL')
                    ->newQuery();
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
                    ->orderBy(3,'desc')
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
            Column::make('thesis_date')->title('tanggal sidang'),
            Column::make('status'),
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
