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

class InformationGuidesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewGuideExaminer $model): QueryBuilder
    {
        return $model->whereRaw('(guide1_id='.auth()->id().' OR guide2_id='.auth()->id().') AND thesis_date is NULL')
                    // ->whereNull('thesis_date')
                    // ->where(function (Builder $query) {
                    //         $query->where('guide1_id',auth()->id())
                    //             ->orWhere('guide2_id',auth()->id());
                    //     })

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
                    ->orderBy(0,'desc')
                    ->orderBy(1,'asc')
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
            Column::make('year_generation')->title('angkatan'),
            Column::make('mahasiswa'),
            Column::make('penguji_4')->title('P1'),
            Column::make('penguji_5')->title('P2'),
            Column::make('proposal_date')->title('tanggal sempro'),
            Column::make('seminar_date')->title('tanggal semhas'),
            Column::make('thesis_date')->title('tanggal sidang'),
            Column::make('npm'),
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
