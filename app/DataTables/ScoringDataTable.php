<?php

namespace App\DataTables;

use Illuminate\Support\Str;
use App\Models\ViewExamScore;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ScoringDataTable extends DataTable
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
                $action .= ' <a href="'.route('scoring.edit',$row->id).'" class="btn btn-success btn-sm action">nilai</a>';
                if (!is_null($row->registration->exam_file))
                {
                    $action .= ' <a href="'.$row->registration->exam_file.'" class="btn btn-primary btn-sm action">FILE</a>';
                }
                return $action;
            })
            ->AddColumn('dinilai', function($row){
                $cek_guide1 = ($row->guide1_id == auth()->id());
                $cek_guide2 = ($row->guide2_id == auth()->id());
                if ( is_null($row->letter) ) {
                    return 'belum';
                }else {
                    return 'sudah';
                }
            })
            ->editColumn('revision', function($row) {
                $decision = $row->revision ? "V" : "X";
                $revision = is_null($row->revision) ? "belum ditentukan" : $decision ;
                return $revision;
            })
            ->editColumn('revision_note', function($row) {
                $revision_note = Str::limit($row->revision_note,50);
                return $revision_note;
            })
            ->editColumn('pass_approved', function($row) {
                $decision = $row->pass_approved ? "V" : "X";
                $pass_approved = is_null($row->pass_approved) ? "belum ditentukan" : $decision ;
                return $pass_approved;
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewExamScore $model): QueryBuilder
    {
        return $model->where('user_id',auth()->id())
                    ->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('scoring-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->orderBy(4,'desc')
                    ->selectStyleSingle()
                    ->buttons([
                        // Button::make('add'),
                        // Button::make('print'),
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
                  ->width(90),
            Column::make('dinilai'),
            Column::make('mahasiswa'),
            Column::make('ujian'),
            Column::make('exam_date')->title('tanggal')->width(65),
            Column::make('revision')->title('rev?')->addClass('text-center'),
            Column::make('revision_note')->title('catatan'),
            Column::make('pass_approved')->title('lanjut?')->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ExamRegistrations_' . date('YmdHis');
    }
}
