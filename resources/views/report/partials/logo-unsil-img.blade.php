{{-- DomPDF tidak bisa mengambil asset() via HTTP; selipkan berkas dari public sebagai data URI. --}}
@php
    $reportLogoSrc = '';
    foreach ([['LOGOUNSIL.png', 'image/png'], ['logo-unsil.png', 'image/png'], ['LOGOUNSIL.jpg', 'image/jpeg'], ['LOGOUNSIL.jpeg', 'image/jpeg']] as [$filename, $mime]) {
        $path = public_path($filename);
        if (is_readable($path)) {
            $reportLogoSrc = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            break;
        }
    }
@endphp
@if ($reportLogoSrc !== '')
    <img src="{{ $reportLogoSrc }}" height="100" alt="" style="max-height:100px;height:100px;width:auto;display:block;"/>
@endif
