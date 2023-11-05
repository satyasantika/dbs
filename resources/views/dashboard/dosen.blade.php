
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
<hr>
untuk melihat hasil pemilihan pembimbing tahap 1 silakan klik tombol berikut:<br>
<a href="{{ route('respons.result') }}" class="btn btn-sm btn-primary">hasil tahap 1</a>
<hr>

untuk merespon pemilihan pembimbing di tahap 2 silakan klik tommbol berikut:<br>
<a href="{{ route('respons.index') }}" class="btn btn-sm btn-primary">proses tahap 2</a>




{{-- @includeWhen(auth()->user()->can('respon selection guide'), 'selection.guide-respon') --}}
