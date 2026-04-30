<?php

namespace App\DataTables;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Models\ExamRegistration;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
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
            ->filterColumn('kode_ujian', function ($query, $keyword) {
                $query->where('et.code', 'like', "%{$keyword}%");
            })
            ->filterColumn('mahasiswa', function ($query, $keyword) {
                $query->where('u.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_1', function ($query, $keyword) {
                $query->where('e1.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_2', function ($query, $keyword) {
                $query->where('e2.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_3', function ($query, $keyword) {
                $query->where('e3.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_4', function ($query, $keyword) {
                $query->where('g1.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_5', function ($query, $keyword) {
                $query->where('g2.name', 'like', "%{$keyword}%");
            })
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
            ->editColumn('exam_date', function($row) {
                return is_null($row->exam_date)? '': \Carbon\Carbon::parse($row->exam_date)->isoFormat('Y-MM-DD');
            })
            ->rawColumns(['action', 'kode_ujian', 'penguji_1', 'penguji_2', 'penguji_3', 'penguji_4', 'penguji_5'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ExamRegistration $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'exam_registrations.id',
                'exam_registrations.exam_type_id',
                'exam_registrations.registration_order',
                'exam_registrations.user_id',
                'exam_registrations.examiner1_id',
                'exam_registrations.examiner2_id',
                'exam_registrations.examiner3_id',
                'exam_registrations.guide1_id',
                'exam_registrations.guide2_id',
                'exam_registrations.chief_id',
                'exam_registrations.exam_date',
                'exam_registrations.exam_time',
                'exam_registrations.title',
                'exam_registrations.ipk',
                'exam_registrations.room',
                'exam_registrations.pass_exam',
                'exam_registrations.updated_at',
                DB::raw('u.name AS mahasiswa'),
                DB::raw('et.code AS kode_ujian'),
                DB::raw("COALESCE(e1.name, '') AS penguji_1"),
                DB::raw("COALESCE(e2.name, '') AS penguji_2"),
                DB::raw("COALESCE(e3.name, '') AS penguji_3"),
                DB::raw("COALESCE(g1.name, '') AS penguji_4"),
                DB::raw("COALESCE(g2.name, '') AS penguji_5"),
                DB::raw('ch.name AS ketua'),
                DB::raw('(SELECT id FROM exam_scores WHERE exam_registration_id = exam_registrations.id AND examiner_order = 1 LIMIT 1) AS grade_1'),
                DB::raw('(SELECT id FROM exam_scores WHERE exam_registration_id = exam_registrations.id AND examiner_order = 2 LIMIT 1) AS grade_2'),
                DB::raw('(SELECT id FROM exam_scores WHERE exam_registration_id = exam_registrations.id AND examiner_order = 3 LIMIT 1) AS grade_3'),
                DB::raw('(SELECT id FROM exam_scores WHERE exam_registration_id = exam_registrations.id AND examiner_order = 4 LIMIT 1) AS grade_4'),
                DB::raw('(SELECT id FROM exam_scores WHERE exam_registration_id = exam_registrations.id AND examiner_order = 5 LIMIT 1) AS grade_5'),
            ])
            ->join('users AS u', 'exam_registrations.user_id', '=', 'u.id')
            ->join('exam_types AS et', 'exam_registrations.exam_type_id', '=', 'et.id')
            ->leftJoin('users AS e1', 'exam_registrations.examiner1_id', '=', 'e1.id')
            ->leftJoin('users AS e2', 'exam_registrations.examiner2_id', '=', 'e2.id')
            ->leftJoin('users AS e3', 'exam_registrations.examiner3_id', '=', 'e3.id')
            ->leftJoin('users AS g1', 'exam_registrations.guide1_id', '=', 'g1.id')
            ->leftJoin('users AS g2', 'exam_registrations.guide2_id', '=', 'g2.id')
            ->leftJoin('users AS ch', 'exam_registrations.chief_id', '=', 'ch.id')
            ->where('exam_registrations.exam_date', 'like', $this->user_id . '%');
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
