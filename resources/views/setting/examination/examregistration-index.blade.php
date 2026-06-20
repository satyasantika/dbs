@extends('layouts.app')

@push('title')
    {{ $title ?? 'Jadwal Ujian' }}
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    Setting > Jadwal Ujian
                    <button type="button" class="btn btn-success btn-sm float-end ms-2"
                            data-bs-toggle="modal" data-bs-target="#modalPasteImport">
                        <i class="bi bi-clipboard-data"></i> Import Paste
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-primary btn-sm float-end">kembali</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    {{ $dataTable->table() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODAL PASTE IMPORT
════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalPasteImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-data me-1"></i> Import Data Ujian (Copy-Paste)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- ── Step 1: Paste ─────────────────────────────────────── --}}
                <div id="stepPaste">
                    <p class="text-muted small mb-1">
                        Salin baris data dari spreadsheet (Excel / Google Sheets), lalu paste di bawah.
                        <br>Urutan kolom yang didukung (tanpa header):
                        <code>no · nama · E1 · E2 · E3 · G1 · G2 · jenis_ujian · ketua · tanggal · ruang · waktu · npm · ipk · judul · kontak · file · meeting_id · passcode · link_room</code>
                    </p>
                    <textarea id="pasteArea" class="form-control font-monospace"
                              rows="7" placeholder="Paste data di sini (Ctrl+V)…"></textarea>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button class="btn btn-primary" id="btnParse">
                            <i class="bi bi-table"></i> Tampilkan Preview →
                        </button>
                        <span id="parseError" class="text-danger small"></span>
                    </div>
                </div>

                {{-- ── Step 2: Preview ───────────────────────────────────── --}}
                <div id="stepPreview" style="display:none">
                    <div id="importSummary" class="mb-2"></div>
                    <div class="table-responsive" style="max-height:380px;overflow-y:auto">
                        <table class="table table-sm table-bordered align-middle" id="previewTable">
                            <thead class="table-dark" style="position:sticky;top:0;z-index:1">
                                <tr>
                                    <th>#</th>
                                    <th>NPM</th>
                                    <th>Nama</th>
                                    <th>Jenis Ujian</th>
                                    <th>Tanggal</th>
                                    <th>Ruang · Waktu</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="previewBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-secondary" id="btnReset">
                            <i class="bi bi-arrow-left"></i> Paste Ulang
                        </button>
                        <button class="btn btn-success" id="btnCommit">
                            <i class="bi bi-check2-all"></i> Simpan yang Valid
                        </button>
                    </div>
                </div>

                {{-- ── Step 3: Hasil ─────────────────────────────────────── --}}
                <div id="stepResult" style="display:none">
                    <div id="importResult"></div>
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-secondary" id="btnImportAgain">
                            <i class="bi bi-arrow-left"></i> Import Lagi
                        </button>
                        <button class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}

    <script>
    (function () {
        'use strict';

        // ── Definisi kolom ──────────────────────────────────────────────────
        // Urutan posisi tetap untuk paste TANPA header
        const POSITIONAL = [
            null,             // 0: no (diabaikan)
            'nama_mahasiswa', // 1
            'penguji1',       // 2: E1
            'penguji2',       // 3: E2
            'penguji3',       // 4: E3
            'pembimbing1',    // 5: G1
            'pembimbing2',    // 6: G2
            'jenis_ujian',    // 7
            'ketua_penguji',  // 8
            'tanggal_ujian',  // 9
            'ruang',          // 10
            'waktu',          // 11
            'npm',            // 12
            'ipk',            // 13
            'judul',          // 14
            'kontak',         // 15
            'file_ujian',     // 16
            'meeting_id',     // 17
            'passcode',       // 18
            'link_room',      // 19
        ];

        // Alias nama header untuk paste DENGAN header
        const HEADER_MAP = {
            no: null, 'no.': null,
            nama_mahasiswa: 'nama_mahasiswa',
            e1: 'penguji1',  penguji1: 'penguji1',
            e2: 'penguji2',  penguji2: 'penguji2',
            e3: 'penguji3',  penguji3: 'penguji3',
            g1: 'pembimbing1', pembimbing1: 'pembimbing1',
            g2: 'pembimbing2', pembimbing2: 'pembimbing2',
            jenis_ujian: 'jenis_ujian',
            ketua: 'ketua_penguji', ketua_penguji: 'ketua_penguji',
            tanggal_ujian: 'tanggal_ujian',
            ruang: 'ruang', waktu: 'waktu',
            npm: 'npm', ipk: 'ipk', judul: 'judul', kontak: 'kontak',
            file_ujian: 'file_ujian',
            status_publikasi_sinta: null,
            meeting_id: 'meeting_id', passcode: 'passcode', link_room: 'link_room',
        };

        const REQUIRED = ['npm', 'jenis_ujian', 'tanggal_ujian', 'judul', 'ruang', 'waktu'];

        let parsedRows = [];

        // ── Parser ──────────────────────────────────────────────────────────
        function parseRaw(raw) {
            const lines = raw.split('\n').filter(l => l.trim() !== '');
            if (!lines.length) return null;

            const firstCell = lines[0].split('\t')[0].trim();
            // Angka di kolom pertama = baris data (tanpa header)
            const hasHeader = isNaN(firstCell) || firstCell === '';

            const dataLines  = hasHeader ? lines.slice(1) : lines;
            const headerKeys = hasHeader
                ? lines[0].split('\t').map(h => h.trim().toLowerCase().replace(/[\s.]+/g, '_'))
                : null;

            const rows = dataLines.map((line, i) => {
                const cells = line.split('\t');
                const row   = { _rowNum: hasHeader ? i + 2 : i + 1 };

                if (hasHeader) {
                    headerKeys.forEach((key, idx) => {
                        const field = HEADER_MAP[key];
                        if (field) row[field] = (cells[idx] ?? '').trim();
                    });
                } else {
                    POSITIONAL.forEach((field, idx) => {
                        if (field) row[field] = (cells[idx] ?? '').trim();
                    });
                }
                return row;
            }).filter(r => r.npm || r.nama_mahasiswa);

            return { rows, hasHeader };
        }

        // ── Preview ─────────────────────────────────────────────────────────
        function renderPreview(rows, hasHeader) {
            const tbody = document.getElementById('previewBody');
            tbody.innerHTML = '';

            rows.forEach(row => {
                const missing = REQUIRED.filter(f => !row[f]);
                const badge   = missing.length
                    ? `<span class="badge bg-danger">Kurang: ${missing.join(', ')}</span>`
                    : `<span class="badge bg-success">Siap</span>`;

                const tr = document.createElement('tr');
                if (missing.length) tr.classList.add('table-danger');
                tr.innerHTML = `
                    <td class="text-muted">${row._rowNum}</td>
                    <td><code>${row.npm || '-'}</code></td>
                    <td class="small">${row.nama_mahasiswa || '<em class="text-muted">—</em>'}</td>
                    <td class="small">${row.jenis_ujian || '-'}</td>
                    <td class="small">${row.tanggal_ujian || '-'}</td>
                    <td class="small">${row.ruang || '-'} ${row.waktu ? '· ' + row.waktu : ''}</td>
                    <td>${badge}</td>`;
                tbody.appendChild(tr);
            });

            const validCount = rows.filter(r => !REQUIRED.some(f => !r[f])).length;
            const modeBadge  = hasHeader
                ? '<span class="badge bg-info me-1">mode: dengan header</span>'
                : '<span class="badge bg-secondary me-1">mode: tanpa header (posisi tetap)</span>';

            document.getElementById('importSummary').innerHTML = `
                <div class="alert alert-info py-2 mb-0">
                    ${modeBadge}
                    <strong>${rows.length}</strong> baris terdeteksi —
                    <strong class="text-success">${validCount} siap kirim</strong>
                    ${rows.length - validCount > 0 ? `· <span class="text-danger">${rows.length - validCount} perlu diperbaiki</span>` : ''}
                </div>`;
        }

        // ── Hasil ───────────────────────────────────────────────────────────
        function renderResult(results) {
            const success = results.filter(r => r.status === 'success').length;
            const skip    = results.filter(r => r.status === 'skip').length;
            const error   = results.filter(r => r.status === 'error').length;

            const rows = results.map(r => {
                const cls  = { success: 'table-success', skip: 'table-warning', error: 'table-danger' }[r.status];
                const icon = { success: '✓', skip: '⚠', error: '✗' }[r.status];
                return `<tr class="${cls}">
                    <td class="text-muted">${r.row}</td>
                    <td>${icon} ${r.message}</td>
                </tr>`;
            }).join('');

            document.getElementById('importResult').innerHTML = `
                <div class="alert alert-secondary py-2">
                    <strong class="text-success">${success}</strong> berhasil &nbsp;·&nbsp;
                    <strong class="text-warning">${skip}</strong> dilewati &nbsp;·&nbsp;
                    <strong class="text-danger">${error}</strong> gagal
                </div>
                <div class="table-responsive" style="max-height:360px;overflow-y:auto">
                    <table class="table table-sm table-bordered">
                        <thead class="table-dark">
                            <tr><th style="width:60px">#</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
        }

        // ── Reset ke step 1 ─────────────────────────────────────────────────
        function resetToStep1() {
            document.getElementById('pasteArea').value = '';
            document.getElementById('parseError').textContent = '';
            parsedRows = [];

            document.getElementById('stepPaste').style.display   = '';
            document.getElementById('stepPreview').style.display = 'none';
            document.getElementById('stepResult').style.display  = 'none';

            const btn = document.getElementById('btnCommit');
            btn.disabled    = false;
            btn.textContent = '';
            btn.innerHTML   = '<i class="bi bi-check2-all"></i> Simpan yang Valid';
        }

        // ── Event: Parse ────────────────────────────────────────────────────
        document.getElementById('btnParse').addEventListener('click', () => {
            const raw = document.getElementById('pasteArea').value.trim();
            document.getElementById('parseError').textContent = '';

            if (!raw) {
                document.getElementById('parseError').textContent = 'Belum ada data yang di-paste.';
                return;
            }

            const result = parseRaw(raw);
            if (!result || !result.rows.length) {
                document.getElementById('parseError').textContent =
                    'Tidak ada data yang terbaca. Pastikan data dipisah dengan TAB.';
                return;
            }

            parsedRows = result.rows;
            renderPreview(parsedRows, result.hasHeader);

            document.getElementById('stepPaste').style.display   = 'none';
            document.getElementById('stepPreview').style.display = '';
        });

        // ── Event: Simpan ───────────────────────────────────────────────────
        document.getElementById('btnCommit').addEventListener('click', async () => {
            const btn = document.getElementById('btnCommit');
            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';

            try {
                const res = await fetch('{{ route('examregistrations.paste-import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ rows: parsedRows }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || `HTTP ${res.status}`);
                }

                const data = await res.json();
                renderResult(data.results);

                document.getElementById('stepPreview').style.display = 'none';
                document.getElementById('stepResult').style.display  = '';

                // Reload DataTable jika ada yang berhasil
                if (data.results.some(r => r.status === 'success')) {
                    if (window.LaravelDataTables && window.LaravelDataTables['examregistrations-table']) {
                        window.LaravelDataTables['examregistrations-table'].ajax.reload(null, false);
                    }
                }
            } catch (e) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-check2-all"></i> Simpan yang Valid';
                alert('Terjadi kesalahan: ' + e.message);
            }
        });

        // ── Event: Reset ────────────────────────────────────────────────────
        document.getElementById('btnReset').addEventListener('click', resetToStep1);
        document.getElementById('btnImportAgain').addEventListener('click', resetToStep1);

        // Reset modal setiap kali ditutup
        document.getElementById('modalPasteImport').addEventListener('hidden.bs.modal', resetToStep1);

    })();
    </script>
@endpush
