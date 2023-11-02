
<h3>Selamat datang, {{ auth()->user()->name }}</h3>
Halaman ini berisi usulan proses pemilihan pembimbing tahap 2 dari mahasiswa sekait dengan kesediaan untuk menjadi Dosen Pembimbing.
Bapak/Ibu dapat menerima atau menolak usulan tersebut selama kuota masih tersedia.
Perhatikan catatan berikut:
<ol>
    <li>Setiap mahasiswa diizinkan mengusulkan hingga (5) lima usulan pasangan pembimbing</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut dan pasangan calon pembimbing juga menerimanya, maka pasangan calon pembimbing akan langsung ditetapkan</li>
    <li>Jika Bapak/Ibu menerima usulan tersebut, sementara pasangan calon pembimbing yang diusulkan menolak, maka usulan pasangan ini dibatalkan oleh sistem dan usulan ini tetap diarsipkan, sementara mahasiswa dapat mengajukan usulan lain pasangan baru.</li>
    <li>Jika Bapak/Ibu menolak usulan tersebut, maka secara otomatis usulan terhadap pasangan calon pembimbing lain juga ditolak.</li>
    <li>Jika satu usulan telah diterima oleh dua calon pembimbing yang berpasangan, maka usulan pasangan lain otomatis ditolak oleh sistem.</li>
</ol>

@includeWhen(auth()->user()->can('respon selection guide'), 'selection.guide-respon')
