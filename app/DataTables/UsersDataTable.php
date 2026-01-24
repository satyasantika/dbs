<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
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
                $action = '<div class="row">';
                $action .= ' <div class="col-auto">';
                $action .= ' <a href="'.route('impersonate',$row->id).'" class="btn btn-outline-primary btn-sm action">I</a>';
                $action .= ' <a href="'.route('userroles.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">R</a>';
                $action .= ' <a href="'.route('userpermissions.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">P</a>';
                $action .= ' <a href="'.route('users.edit',$row->id).'" class="btn btn-outline-primary btn-sm action">E</a></div>';
                $action .= ' <div class="col-auto"><form id="activation-form" action='.route('users.activation',$row->id).' method="POST"> <button type="submit" class="btn btn-'.($row->hasPermissionTo('active') ? 'outline-success' : 'outline-danger').' btn-sm"><input type="hidden" name="_token" value='.csrf_token().'><input type="hidden" name="_method" value="PUT">'. ($row->hasPermissionTo('active') ? 'A':'nA').' </button> </form></div>';
                $action .= '</div>';
                return $action;
            })
            ->editColumn('username', function($row){
                return $row->username.($row->hasPermissionTo('active')?'-a':'-na');
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at->format('Y-m-d H:i:s');
            })
            ->editColumn('created_at', function($row) {
                return $row->created_at->format('Y-m-d H:i:s');
            })

            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('users-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1,'asc')
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('add'),
                        Button::make('excel'),
                        Button::make('csv'),
                        // Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload'),
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
                  ->width(150),
                //   ->addClass('text-center'),
            // Column::make('id'),
            Column::make('name'),
            Column::make('username')->title('NPM'),
            Column::make('created_at'),
            Column::make('updated_at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
