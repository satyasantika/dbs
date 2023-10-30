
@if (App\Models\SelectionStage::where('user_id',auth()->user()->id)->doesntExist())
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
Halaman ini akan menuntun Anda pada proses pemilihan pembimbing tahap 2 di Jurusan Pendidikan Matematika
silakan klik tombol berikut untuk bergabung dalam proses pemilihan pembimbing tahap 2.
<form id="stage-form" action="{{ route('stages.store') }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-primary btn-sm float-end" onclick="return confirm('Yakin akan bergabung?');">
        {{ __('Join Tahap 2') }}
    </button>
</form>

@else
<h4>Halo, {{ auth()->user()->name }}</h4>
<h4>Selamat bergabung di program pemilihan pembimbing tahap 2</h4>
Halaman ini merupakan bagian dari proses pemilihan pembimbing tahap 2 di Jurusan Pendidikan Matematika.
Anda terdeteksi sudah bergabung dalam tahapan pemilihan ini, silakan klik tombol pembimbing untuk mulai memilih.

@includeWhen(auth()->user()->can('read selection stages'), 'selection.stage-submission')

@endif
