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
                $action .= ' <a href="'.route('scoring.edit',$row->id).'" class="btn btn-primary btn-sm action">Nilai</a>';
                if (!is_null($row->registration->exam_file))
                {
                    $action .= ' <a href="'.$row->registration->exam_file.'" target="_blank" class="btn btn-outline-dark btn-sm action">File</a>';
                }
                return $action;
            })
            ->editColumn('mahasiswa', function($row) {
                if ($row->ujian == 'sempro') {
                    $warna = 'bg-light text-success';
                } elseif ($row->ujian == 'semhas') {
                    $warna = 'bg-light text-primary';
                } else {
                    $warna = 'bg-primary';
                }
                $student = '<span class="badge '.$warna.'">'.$row->ujian.'</span>';
                return $student.$row->mahasiswa;
            })
            ->editColumn('waktu', function($row){
                $timestamp = strtotime($row->waktu);
                $waktu = '<span class="badge bg-light text-dark">'.date('d-m-Y', $timestamp).'</span>';
                $waktu .= ' <span class="badge bg-dark text-white">'.date('H:i', $timestamp).'</span>';
                return $waktu;
            })
            ->editColumn('revision_note', function($row) {

                $icon_direvisi = '<span class="badge bg-warning text-dark"><i class="bi bi-quote"></i> ada revisi</span>';
                $icon_belum_direvisi = '<span class="badge bg-success"><i class="bi bi-shield-check"></i> tanpa revisi</span>';
                $decision_rev = $row->revision ? $icon_direvisi : $icon_belum_direvisi ;

                $note = $decision_rev.' '.Str::limit($row->revision_note,50);
                if ( is_null($row->letter) ) {
                    $nilai =  '<span class="badge bg-light text-danger"><i class="bi bi-x-circle"></i> belum dinilai</span>';
                }else {
                    $nilai =  ' <span class="badge bg-primary">grade: '.$row->letter.'</span>';
                }
                $icon_lanjut = '<span class="badge bg-success">pass <i class="bi bi-check-circle"></i></span>';

                $keputusan_lanjut = $row->pass_approved ? $icon_lanjut : '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> gagal</span>';
                $note .= ' '.$nilai.' '.$keputusan_lanjut;

                $icon_belum_dinilai = '<span class="badge bg-danger"><i class="bi bi-question-diamond-fill"></i> belum dinilai</span>' ;

                $revision_note = is_null($row->revision) ? $icon_belum_dinilai : $note ;

                return $revision_note;
            })
            ->rawColumns(['dinilai', 'action', 'mahasiswa', 'waktu', 'revision_note', 'pass_approved'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ViewExamScore $model): QueryBuilder
    {
        return $model->selectRaw("*, CONCAT(exam_date, ' ', exam_time) as waktu")
                    ->where('user_id',auth()->id())
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
                    ->orderBy(2,'desc')
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
                  ->width(100),
            Column::make('mahasiswa'),
            Column::make('waktu')->searchable(false),
            Column::make('revision_note')->title('catatan'),
            // Column::make('pass_approved')->title('lanjut?')->addClass('text-center'),
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
