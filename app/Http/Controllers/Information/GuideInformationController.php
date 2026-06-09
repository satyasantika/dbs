<?php

namespace App\Http\Controllers\Information;

use App\Http\Controllers\Controller;
use App\DataTables\InformationPassRecapDataTable;

class GuideInformationController extends Controller
{
    public function recap(InformationPassRecapDataTable $dataTable, $generation, $context)
    {
        $title = 'List '.$context;
        $route = 'welcome';
        return $dataTable->with([
            'generation'=>$generation,
            'context'=>$context
            ])->render('layouts.setting',compact('title','route','context','generation'));
    }
}
