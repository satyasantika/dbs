<?php

namespace App\DataTables;

use App\Models\GuideExaminer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
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
            ->filterColumn('npm', function ($query, $keyword) {
                $query->where('u.username', 'like', "%{$keyword}%");
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
            ->editColumn('proposal_date', function($row) {
                return is_null($row->proposal_date)? '': \Carbon\Carbon::parse($row->proposal_date)->isoFormat('Y-MM-DD');
            })
            ->editColumn('seminar_date', function($row) {
                return is_null($row->seminar_date)? '': \Carbon\Carbon::parse($row->seminar_date)->isoFormat('Y-MM-DD');
            })
            ->editColumn('thesis_date', function($row) {
                return is_null($row->thesis_date)? '': \Carbon\Carbon::parse($row->thesis_date)->isoFormat('Y-MM-DD');
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(GuideExaminer $model): QueryBuilder
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
