<?php

namespace App\Http\Controllers\Information;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\InformationPassDataTable;
use App\DataTables\InformationGuidesDataTable;
use App\DataTables\InformationPassRecapDataTable;

class GuideInformationController extends Controller
{
    public function index(InformationGuidesDataTable $dataTable)
    {
        $title = 'Bimbingan belum lulus';
        return $dataTable->render('layouts.setting',compact('title'));
    }

    public function pass(InformationPassDataTable $dataTable)
    {
        $title = 'Lulusan Pembimbing Penguji';
        return $dataTable->render('layouts.setting',compact('title'));
    }

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
