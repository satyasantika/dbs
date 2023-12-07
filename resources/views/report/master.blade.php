<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    {{-- <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet"> --}}

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style type="text/css">
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Times New Roman', Times, serif;
        }
        @page {
            /* margin: 1cm 0cm 0cm 0cm;
            width: 800px; */
            font-family: 'Times New Roman', Times, serif;
        }
        /* .vertical-space-sign {
            margin-bottom: 72pt;
        }
        .vertical-space-paragraph {
            margin-bottom: 120pt;
        } */
        /* .text-center {
            text-align:center;
        }
        .bold{
            font-weight:bold;
        }
        .font-14{
            font-size: 14pt;
            font-weight:bold;
        }
        .font-20{
            font-size: 20pt;
            font-weight:bold;
        }
        .table-center{
            margin-left:auto;
            margin-right:auto;
        } */
        .page-break {
            page-break-after: always;
            vertical-align: middle;
        }
        /* .table,
        .table th,
        .table td {
            padding:5px;
            border: 1px solid black;
            border-collapse: collapse;
        } */
    </style>
</head>
<body>
    @include('report.header-fkip')
    @yield('report')
</body>
</html>
