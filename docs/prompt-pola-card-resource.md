# Prompt: Terapkan pola card-grid responsif ke resource lain

Tempel/gunakan prompt di bawah ini (ganti `[NamaResource]`) kapan pun ingin
menerapkan tampilan card seperti `UserResource`/`RoleResource` ke resource
Filament lain di proyek ini.

---

## Prompt

> Terapkan pola tampilan card responsif (seperti `app/Filament/Resources/UserResource.php`
> dan `RoleResource.php`) ke `[NamaResource]`. Sidebar, lebar konten utama, dan
> footer sudah diatur secara global untuk seluruh panel — jangan diubah atau
> diduplikasi. Ikuti checklist di `docs/prompt-pola-card-resource.md` persis,
> termasuk semua catatan gotcha di dalamnya, lalu verifikasi dengan menjalankan
> test resource terkait dan mengecek HTML hasil render.

---

## Yang SUDAH global — jangan disentuh ulang

Semua ini sudah berlaku otomatis di **semua panel** begitu ditambahkan, tidak
perlu diulang per-resource atau per-halaman:

| Bagian | File | Catatan |
|---|---|---|
| Tema gelap sidebar/topbar | `resources/views/filament/shared/custom-styles.blade.php` | Gradient login, warna aktif/hover, dll. |
| Lebar sidebar minimize (3.5rem) + divider kategori | sama seperti di atas + `sidebar/index.blade.php` | Marker class `fi-sidebar-nav-collapsed`, bukan Tailwind asli — lihat komentar di file. |
| Identitas user (avatar/nama/role) + Keluar | `resources/views/filament/shared/sidebar-footer.blade.php` | Split kiri-kanan, pindah ke navbar saat minimize. |
| Edit Profil / Ubah Password | `app/Filament/Shared/Pages/{EditProfile,ChangePassword}.php` | Sudah didaftarkan di `pages()` semua panel + `userMenuItems()`. Lebar form dibatasi `max-w-xl` lewat properti `$maxContentWidth` di kelas halamannya sendiri. |
| Konten utama selebar layar, padding 31px/30px | `resources/views/vendor/filament-panels/components/layout/index.blade.php` | `<main>` sekarang `flex-1` (bukan `h-full`) supaya footer sticky bekerja. |
| **Grid card auto-fill (lebar kolom)** | `custom-styles.blade.php`, aturan `.fi-ta-content-grid` | `minmax(320px, 1fr)` — otomatis berlaku ke *resource apa pun* yang punya `.fi-ta-content-grid` di halamannya. |
| **Penyesuaian jumlah baris ke tinggi layar** | `resources/views/filament/shared/adaptive-grid-script.blade.php` | Script global yang mendeteksi semua `.fi-ta-content-grid` di halaman manapun dan mengatur `tableRecordsPerPage` — tidak perlu wiring tambahan per resource. |
| Footer halaman | `resources/views/filament/shared/page-footer.blade.php` | Terdaftar lewat `PanelsRenderHook::FOOTER` di `AppServiceProvider`. |

Karena grid CSS dan script adaptif sudah **global**, langkah yang benar-benar
perlu dilakukan per-resource hanya di bawah ini.

## Langkah per-resource

1. **Aktifkan mode card.** Tambahkan ke `table()`:
   ```php
   ->contentGrid(['default' => 1])
   ```
   ⚠️ **Gotcha #1**: `contentGrid()` saja **tidak cukup**. Filament hanya benar-benar
   merender card kalau `$table->hasColumnsLayout()` bernilai `true`, dan itu HANYA
   terpicu kalau kolom dibungkus komponen Layout (`Stack`/`Split`), bukan array
   `TextColumn` biasa. Tanpa langkah 2, halaman tetap render `<table>` normal
   walau `contentGrid()` sudah dipasang (sudah pernah terjadi persis di
   `UserResource`/`RoleResource` — butuh trace manual ke
   `vendor/filament/tables/resources/views/index.blade.php` baris ~295 untuk
   ketemu kondisinya: `($content || $hasColumnsLayout) && ...`).

2. **Bungkus kolom dengan `Layout\Stack`.** Ubah:
   ```php
   ->columns([
       TextColumn::make('a')...,
       TextColumn::make('b')...,
   ])
   ```
   menjadi:
   ```php
   ->columns([
       Tables\Columns\Layout\Stack::make([
           Tables\Columns\Layout\Split::make([   // opsional: sejajarkan 2 kolom
               TextColumn::make('a')...,
               TextColumn::make('b')...,
           ]),
           TextColumn::make('c')...,              // sisanya susun vertikal
       ])->space(2),
   ])
   ```
   Semua modifier yang sudah ada (`searchable()`, `sortable()`, `badge()`,
   `toggleable()`, `counts()`, dst) tetap jalan sama persis di dalam
   Stack/Split — tidak ada yang perlu diganti.

3. **Actions berupa icon.** Tambahkan `->iconButton()` ke `EditAction`,
   `DeleteAction`, dan action custom lain di `->actions([...])`.

4. **(Opsional) Sembunyikan delete kalau record masih dipakai relasi lain.**
   ```php
   TextColumn::make('xxx_count')->counts('namaRelasi')->badge()->sortable(),
   // ...
   Tables\Actions\DeleteAction::make()
       ->iconButton()
       ->hidden(fn ($record): bool => $record->namaRelasi_count > 0),
   ```
   Contoh persis: `RoleResource` (`users_count`, hide delete kalau role masih
   dipakai). ⚠️ **Gotcha #2**: kalau menulis test untuk ini, `assertTableActionHidden()`/
   `assertTableColumnStateSet()` HARUS diberi **key record** (`$record->getKey()`),
   bukan instance Model langsung — instance Model yang dibuat manual di test
   tidak pernah melalui query `withCount()`, jadi `xxx_count`-nya `null` dan
   assertion gagal walau logic aplikasinya benar. Lihat
   `tests/Feature/Filament/AdminRoleResourceTest.php` untuk contoh yang benar.

5. **Search & filter tetap aktif.** Mode card tidak mematikan apa pun —
   pastikan saja `->searchable()` di kolom yang relevan dan `->filters([...])`
   tidak ikut terhapus saat refactor.

6. **Kalau resource ini baru** (belum pernah tampil di sidebar): daftarkan di
   `->resources([...])` panel provider terkait, dan pastikan
   `$navigationGroup`-nya SUDAH ADA di `->navigationGroups()` panel itu
   **tanpa** `->icon()` di level grup — icon di level grup membuat Filament
   mengganti isi grup jadi flyout ikon tunggal saat sidebar diminimize, bukan
   menampilkan ikon tiap menu satu-satu (lihat komentar di
   `AdminPanelProvider::panel()`).

## Verifikasi

1. `docker exec -e HOME=/tmp dbs-php php artisan test --filter=[NamaTestResource]`
2. Cek HTML hasil render (lewat test `Livewire::test()->html()` atau curl
   dengan sesi login) — pastikan:
   - `<table` **tidak muncul** untuk resource ini (kalau masih ada, langkah 2
     di atas belum benar).
   - `fi-ta-content-grid` **dan** `rounded-xl shadow-sm ring-1` ada di HTML
     (tanda card benar-benar aktif, bukan cuma `contentGrid()` tanpa efek).
3. Buka halamannya di browser sungguhan (bukan cuma curl) untuk cek jumlah
   baris menyesuaikan tinggi layar — bagian ini (`adaptive-grid-script.blade.php`)
   adalah pendekatan sisi-klien yang belum pernah divalidasi visual langsung
   di browser nyata, jadi selalu layak dicek ulang tiap kali dipakai di
   resource baru dengan tinggi card yang mungkin berbeda dari Users/Roles.
