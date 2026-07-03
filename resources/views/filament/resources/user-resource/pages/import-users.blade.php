<x-filament-panels::page>
<div id="import-page" wire:ignore style="font-size:14px;max-width:900px;">

    {{-- STEP 1 — PETUNJUK + AREA PASTE --}}
    <div id="step1">

        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;color:#15803d;margin:0 0 10px;font-size:15px;">Cara menggunakan Import Banyak</p>
            <ol style="margin:0;padding-left:22px;color:#166534;line-height:2;">
                <li>Buka data pengguna di <strong>Google Sheets</strong> atau <strong>Excel</strong></li>
                <li>Pilih semua baris yang ingin diimpor — boleh ikut sertakan baris header, boleh juga tidak</li>
                <li>Tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 6px;font-size:12px;">Ctrl+C</kbd> untuk menyalin</li>
                <li>Klik area teks bertanda <em>"Paste data di sini"</em> di bawah</li>
                <li>Tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 6px;font-size:12px;">Ctrl+V</kbd> untuk menempel</li>
                <li>Klik tombol <strong>Tampilkan Preview</strong> — sistem akan menampilkan tabel hasil pembacaan</li>
                <li>Periksa tabel preview, lalu klik <strong>Simpan Data Valid</strong></li>
            </ol>
        </div>

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
                            [1, 'no',       'no, no.',                 '1',              false],
                            [2, 'nama',     'nama, name, nama_lengkap', 'Budi Santoso',   true],
                            [3, 'username', 'username, nim',           'budi123',        true],
                            [4, 'password', 'password, pass',          'rahasia123',     'Wajib untuk user baru'],
                            [5, 'email',    'email',                   'budi@mail.com',  true],
                            [6, 'role',     'role, peran',             'mahasiswa',      true],
                        ];
                        @endphp
                        @foreach($cols as [$no, $nama, $alias, $contoh, $wajib])
                        <tr style="{{ $loop->even ? 'background:#f9fafb;' : 'background:#fff;' }}">
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">{{ $no }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;color:#111827;">{{ $nama }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">{{ $alias }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#374151;">{{ $contoh }}</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;text-align:center;">
                                @if($wajib === true)
                                    <span style="color:#dc2626;font-weight:700;">✓</span>
                                @elseif($wajib === false)
                                    <span style="color:#d1d5db;">—</span>
                                @else
                                    <span style="color:#d97706;font-size:11px;">{{ $wajib }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e;line-height:1.8;">
                <strong>Catatan penting:</strong><br>
                • Kolom <strong>role</strong> harus persis sama dengan nama role di sistem (contoh: mahasiswa, dosen, dbs, kajur, manajer nuir, validator nuir) — tidak peka huruf besar/kecil.<br>
                • Kolom <strong>username</strong> dipakai untuk mendeteksi duplikat — jika sudah ada, baris akan <strong>diperbarui otomatis</strong> saat simpan (klik Batalkan jika tidak ingin mengubah).<br>
                • Kolom <strong>password</strong> wajib diisi untuk user baru; untuk user yang sudah ada, kosongkan agar password lama tidak berubah.<br>
                • Password disimpan langsung ke sistem — tidak ditampilkan lagi setelah disimpan.
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:700;font-size:15px;color:#111827;margin-bottom:8px;">
                Area Paste Data
            </label>
            <textarea
                id="pasteArea"
                rows="9"
                placeholder="Paste data di sini (Ctrl+V)…&#10;&#10;Contoh 1 baris TANPA header:&#10;1&#9;Budi Santoso&#9;budi123&#9;rahasia123&#9;budi@mail.com&#9;mahasiswa&#10;&#10;Contoh 1 baris DENGAN header:&#10;nama&#9;username&#9;password&#9;email&#9;role&#10;Budi Santoso&#9;budi123&#9;rahasia123&#9;budi@mail.com&#9;mahasiswa"
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

    {{-- STEP 2 — TABEL PREVIEW --}}
    <div id="step2" style="display:none;">

        <div id="summaryBanner" style="margin-bottom:12px;"></div>

        <div style="overflow:auto;max-height:420px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead id="previewHead" style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;white-space:nowrap;">#</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Nama</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Username</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Email</th>
                        <th style="padding:7px 10px;white-space:nowrap;">Role</th>
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

    {{-- STEP 3 — HASIL IMPORT --}}
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
                href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;"
            >
                Lihat Daftar Pengguna
            </a>
        </div>

    </div>

    @script
    <script>
(function () {

    var STATE = window.userPasteImport = window.userPasteImport || {};
    if (!Array.isArray(STATE.parsedRows)) {
        STATE.parsedRows = [];
    }
    if (typeof STATE.previewHasHeader !== 'boolean') {
        STATE.previewHasHeader = false;
    }

    var POSITIONAL_FIELDS = ['nama', 'username', 'password', 'email', 'role'];

    var HEADER_MAP = {
        'no': null, 'no.': null,
        'nama': 'nama', 'name': 'nama', 'nama_lengkap': 'nama',
        'username': 'username', 'nim': 'username',
        'password': 'password', 'pass': 'password',
        'email': 'email',
        'role': 'role', 'peran': 'role',
    };

    var REQUIRED = ['nama', 'username', 'email', 'role'];

    function getParsedRows() {
        return STATE.parsedRows;
    }

    function setParsedRows(rows) {
        STATE.parsedRows = rows;
    }

    function rowKey(rowNum) {
        return String(rowNum);
    }

    function findRow(rowNum) {
        var key = rowKey(rowNum);
        return getParsedRows().find(function (r) {
            return rowKey(r._rowNum) === key;
        });
    }

    function mapPositionalCells(cells) {
        var row = {};
        var first = (cells[0] || '').trim();
        var offset = (/^\d+$/.test(first) && cells.length > POSITIONAL_FIELDS.length) ? 1 : 0;

        POSITIONAL_FIELDS.forEach(function (field, idx) {
            row[field] = (cells[idx + offset] || '').trim();
        });

        return row;
    }

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
        if (meta && meta.content) {
            return meta.content;
        }

        var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        if (match) {
            return decodeURIComponent(match[1]);
        }

        return '{{ csrf_token() }}';
    }

    function countSavableRows() {
        return getParsedRows().filter(function (r) {
            if (r._invalid) return false;
            return r._duplicateAction !== 'cancel';
        }).length;
    }

    function countPendingDuplicates() {
        return 0;
    }

    function updateCommitState() {
        var savable   = countSavableRows();
        var pending   = countPendingDuplicates();
        var cancelled = getParsedRows().filter(function (r) {
            return !r._invalid && r._duplicateAction === 'cancel';
        }).length;
        var noteEl    = document.getElementById('commitNote');
        var btnCommit = document.getElementById('btnCommit');

        if (pending > 0) {
            noteEl.textContent = pending + ' baris sudah ada — pilih Perbarui atau Batalkan.';
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

        if (row._dupInfo && row._dupInfo.is_duplicate) {
            if (row._duplicateAction === 'cancel') {
                return '<span style="background:#f3f4f6;color:#6b7280;padding:2px 6px;border-radius:4px;font-size:11px;">Dibatalkan</span>'
                    + ' <button type="button" onclick="setDuplicateAction(' + row._rowNum + ', null)"'
                    + ' style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Ubah</button>';
            }

            return '<div style="font-size:11px;color:#92400e;margin-bottom:4px;line-height:1.4;">'
                + escapeHtml(row._dupInfo.message) + '</div>'
                + '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 6px;border-radius:4px;font-size:11px;">Akan diperbarui saat simpan</span>'
                + ' <button type="button" onclick="setDuplicateAction(' + row._rowNum + ', \'cancel\')"'
                + ' style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Batalkan</button>';
        }

        return '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;">Siap</span>';
    }

    function renderPreview() {
        var rows       = getParsedRows();
        var validCount = rows.filter(function (r) { return !r._invalid; }).length;
        var dupCount   = rows.filter(function (r) { return r._dupInfo && r._dupInfo.is_duplicate; }).length;
        var pending    = countPendingDuplicates();
        var modeTxt    = STATE.previewHasHeader ? 'mode: dengan header' : 'mode: tanpa header (posisi tetap)';
        var modeBg     = STATE.previewHasHeader ? '#dbeafe' : '#f3f4f6';
        var modeColor  = STATE.previewHasHeader ? '#1d4ed8' : '#374151';

        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">' +
            '<span style="background:' + modeBg + ';color:' + modeColor + ';padding:2px 8px;border-radius:4px;font-size:11px;">' + modeTxt + '</span>' +
            '<span><strong>' + rows.length + '</strong> baris terdeteksi — ' +
            '<strong style="color:#15803d">' + validCount + ' valid</strong>' +
            (rows.length - validCount > 0 ? ' · <span style="color:#dc2626">' + (rows.length - validCount) + ' perlu diperbaiki</span>' : '') +
            (dupCount > 0 ? ' · <span style="color:#d97706">' + dupCount + ' sudah ada</span>' : '') +
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
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.nama || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.username || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.email || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.role || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + renderStatusCell(row) + '</td>';
            tbody.appendChild(tr);
        });

        resetCommitButton();
        updateCommitState();
    }

    window.setDuplicateAction = function (rowNum, action) {
        var row = findRow(rowNum);
        if (!row) return;

        row._duplicateAction = action || null;
        renderPreview();
    };

    function setCommitDisabled(disabled, note) {
        var btnCommit = document.getElementById('btnCommit');
        var noteEl = document.getElementById('commitNote');
        if (!btnCommit) return;

        btnCommit.disabled = disabled;
        btnCommit.style.opacity = disabled ? '0.5' : '1';
        btnCommit.style.cursor = disabled ? 'not-allowed' : 'pointer';

        if (note !== undefined && noteEl) {
            noteEl.textContent = note;
        }
    }

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
            '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;font-size:13px;color:#6b7280;">Memeriksa data yang sudah ada…</div>';
        document.getElementById('previewBody').innerHTML = '';
        setCommitDisabled(true, 'Menunggu pemeriksaan…');

        return fetch('{{ route('users.paste-import-check-duplicates') }}', {
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
                checkMap[rowKey(c.row)] = c;
            });

            rows.forEach(function (row) {
                row._duplicateAction = null;
                row._dupInfo = checkMap[rowKey(row._rowNum)] || { is_duplicate: false };
            });

            setParsedRows(rows);
            renderPreview();
        })
        .catch(function (e) {
            rows.forEach(function (row) {
                row._dupInfo = { is_duplicate: false };
            });
            setParsedRows(rows);
            renderPreview();
            document.getElementById('commitNote').textContent =
                'Pemeriksaan gagal: ' + e.message + '. Anda tetap bisa simpan tanpa info duplikat.';
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

    function isHeaderRow(firstCell) {
        var key = firstCell.trim().toLowerCase().replace(/[\s.]+/g, '_');
        return HEADER_MAP.hasOwnProperty(key);
    }

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
        var hasHeader = isHeaderRow(firstCell);

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
                Object.assign(row, mapPositionalCells(cells));
            }

            var missing  = REQUIRED.filter(function (f) { return row[f] === undefined || row[f] === null || String(row[f]).trim() === ''; });
            row._invalid = missing.length > 0;
            row._missing = missing;
            row._duplicateAction = null;
            row._dupInfo = null;
            return row;
        }).filter(function (r) { return r.nama || r.username; });

        if (!rows.length) {
            showError('Tidak ada data yang terbaca. Pastikan kolom dipisah dengan TAB (bukan spasi).');
            return;
        }

        setParsedRows(rows);
        STATE.previewHasHeader = hasHeader;

        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = '';

        fetchDuplicateChecks(rows);
    };

    window.goBack = function () {
        resetCommitButton();
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    window.resetAll = function () {
        setParsedRows([]);
        STATE.previewHasHeader = false;
        resetCommitButton();
        document.getElementById('pasteArea').value = '';
        document.getElementById('previewBody').innerHTML = '';
        document.getElementById('resultBody').innerHTML = '';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    window.doCommit = function () {
        if (countPendingDuplicates() > 0) {
            alert('Masih ada baris yang sudah ada di database. Pilih Perbarui atau Batalkan untuk setiap baris.');
            return;
        }

        var validRows = getParsedRows().filter(function (r) {
            if (r._invalid) return false;
            if (r._duplicateAction === 'cancel') return false;
            return true;
        });

        if (!validRows.length) { alert('Tidak ada baris valid untuk disimpan.'); return; }

        var csrfToken = getCsrfToken();
        if (!csrfToken) {
            alert('CSRF token tidak ditemukan. Coba refresh halaman.');
            return;
        }

        setCommitButtonLoading(true);

        fetch('{{ route('users.paste-import') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ rows: validRows }),
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
                '<span style="color:#d97706;font-weight:700;">⚠ ' + skip + ' dilewati</span>' +
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
