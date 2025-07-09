<?php

namespace App\DataTables;

use App\Models\ViewGuideExaminer;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Models\ViewExamRegistration;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
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
    public function query(ViewGuideExaminer $model): QueryBuilder
    {
        $daftar_sidang = ViewExamRegistration::where('year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',3)->pluck('user_id');
        $daftar_sempro = ViewExamRegistration::where('year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',1)->pluck('user_id');
        $daftar_semhas = ViewExamRegistration::where('year_generation',$this->generation)->where('pass_exam',0)->where('exam_type_id',2)->pluck('user_id');
        // dd($daftar_sidang);
        if ($this->context == "Total Mahasiswa") {
            return $model->where('year_generation',$this->generation)->newQuery();
        }
        if ($this->context == "Mahasiswa Lulus") {
            return $model->where('year_generation',$this->generation)->whereNotNull('thesis_date')->whereNotIn('user_id',$daftar_sidang)->newQuery();
        }
        if ($this->context == "Mahasiswa Belum Lulus") {
            $reg = $model->whereIn('user_id',$daftar_sidang)->newQuery();
            return $model->where('year_generation',$this->generation)->whereNull('thesis_date')->union($reg)->newQuery();
        }
        if ($this->context == "Mahasiswa Belum Sempro") {
            $reg = $model->whereIn('user_id',$daftar_sempro)->newQuery();
            return $model->where('year_generation',$this->generation)->whereNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->union($reg)->newQuery();
        }
        if ($this->context == "Mahasiswa Akan Semhas") {
            $reg = $model->whereIn('user_id',$daftar_semhas)->newQuery();
            return $model->where('year_generation',$this->generation)->whereNotNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->whereNotIn('user_id',$daftar_sempro)->union($reg)->newQuery();
        }
        if ($this->context == "Mahasiswa Akan Sidang") {
            $reg = $model->whereIn('user_id',$daftar_sidang)->newQuery();
            return $model->where('year_generation',$this->generation)->whereNotNull('proposal_date')->whereNotNull('seminar_date')->whereNull('thesis_date')->whereNotIn('user_id',$daftar_semhas)->union($reg)->newQuery();
        }
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
