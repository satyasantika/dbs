<x-filament-panels::page>
<div id="import-page" style="font-size:14px;max-width:900px;">

    {{-- ══════════════════════════════════════════════
         STEP 1 — PETUNJUK + AREA PASTE
    ══════════════════════════════════════════════ --}}
    <div id="step1">

        {{-- Cara pakai --}}
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;color:#15803d;margin:0 0 10px;font-size:15px;">Cara menggunakan Import Banyak</p>
            <ol style="margin:0;padding-left:22px;color:#166534;line-height:2;">
                <li>Buka data ujian di <strong>Google Sheets</strong> atau <strong>Excel</strong></li>
                <li>Pilih semua baris yang ingin diimpor — boleh ikut sertakan baris header, boleh juga tidak</li>
                <li>Tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 6px;font-size:12px;">Ctrl+C</kbd> untuk menyalin</li>
                <li>Klik area teks bertanda <em>"Paste data di sini"</em> di bawah</li>
                <li>Tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 6px;font-size:12px;">Ctrl+V</kbd> untuk menempel</li>
                <li>Klik tombol <strong>Tampilkan Preview</strong> — sistem akan menampilkan tabel hasil pembacaan</li>
                <li>Periksa tabel preview, lalu klik <strong>Simpan Data Valid</strong></li>
            </ol>
        </div>

        {{-- Referensi kolom --}}
        <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;margin:0 0 12px;color:#111827;font-size:15px;">Referensi Urutan Kolom Spreadsheet</p>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:600px;">
                    <thead>
                        <tr style="background:#1f2937;color:#fff;text-align:left;">
                            <th style="padding:6px 10px;">No</th>
                            <th style="padding:6px 10px;">Nama Kolom</th>
                            <th style="padding:6px 10px;">Alias Header yang Diterima</th>
                            <th style="padding:6px 10px;">Contoh Nilai</th>
                            <th style="padding:6px 10px;text-align:center;">Wajib</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $cols = [
                            [1,  'no',              'no, no.',                       '1',                            false],
                            [2,  'nama_mahasiswa',  'nama_mahasiswa',                'BUDI SANTOSO',                 false],
                            [3,  'E1 — Penguji 1',  'e1, penguji1',                 'DDN',                          false],
                            [4,  'E2 — Penguji 2',  'e2, penguji2',                 'RAT',                          false],
                            [5,  'E3 — Penguji 3',  'e3, penguji3',                 'LIN',                          false],
                            [6,  'G1 — Pembimbing 1','g1, pembimbing1',             'SIN',                          false],
                            [7,  'G2 — Pembimbing 2','g2, pembimbing2',             'MAD',                          false],
                            [8,  'jenis_ujian',     'jenis_ujian',                  'Seminar Hasil Penelitian',      true],
                            [9,  'ketua',           'ketua, ketua_penguji',         'DDN',                          false],
                            [10, 'tanggal_ujian',   'tanggal_ujian',                '19-Jun-2026',                  true],
                            [11, 'ruang',           'ruang',                        '1',                            true],
                            [12, 'waktu',           'waktu',                        '07.00 - 08.00',                true],
                            [13, 'npm',             'npm',                          '222151084',                    true],
                            [14, 'ipk',             'ipk',                          '3,68',                         false],
                            [15, 'judul',           'judul',                        'Efektivitas Integrasi...',     true],
                            [16, 'kontak',          'kontak',                       '085156778922',                 false],
                            [17, 'file_ujian',      'file_ujian',                   'https://drive.google.com/…',   false],
                            [18, 'meeting_id',      'meeting_id',                   '995 3668 7665',                false],
                            [19, 'passcode',        'passcode',                     'matematika',                   false],
                            [20, 'link_room',       'link_room',                    'https://zoom.us/j/…',          false],
                        ];
                        @endphp
                        @foreach($cols as [$no, $nama, $alias, $contoh, $wajib])
                        <tr style="{{ $loop->even ? 'background:#f9fafb;' : 'background:#fff;' }}">
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">{{ $no }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;color:#111827;">{{ $nama }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">{{ $alias }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#374151;">{{ $contoh }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;text-align:center;">
                                @if($wajib)
                                    <span style="color:#dc2626;font-weight:700;">✓</span>
                                @else
                                    <span style="color:#d1d5db;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @php $examTypes = \App\Models\ExamType::orderBy('id')->get(); @endphp
            <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e;line-height:1.8;">
                <strong>Catatan penting:</strong><br>
                • <strong>Kolom <code>jenis_ujian</code></strong> harus mengandung kata kunci yang cocok dengan nama jenis ujian di sistem.
                Nama yang terdaftar saat ini:
                <ul style="margin:2px 0 4px 16px;">
                    @foreach($examTypes as $et)
                        <li><strong>{{ $et->name }}</strong> <span style="color:#b45309;">(kode: {{ $et->code }})</span>
                        — kata kunci yang dikenali: setiap kata ≥5 huruf dari nama tersebut</li>
                    @endforeach
                </ul>
                Contoh yang <strong>diterima</strong>: "Ujian Hasil Penelitian", "Seminar Hasil", "semhas", "Hasil Penelitian".<br>
                • Penguji &amp; pembimbing diisi dengan <strong>inisial dosen</strong> (huruf kapital, contoh: DDN), bukan nama lengkap.<br>
                • NPM yang belum terdaftar akan <strong>otomatis dibuatkan akun</strong> dengan password = NPM dan email <em>npm@student.unsil.ac.id</em>.<br>
                • Kontak diisi nomor HP (boleh pakai awalan 0), sistem akan menyimpan tanpa awalan 0 untuk keperluan WhatsApp.<br>
                • Kolom yang tidak wajib boleh dikosongkan — sistem hanya membutuhkan 6 kolom bertanda ✓.
            </div>
        </div>

        {{-- Area paste --}}
        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:700;font-size:15px;color:#111827;margin-bottom:8px;">
                Area Paste Data
            </label>
            <textarea
                id="pasteArea"
                rows="9"
                placeholder="Paste data di sini (Ctrl+V)…&#10;&#10;Contoh 1 baris TANPA header:&#10;1&#9;WULAN SRI WAHYUNI&#9;DDN&#9;RAT&#9;LIN&#9;SIN&#9;MAD&#9;Seminar Hasil Penelitian&#9;DDN&#9;19-Jun-2026&#9;1&#9;07.00 - 08.00&#9;222151084&#9;3,68&#9;Judul Skripsi...&#9;085156778922&#9;&#9;995 3668 7665&#9;matematika&#9;https://zoom.us/j/..."
                style="width:100%;font-family:monospace;font-size:12px;border:2px solid #d1d5db;border-radius:8px;padding:12px;resize:vertical;background:#fff;color:#111827;line-height:1.5;"
            ></textarea>
            <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                <button
                    type="button"
                    onclick="doParse()"
                    style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;"
                >
                    Tampilkan Preview →
                </button>
                <span id="parseError" style="color:#dc2626;font-size:13px;display:none;"></span>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         STEP 2 — TABEL PREVIEW
    ══════════════════════════════════════════════ --}}
    <div id="step2" style="display:none;">

        <div id="summaryBanner" style="margin-bottom:12px;"></div>

        <div style="overflow:auto;max-height:420px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead id="previewHead" style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;white-space:nowrap;">#</th>
                        <th style="padding:7px 10px;white-space:nowrap;">NPM</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Nama</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Jenis Ujian</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Tanggal</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Ruang · Waktu</th>
                        <th style="padding:7px 10px;white-space:nowrap;min-width:220px;">Status / Tindakan</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>

        <div style="display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button
                type="button"
                onclick="goBack()"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;"
            >
                ← Paste Ulang
            </button>
            <button
                id="btnCommit"
                type="button"
                onclick="doCommit()"
                style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;"
            >
                Simpan Data Valid
            </button>
            <span id="commitNote" style="color:#6b7280;font-size:13px;"></span>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         STEP 3 — HASIL IMPORT
    ══════════════════════════════════════════════ --}}
    <div id="step3" style="display:none;">

        <div id="resultBanner" style="margin-bottom:12px;"></div>

        <div style="overflow:auto;max-height:420px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;width:50px;">#</th>
                        <th style="padding:7px 10px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="resultBody"></tbody>
            </table>
        </div>

        <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button
                type="button"
                onclick="resetAll()"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;"
            >
                ← Import Lagi
            </button>
            <a
                href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl('index') }}"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;"
            >
                Lihat Daftar Ujian
            </a>
        </div>

    </div>

    @script
    <script>
(function () {

    /* ── Definisi kolom ─────────────────────────────────────────────── */
    var POSITIONAL = [
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

    var HEADER_MAP = {
        'no': null, 'no.': null,
        'nama_mahasiswa': 'nama_mahasiswa',
        'e1': 'penguji1',    'penguji1': 'penguji1',
        'e2': 'penguji2',    'penguji2': 'penguji2',
        'e3': 'penguji3',    'penguji3': 'penguji3',
        'g1': 'pembimbing1', 'pembimbing1': 'pembimbing1',
        'g2': 'pembimbing2', 'pembimbing2': 'pembimbing2',
        'jenis_ujian': 'jenis_ujian',
        'ketua': 'ketua_penguji', 'ketua_penguji': 'ketua_penguji',
        'tanggal_ujian': 'tanggal_ujian',
        'ruang': 'ruang', 'waktu': 'waktu',
        'npm': 'npm', 'ipk': 'ipk', 'judul': 'judul', 'kontak': 'kontak',
        'file_ujian': 'file_ujian', 'status_publikasi_sinta': null,
        'meeting_id': 'meeting_id', 'passcode': 'passcode', 'link_room': 'link_room',
    };

    var REQUIRED = ['npm', 'jenis_ujian', 'tanggal_ujian', 'judul', 'ruang', 'waktu'];
    var parsedRows = [];
    var previewHasHeader = false;

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function resetCommitButton() {
        var btn = document.getElementById('btnCommit');
        if (!btn) return;
        btn.disabled      = false;
        btn.textContent   = 'Simpan Data Valid';
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
    }

    function setCommitButtonLoading(loading) {
        var btn = document.getElementById('btnCommit');
        if (!btn) return;
        if (loading) {
            btn.disabled      = true;
            btn.textContent   = 'Menyimpan…';
            btn.style.opacity = '0.7';
            btn.style.cursor  = 'not-allowed';
        } else {
            resetCommitButton();
        }
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '{{ csrf_token() }}';
    }

    function countSavableRows() {
        return parsedRows.filter(function (r) {
            if (r._invalid) return false;
            if (r._dupInfo && r._dupInfo.is_duplicate) {
                return r._duplicateAction === 'continue';
            }
            return r._duplicateAction !== 'cancel';
        }).length;
    }

    function countPendingDuplicates() {
        return parsedRows.filter(function (r) {
            return !r._invalid && r._dupInfo && r._dupInfo.is_duplicate && !r._duplicateAction;
        }).length;
    }

    function updateCommitState() {
        var savable   = countSavableRows();
        var pending   = countPendingDuplicates();
        var cancelled = parsedRows.filter(function (r) {
            return !r._invalid && r._duplicateAction === 'cancel';
        }).length;
        var noteEl    = document.getElementById('commitNote');
        var btnCommit = document.getElementById('btnCommit');

        if (pending > 0) {
            noteEl.textContent = pending + ' duplikat perlu dipilih: Lanjut atau Batalkan.';
            btnCommit.disabled = true;
            btnCommit.style.opacity = '0.5';
            btnCommit.style.cursor = 'not-allowed';
            return;
        }

        if (savable === 0) {
            noteEl.textContent = cancelled > 0
                ? 'Semua baris valid dibatalkan — tidak ada yang disimpan.'
                : 'Tidak ada baris valid untuk disimpan.';
            btnCommit.disabled = true;
            btnCommit.style.opacity = '0.5';
            btnCommit.style.cursor = 'not-allowed';
            return;
        }

        noteEl.textContent = savable + ' baris akan disimpan.'
            + (cancelled > 0 ? ' (' + cancelled + ' dibatalkan)' : '');
        btnCommit.disabled = false;
        btnCommit.style.opacity = '1';
        btnCommit.style.cursor = 'pointer';
    }

    function renderStatusCell(row) {
        if (row._invalid) {
            return '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">Kurang: '
                + escapeHtml(row._missing.join(', ')) + '</span>';
        }

        if (row._duplicateAction === 'cancel') {
            return '<span style="background:#f3f4f6;color:#6b7280;padding:2px 6px;border-radius:4px;font-size:11px;">Dibatalkan</span>'
                + ' <button type="button" onclick="setDuplicateAction(' + row._rowNum + ', null)"'
                + ' style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Ubah</button>';
        }

        if (row._dupInfo && row._dupInfo.is_duplicate) {
            var dup = row._dupInfo;

            if (row._duplicateAction === 'continue') {
                return '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 6px;border-radius:4px;font-size:11px;">Lanjut ujian ke-'
                    + row._registration_order + '</span>'
                    + ' <button type="button" onclick="setDuplicateAction(' + row._rowNum + ', null)"'
                    + ' style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Ubah</button>';
            }

            var html = '<div style="font-size:11px;color:#92400e;margin-bottom:6px;line-height:1.4;">'
                + escapeHtml(dup.message) + '</div><div style="display:flex;gap:6px;flex-wrap:wrap;">';

            if (dup.can_continue && dup.suggested_order) {
                html += '<button type="button" onclick="setDuplicateAction(' + row._rowNum + ', \'continue\')"'
                    + ' style="background:#2563eb;color:#fff;border:none;border-radius:4px;padding:4px 10px;font-size:11px;cursor:pointer;font-weight:600;">'
                    + 'Lanjut (ke-' + dup.suggested_order + ')</button>';
            } else {
                html += '<span style="color:#dc2626;font-size:11px;">Sudah 3× ujian — tidak bisa lanjut</span>';
            }

            html += '<button type="button" onclick="setDuplicateAction(' + row._rowNum + ', \'cancel\')"'
                + ' style="background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:4px;padding:4px 10px;font-size:11px;cursor:pointer;">Batalkan</button>';
            html += '</div>';

            return html;
        }

        return '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;">Siap</span>';
    }

    function renderPreview() {
        var rows       = parsedRows;
        var validCount = rows.filter(function (r) { return !r._invalid; }).length;
        var dupCount   = rows.filter(function (r) { return r._dupInfo && r._dupInfo.is_duplicate; }).length;
        var pending    = countPendingDuplicates();
        var modeTxt    = previewHasHeader ? 'mode: dengan header' : 'mode: tanpa header (posisi tetap)';
        var modeBg     = previewHasHeader ? '#dbeafe' : '#f3f4f6';
        var modeColor  = previewHasHeader ? '#1d4ed8' : '#374151';

        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">' +
            '<span style="background:' + modeBg + ';color:' + modeColor + ';padding:2px 8px;border-radius:4px;font-size:11px;">' + modeTxt + '</span>' +
            '<span><strong>' + rows.length + '</strong> baris terdeteksi — ' +
            '<strong style="color:#15803d">' + validCount + ' valid</strong>' +
            (rows.length - validCount > 0 ? ' · <span style="color:#dc2626">' + (rows.length - validCount) + ' perlu diperbaiki</span>' : '') +
            (dupCount > 0 ? ' · <span style="color:#d97706">' + dupCount + ' duplikat</span>' : '') +
            (pending > 0 ? ' · <strong style="color:#dc2626">' + pending + ' menunggu pilihan</strong>' : '') +
            '</span></div>';

        var tbody = document.getElementById('previewBody');
        tbody.innerHTML = '';

        rows.forEach(function (row) {
            var bg = row._invalid ? '#fef2f2'
                : (row._duplicateAction === 'cancel' ? '#f9fafb'
                : (row._dupInfo && row._dupInfo.is_duplicate && !row._duplicateAction ? '#fffbeb' : ''));
            var tr = document.createElement('tr');
            tr.style.background = bg;
            tr.innerHTML =
                '<td style="padding:5px 10px;color:#9ca3af;border-bottom:1px solid #f3f4f6;">' + row._rowNum + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.npm || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.nama_mahasiswa || '—') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.jenis_ujian || '-') + '</td>' +
                '<td style="padding:5px 10px;white-space:nowrap;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.tanggal_ujian || '-') + '</td>' +
                '<td style="padding:5px 10px;white-space:nowrap;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.ruang || '-') + (row.waktu ? ' · ' + escapeHtml(row.waktu) : '') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + renderStatusCell(row) + '</td>';
            tbody.appendChild(tr);
        });

        resetCommitButton();
        updateCommitState();
    }

    window.setDuplicateAction = function (rowNum, action) {
        var row = parsedRows.find(function (r) { return r._rowNum === rowNum; });
        if (!row) return;

        if (action === 'continue' && row._dupInfo && row._dupInfo.can_continue) {
            row._duplicateAction   = 'continue';
            row._registration_order = row._dupInfo.suggested_order;
        } else if (action === 'cancel') {
            row._duplicateAction    = 'cancel';
            row._registration_order = null;
        } else {
            row._duplicateAction    = null;
            row._registration_order = null;
        }

        renderPreview();
    };

    function fetchDuplicateChecks(rows) {
        var csrfToken = getCsrfToken();
        if (!csrfToken) {
            rows.forEach(function (row) {
                row._dupInfo = { is_duplicate: false };
            });
            renderPreview();
            return Promise.resolve();
        }

        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;font-size:13px;color:#6b7280;">Memeriksa duplikat di database…</div>';
        document.getElementById('previewBody').innerHTML = '';
        document.getElementById('commitNote').textContent = 'Menunggu pemeriksaan duplikat…';

        return fetch('{{ route('examregistrations.paste-import-check-duplicates') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ rows: rows }),
        })
        .then(function (res) {
            return parseJsonResponse(res).then(function (body) {
                if (!res.ok) {
                    throw new Error(body.message || ('HTTP ' + res.status));
                }
                return body;
            });
        })
        .then(function (data) {
            var checkMap = {};
            (data.checks || []).forEach(function (c) {
                checkMap[c.row] = c;
            });

            rows.forEach(function (row) {
                row._duplicateAction    = null;
                row._registration_order = null;
                row._dupInfo            = checkMap[row._rowNum] || { is_duplicate: false };
            });

            renderPreview();
        })
        .catch(function (e) {
            rows.forEach(function (row) {
                row._dupInfo = { is_duplicate: false };
            });
            renderPreview();
            document.getElementById('commitNote').textContent =
                'Pemeriksaan duplikat gagal: ' + e.message + '. Anda tetap bisa simpan tanpa info duplikat.';
        });
    }

    function splitCells(line) {
        if (line.indexOf('\t') !== -1) {
            return line.split('\t');
        }
        return line.trim().split(/\s{2,}/);
    }

    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            if (!text) {
                throw new Error('Respons server kosong (HTTP ' + res.status + ')');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Respons bukan JSON (HTTP ' + res.status + '): ' + text.slice(0, 300));
            }
        });
    }

    /* ── Parse ──────────────────────────────────────────────────────── */
    window.doParse = function () {
        var errEl = document.getElementById('parseError');
        errEl.style.display = 'none';
        errEl.textContent   = '';

        var raw = document.getElementById('pasteArea').value.trim();
        if (!raw) { showError('Belum ada data yang di-paste.'); return; }

        var lines = raw.split('\n').filter(function (l) { return l.trim() !== ''; });
        if (!lines.length) { showError('Data kosong.'); return; }

        var firstLineCells = splitCells(lines[0]);
        var firstCell = firstLineCells[0].trim();
        var hasHeader = isNaN(Number(firstCell)) || firstCell === '';

        var dataLines  = hasHeader ? lines.slice(1) : lines;
        var headerKeys = hasHeader
            ? firstLineCells.map(function (h) { return h.trim().toLowerCase().replace(/[\s.]+/g, '_'); })
            : null;

        var rows = dataLines.map(function (line, i) {
            var cells = splitCells(line);
            var row   = { _rowNum: hasHeader ? i + 2 : i + 1 };

            if (hasHeader) {
                headerKeys.forEach(function (key, idx) {
                    var field = HEADER_MAP[key];
                    if (field) row[field] = (cells[idx] || '').trim();
                });
            } else {
                POSITIONAL.forEach(function (field, idx) {
                    if (field) row[field] = (cells[idx] || '').trim();
                });
            }

            var missing  = REQUIRED.filter(function (f) { return !row[f]; });
            row._invalid = missing.length > 0;
            row._missing = missing;
            row._duplicateAction    = null;
            row._registration_order = null;
            row._dupInfo            = null;
            return row;
        }).filter(function (r) { return r.npm || r.nama_mahasiswa; });

        if (!rows.length) {
            showError('Tidak ada data yang terbaca. Pastikan kolom dipisah dengan TAB (bukan spasi).');
            return;
        }

        parsedRows = rows;
        previewHasHeader = hasHeader;

        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = '';

        fetchDuplicateChecks(rows);
    };

    /* ── Kembali ke step 1 ──────────────────────────────────────────── */
    window.goBack = function () {
        resetCommitButton();
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    /* ── Reset semua ────────────────────────────────────────────────── */
    window.resetAll = function () {
        parsedRows = [];
        resetCommitButton();
        document.getElementById('pasteArea').value = '';
        document.getElementById('previewBody').innerHTML = '';
        document.getElementById('resultBody').innerHTML = '';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    /* ── Simpan ─────────────────────────────────────────────────────── */
    window.doCommit = function () {
        if (countPendingDuplicates() > 0) {
            alert('Masih ada baris duplikat yang belum dipilih. Pilih Lanjut atau Batalkan untuk setiap duplikat.');
            return;
        }

        var validRows = parsedRows.filter(function (r) {
            if (r._invalid) return false;
            if (r._duplicateAction === 'cancel') return false;
            if (r._dupInfo && r._dupInfo.is_duplicate && r._duplicateAction !== 'continue') return false;
            return true;
        });

        if (!validRows.length) { alert('Tidak ada baris valid untuk disimpan.'); return; }

        var csrfToken = getCsrfToken();
        if (!csrfToken) {
            alert('CSRF token tidak ditemukan. Coba refresh halaman.');
            return;
        }

        setCommitButtonLoading(true);

        fetch('{{ route('examregistrations.paste-import') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ rows: parsedRows }),
        })
        .then(function (res) {
            return parseJsonResponse(res).then(function (body) {
                if (!res.ok) {
                    var msg = body.message || (body.errors ? Object.values(body.errors).flat().join(' ') : null) || ('HTTP ' + res.status);
                    throw new Error(msg);
                }
                return body;
            });
        })
        .then(function (data) {
            if (!data.results) {
                throw new Error('Respons server tidak valid.');
            }

            var results = data.results;
            var success = results.filter(function (r) { return r.status === 'success'; }).length;
            var skip    = results.filter(function (r) { return r.status === 'skip'; }).length;
            var error   = results.filter(function (r) { return r.status === 'error'; }).length;

            document.getElementById('resultBanner').innerHTML =
                '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;display:flex;gap:20px;flex-wrap:wrap;font-size:14px;">' +
                '<span style="color:#15803d;font-weight:700;">✓ ' + success + ' berhasil</span>' +
                '<span style="color:#d97706;font-weight:700;">⚠ ' + skip + ' dilewati (duplikat / batas ujian)</span>' +
                '<span style="color:#dc2626;font-weight:700;">✗ ' + error + ' gagal</span>' +
                '</div>';

            var tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            var icons  = { success: '✓', skip: '⚠', error: '✗' };
            var colors = { success: '#f0fdf4', skip: '#fffbeb', error: '#fef2f2' };
            results.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.style.background = colors[r.status] || '';
                tr.innerHTML =
                    '<td style="padding:5px 10px;color:#9ca3af;border-bottom:1px solid #f3f4f6;">' + r.row + '</td>' +
                    '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + (icons[r.status] || '') + ' ' + r.message + '</td>';
                tbody.appendChild(tr);
            });

            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = '';
        })
        .catch(function (e) {
            alert('Terjadi kesalahan: ' + e.message);
            setCommitButtonLoading(false);
        });
    };

    /* ── Helper ─────────────────────────────────────────────────────── */
    function showError(msg) {
        var el = document.getElementById('parseError');
        el.textContent  = msg;
        el.style.display = 'inline';
    }

})();
    </script>
    @endscript

</div>
</x-filament-panels::page>
