
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
Anda terdeteksi sudah bergabung dalam tahapan pemilihan ini, silakan ikuti teknis berikut:
<ol>
    <li>Anda dapat mengusulkan hingga (5) lima usulan pasangan pembimbing</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut dan pasangan calon pembimbing juga menerimanya, maka pasangan calon pembimbing akan langsung ditetapkan</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut, sementara pasangan calon pembimbing yang diusulkan menolak, maka usulan pasangan ini dibatalkan oleh sistem dan mahasiswa dapat mengganti usulan pasangan yang baru.</li>
    <li>Jika Bapak/Ibu menolak usulan tersebut, maka secara otomatis usulan terhadap pasangan calon pembimbing lain juga ditolak.</li>
    <li>Jika satu usulan telah diterima oleh dua calon pembimbing yang berpasangan, maka usulan pasangan lain otomatis ditolak oleh sistem.</li>
</ol>

Jika sudah siap memilih, silakan klik tombol pembimbing untuk mulai memilih.

@includeWhen(auth()->user()->can('read selection stages'), 'selection.stage-submission')

@endif
