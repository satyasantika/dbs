<?php

namespace App\Http\Controllers\Information;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\InformationPassDataTable;
use App\DataTables\InformationGuidesDataTable;

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
}
