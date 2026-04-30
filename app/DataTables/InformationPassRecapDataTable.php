<?php

namespace App\DataTables;

use App\Models\GuideExaminer;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Models\ExamRegistration;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class InformationPassRecapDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->filterColumn('npm', function ($query, $keyword) {
                $query->where('u.username', 'like', "%{$keyword}%");
            })
            ->filterColumn('mahasiswa', function ($query, $keyword) {
                $query->where('u.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_4', function ($query, $keyword) {
                $query->where('g1.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('penguji_5', function ($query, $keyword) {
                $query->where('g2.name', 'like', "%{$keyword}%");
            })
            ->addColumn('masa_studi', function($row){
                $angkatan = substr($row->npm,0,2);
                $tahun_masuk = '20'.$angkatan.'-09-01';
                $time = \Carbon\Carbon::parse($tahun_masuk)->diff($row->thesis_date);
                $dibaca = $time->y.' tahun '.$time->m.' bulan';
                if (is_null($row->thesis_date)) {
                    return 'belum lulus';
                } else {
                    return $dibaca;
                }

            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(GuideExaminer $model): QueryBuilder
    {
        $baseQuery = $this->baseGuideExaminerQuery($model);
        $daftar_sidang = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')->where('ge.year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',3)->pluck('exam_registrations.user_id');
        $daftar_sempro = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')->where('ge.year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',1)->pluck('exam_registrations.user_id');
        $daftar_semhas = ExamRegistration::join('guide_examiners as ge', 'exam_registrations.user_id', '=', 'ge.user_id')->where('ge.year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',2)->pluck('exam_registrations.user_id');

        if ($this->context == "Total Mahasiswa") {
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation);
        }
        if ($this->context == "Mahasiswa Lulus") {
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation)->whereNotNull('guide_examiners.thesis_date')->whereNotIn('guide_examiners.user_id', $daftar_sidang);
        }
        if ($this->context == "Mahasiswa Belum Lulus") {
            $reg = (clone $baseQuery)->whereIn('guide_examiners.user_id', $daftar_sidang);
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation)->whereNull('guide_examiners.thesis_date')->union($reg);
        }
        if ($this->context == "Mahasiswa Belum Sempro") {
            $reg = (clone $baseQuery)->whereIn('guide_examiners.user_id', $daftar_sempro);
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation)->whereNull('guide_examiners.proposal_date')->whereNull('guide_examiners.seminar_date')->whereNull('guide_examiners.thesis_date')->union($reg);
        }
        if ($this->context == "Mahasiswa Akan Semhas") {
            $reg = (clone $baseQuery)->whereIn('guide_examiners.user_id', $daftar_semhas);
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation)->whereNotNull('guide_examiners.proposal_date')->whereNull('guide_examiners.seminar_date')->whereNull('guide_examiners.thesis_date')->whereNotIn('guide_examiners.user_id', $daftar_sempro)->union($reg);
        }
        if ($this->context == "Mahasiswa Akan Sidang") {
            $reg = (clone $baseQuery)->whereIn('guide_examiners.user_id', $daftar_sidang);
            return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation)->whereNotNull('guide_examiners.proposal_date')->whereNotNull('guide_examiners.seminar_date')->whereNull('guide_examiners.thesis_date')->whereNotIn('guide_examiners.user_id', $daftar_semhas)->union($reg);
        }

        return (clone $baseQuery)->where('guide_examiners.year_generation', $this->generation);
    }

    private function baseGuideExaminerQuery(GuideExaminer $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'guide_examiners.id',
                'guide_examiners.user_id',
                'guide_examiners.year_generation',
                'guide_examiners.examiner1_id',
                'guide_examiners.examiner2_id',
                'guide_examiners.examiner3_id',
                'guide_examiners.guide1_id',
                'guide_examiners.guide2_id',
                'guide_examiners.proposal_date',
                'guide_examiners.seminar_date',
                'guide_examiners.thesis_date',
                DB::raw('u.username AS npm'),
                DB::raw('u.name AS mahasiswa'),
                DB::raw("COALESCE(e1.name, '') AS penguji_1"),
                DB::raw("COALESCE(e2.name, '') AS penguji_2"),
                DB::raw("COALESCE(e3.name, '') AS penguji_3"),
                DB::raw("COALESCE(g1.name, '') AS penguji_4"),
                DB::raw("COALESCE(g2.name, '') AS penguji_5"),
                DB::raw('e1.name AS ketua'),
                DB::raw('NULL AS doc'),
            ])
            ->join('users AS u', 'guide_examiners.user_id', '=', 'u.id')
            ->leftJoin('users AS e1', 'guide_examiners.examiner1_id', '=', 'e1.id')
            ->leftJoin('users AS e2', 'guide_examiners.examiner2_id', '=', 'e2.id')
            ->leftJoin('users AS e3', 'guide_examiners.examiner3_id', '=', 'e3.id')
            ->leftJoin('users AS g1', 'guide_examiners.guide1_id', '=', 'g1.id')
            ->leftJoin('users AS g2', 'guide_examiners.guide2_id', '=', 'g2.id');
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
                        Button::make('add'),
                        Button::make('excel'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        if ($this->context == "Mahasiswa Lulus" || $this->context == "Total Mahasiswa") {
            return [
                Column::make('npm'),
                Column::make('mahasiswa'),
                Column::make('penguji_4')->title('P1'),
                Column::make('penguji_5')->title('P2'),
                Column::make('proposal_date')->title('SemPro'),
                Column::make('seminar_date')->title('SemHas'),
                Column::make('thesis_date')->title('Sidang'),
                Column::computed('masa_studi'),
            ];
        } elseif ($this->context == "Mahasiswa Belum Sempro") {
            return [
                Column::make('npm'),
                Column::make('mahasiswa'),
                Column::make('penguji_4')->title('P1'),
                Column::make('penguji_5')->title('P2'),
                Column::make('proposal_date')->title('SemPro'),
                Column::make('seminar_date')->title('SemHas'),
            ];
        }
        else
        {
            return [
                Column::make('npm'),
                Column::make('mahasiswa'),
                Column::make('penguji_4')->title('P1'),
                Column::make('penguji_5')->title('P2'),
                Column::make('proposal_date')->title('SemPro'),
                Column::make('seminar_date')->title('SemHas'),
                Column::make('thesis_date')->title('Sidang'),
            ];

        }
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'GuideExaminers_' . date('YmdHis');
    }
}
