<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Models\ViewExamScore;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Models\ViewExamRegistration;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ExamRegistrationsDataTable extends DataTable
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
                $action .= ' <a href="'.route('examregistrations.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a>';
                $action .= ' <a href="'.route('examregistrations.examscores.index',$row->id).'" class="btn btn-outline-secondary btn-sm action">S</a>';
                return $action;
            })
            ->editColumn('kode_ujian', function($row) {
                if ($row->exam_type_id == 1) {
                    $warna = 'text-success';
                } elseif ($row->exam_type_id == 2) {
                    $warna = 'text-primary';
                } else {
                    $warna = 'text-danger';
                }
                $kode_ujian = '<span class="'.$warna.'">'.$row->kode_ujian.'</span>';
                return $kode_ujian;
            })
            ->editColumn('penguji_1', function($row) {
                $ketua = $row->penguji_1 == $row->ketua ? "*" : "";
                $belum = '<span class="text-danger"><i class="bi bi-x-circle"></i> '.$row->penguji_1.$ketua.'</span>';
                $sudah = '<span class="text-success"><i class="bi bi-check-circle"></i> '.$row->penguji_1.$ketua.'</span>';
                return is_null($row->grade_1) ? $belum : $sudah;
            })
            ->editColumn('penguji_2', function($row) {
                $ketua = $row->penguji_2 == $row->ketua ? "*" : "";
                $belum = '<span class="text-danger"><i class="bi bi-x-circle"></i> '.$row->penguji_2.$ketua.'</span>';
                $sudah = '<span class="text-success"><i class="bi bi-check-circle"></i> '.$row->penguji_2.$ketua.'</span>';
                return is_null($row->grade_2) ? $belum : $sudah;
            })
            ->editColumn('penguji_3', function($row) {
                $ketua = $row->penguji_3 == $row->ketua ? "*" : "";
                $belum = '<span class="text-danger"><i class="bi bi-x-circle"></i> '.$row->penguji_3.$ketua.'</span>';
                $sudah = '<span class="text-success"><i class="bi bi-check-circle"></i> '.$row->penguji_3.$ketua.'</span>';
                return is_null($row->grade_3) ? $belum : $sudah;
            })
            ->editColumn('penguji_4', function($row) {
                $ketua = $row->penguji_4 == $row->ketua ? "*" : "";
                $belum = '<span class="text-danger"><i class="bi bi-x-circle"></i> '.$row->penguji_4.$ketua.'</span>';
                $sudah = '<span class="text-success"><i class="bi bi-check-circle"></i> '.$row->penguji_4.$ketua.'</span>';
                return is_null($row->grade_4) ? $belum : $sudah;
            })
            ->editColumn('penguji_5', function($row) {
                $ketua = $row->penguji_5 == $row->ketua ? "*" : "";
                $belum = '<span class="text-danger"><i class="bi bi-x-circle"></i> '.$row->penguji_5.$ketua.'</span>';
                $sudah = '<span class="text-success"><i class="bi bi-check-circle"></i> '.$row->penguji_5.$ketua.'</span>';
                return is_null($row->grade_5) ? $belum : $sudah;
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['action', 'kode_ujian', 'penguji_1', 'penguji_2', 'penguji_3', 'penguji_4', 'penguji_5'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewExamRegistration $model): QueryBuilder
    {
        return $model->where('exam_date','like', $this->user_id.'%')->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('examregistrations-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(3)
                    ->orderBy(2,'asc')
                    ->orderBy(4,'asc')
                    ->selectStyleSingle()
                    ->buttons([
                        // Button::make('add'),
                        Button::make('print'),
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
            Column::make('kode_ujian')->title('ujian'),
            Column::make('room'),
            Column::make('exam_date'),
            Column::make('exam_time'),
            Column::make('mahasiswa'),
            Column::make('penguji_1')->title('P1'),
            Column::make('penguji_2')->title('P2'),
            Column::make('penguji_3')->title('P3'),
            Column::make('penguji_4')->title('G1'),
            Column::make('penguji_5')->title('G2'),
            // Column::make('ketua'),
            // Column::make('updated_at'),
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
