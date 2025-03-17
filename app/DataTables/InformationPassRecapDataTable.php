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
        if ($this->context == "Total Mahasiswa") {
            return $model->where('year_generation',$this->generation)->newQuery();
        }
        if ($this->context == "Mahasiswa Lulus") {
            return $model->where('year_generation',$this->generation)->whereNotNull('thesis_date')->newQuery();
        }
        if ($this->context == "Mahasiswa Belum Lulus") {
            return $model->where('year_generation',$this->generation)->whereNull('thesis_date')->newQuery();
        }
        if ($this->context == "Mahasiswa Belum Sempro") {
            return $model->where('year_generation',$this->generation)->whereNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->newQuery();
        }
        if ($this->context == "Mahasiswa Akan Semhas") {
            return $model->where('year_generation',$this->generation)->whereNotNull('proposal_date')->whereNull('seminar_date')->whereNull('thesis_date')->newQuery();
        }
        if ($this->context == "Mahasiswa Akan Sidang") {
            return $model->where('year_generation',$this->generation)->whereNotNull('proposal_date')->whereNotNull('seminar_date')->whereNull('thesis_date')->newQuery();
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
