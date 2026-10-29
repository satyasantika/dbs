<x-filament-panels::page>
<div id="import-guideexaminer-page" wire:ignore style="font-size:14px;max-width:1100px;">

    <div id="step1">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;color:#15803d;margin:0 0 10px;font-size:15px;">Cara import pembimbing &amp; penguji</p>
            <ol style="margin:0;padding-left:22px;color:#166534;line-height:2;">
                <li>Siapkan sheet Excel/Google Sheets dengan urutan kolom <strong>persis</strong> seperti di bawah — <strong>tanpa header pun boleh, langsung paste apa adanya</strong></li>
                <li>Salin baris datanya, lalu tempel di area bawah</li>
                <li>Klik <strong>Tampilkan Preview</strong> untuk memeriksa NPM dan kode dosen sebelum disimpan</li>
                <li>NPM yang belum ada otomatis didaftarkan sebagai mahasiswa baru (pakai kolom nama_mahasiswa)</li>
                <li>Mahasiswa yang sudah lulus (ada tanggal sidang) otomatis dilewati — datanya tidak diubah</li>
            </ol>
        </div>

        <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;margin:0 0 12px;color:#111827;font-size:15px;">Urutan kolom (tanpa header)</p>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:#1f2937;color:#fff;text-align:left;">
                            <th style="padding:6px 10px;">No</th>
                            <th style="padding:6px 10px;">Kolom</th>
                            <th style="padding:6px 10px;">Isi</th>
                            <th style="padding:6px 10px;">Contoh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">1</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;">npm</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">NPM mahasiswa</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">232151001</td>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <td style="padding:5px 10px;color:#9ca3af;text-align:center;">2</td>
                            <td style="padding:5px 10px;font-family:monospace;">nama_mahasiswa</td>
                            <td style="padding:5px 10px;color:#6b7280;">Nama lengkap (dipakai jika NPM belum terdaftar)</td>
                            <td style="padding:5px 10px;">FITRI KOMALASARI</td>
                        </tr>
                        <tr>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">3</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;">penguji_1</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Kode inisial dosen (boleh kosong)</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">RAT</td>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <td style="padding:5px 10px;color:#9ca3af;text-align:center;">4</td>
                            <td style="padding:5px 10px;font-family:monospace;">penguji_2</td>
                            <td style="padding:5px 10px;color:#6b7280;">Kode inisial dosen (boleh kosong)</td>
                            <td style="padding:5px 10px;">DPA</td>
                        </tr>
                        <tr>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">5</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;">penguji_3</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Kode inisial dosen (boleh kosong)</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">SUK</td>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <td style="padding:5px 10px;color:#9ca3af;text-align:center;">6</td>
                            <td style="padding:5px 10px;font-family:monospace;">pembimbing_1</td>
                            <td style="padding:5px 10px;color:#6b7280;">Kode inisial dosen (boleh kosong)</td>
                            <td style="padding:5px 10px;">HET</td>
                        </tr>
                        <tr>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">7</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;">pembimbing_2</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Kode inisial dosen (boleh kosong)</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">RED</td>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <td style="padding:5px 10px;color:#9ca3af;text-align:center;">8</td>
                            <td style="padding:5px 10px;font-family:monospace;">tahun_angkatan</td>
                            <td style="padding:5px 10px;color:#6b7280;">4 digit tahun angkatan</td>
                            <td style="padding:5px 10px;">2023</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e;line-height:1.7;">
                Kolom kode dosen yang dikosongkan akan mengosongkan slot itu juga di sistem (replace penuh, bukan tempel sebagian). Kode dicocokkan ke field "Inisial" dosen — pastikan sudah diisi di data dosen.
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:700;font-size:15px;color:#111827;margin-bottom:8px;">Area Paste Data</label>
            <textarea
                id="pasteArea"
                rows="10"
                placeholder="Paste data di sini (Ctrl+V)…&#10;&#10;Contoh tanpa header:&#10;232151001&#9;FITRI KOMALASARI&#9;RAT&#9;DPA&#9;SUK&#9;HET&#9;RED&#9;2023"
                style="width:100%;font-family:monospace;font-size:12px;border:2px solid #d1d5db;border-radius:8px;padding:12px;resize:vertical;background:#fff;color:#111827;line-height:1.5;"
            ></textarea>
            <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                <button type="button" onclick="doParseGE()" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">
                    Tampilkan Preview →
                </button>
                <span id="parseErrorGE" style="color:#dc2626;font-size:13px;display:none;"></span>
            </div>
        </div>
    </div>

    <div id="step2" style="display:none;">
        <div id="summaryBannerGE" style="margin-bottom:12px;"></div>
        <div style="overflow:auto;max-height:460px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;">#</th>
                        <th style="padding:7px 10px;">NPM</th>
                        <th style="padding:7px 10px;">Nama</th>
                        <th style="padding:7px 10px;">Penguji 1</th>
                        <th style="padding:7px 10px;">Penguji 2</th>
                        <th style="padding:7px 10px;">Penguji 3</th>
                        <th style="padding:7px 10px;">Pembimbing 1</th>
                        <th style="padding:7px 10px;">Pembimbing 2</th>
                        <th style="padding:7px 10px;">Angkatan</th>
                        <th style="padding:7px 10px;">Status</th>
                    </tr>
                </thead>
                <tbody id="previewBodyGE"></tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button type="button" onclick="goBackGE()" style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;">← Paste Ulang</button>
            <button id="btnCommitGE" type="button" onclick="doCommitGE()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">Simpan Baris Valid</button>
            <span id="commitNoteGE" style="color:#6b7280;font-size:13px;"></span>
        </div>
    </div>

    <div id="step3" style="display:none;">
        <div id="resultBannerGE" style="margin-bottom:12px;"></div>
        <div style="overflow:auto;max-height:460px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;width:50px;">#</th>
                        <th style="padding:7px 10px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="resultBodyGE"></tbody>
            </table>
        </div>
        <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button type="button" onclick="resetAllGE()" style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;">← Import Lagi</button>
            <a href="{{ \App\Filament\Resources\GuideExaminerResource::getUrl('index') }}" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">Lihat Daftar</a>
        </div>
    </div>

    @script
    <script>
(function () {
    var STATE = window.guideExaminerPasteImport = window.guideExaminerPasteImport || { parsedRows: [] };
    if (!Array.isArray(STATE.parsedRows)) STATE.parsedRows = [];

    var POSITIONAL_FIELDS = ['npm', 'nama_mahasiswa', 'penguji_1', 'penguji_2', 'penguji_3', 'pembimbing_1', 'pembimbing_2', 'tahun_angkatan'];
    var HEADER_MAP = {
        'no': null, 'no.': null,
        'npm': 'npm', 'nim': 'npm', 'username': 'npm',
        'nama_mahasiswa': 'nama_mahasiswa', 'nama': 'nama_mahasiswa', 'nama_siswa': 'nama_mahasiswa',
        'penguji_1': 'penguji_1', 'penguji1': 'penguji_1',
        'penguji_2': 'penguji_2', 'penguji2': 'penguji_2',
        'penguji_3': 'penguji_3', 'penguji3': 'penguji_3',
        'pembimbing_1': 'pembimbing_1', 'pembimbing1': 'pembimbing_1',
        'pembimbing_2': 'pembimbing_2', 'pembimbing2': 'pembimbing_2',
        'tahun_angkatan': 'tahun_angkatan', 'angkatan': 'tahun_angkatan', 'tahun': 'tahun_angkatan'
    };
    var SLOT_FIELDS = [
        ['penguji_1', 'Penguji 1'],
        ['penguji_2', 'Penguji 2'],
        ['penguji_3', 'Penguji 3'],
        ['pembimbing_1', 'Pembimbing 1'],
        ['pembimbing_2', 'Pembimbing 2']
    ];

    function getParsedRows() { return STATE.parsedRows; }
    function setParsedRows(rows) { STATE.parsedRows = rows; }
    function rowKey(rowNum) { return String(rowNum); }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        if (match) return decodeURIComponent(match[1]);
        return '{{ csrf_token() }}';
    }

    function splitCells(line) {
        if (line.indexOf('\t') !== -1) return line.split('\t');
        return line.trim().split(/\s{2,}/);
    }

    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            if (!text) throw new Error('Respons server kosong (HTTP ' + res.status + ')');
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Respons bukan JSON (HTTP ' + res.status + '): ' + text.slice(0, 300)); }
        });
    }

    function mapPositionalCells(cells) {
        var row = {};
        POSITIONAL_FIELDS.forEach(function (field, idx) {
            row[field] = (cells[idx] || '').trim();
        });
        return row;
    }

    function isHeaderRow(firstCell) {
        var key = firstCell.trim().toLowerCase().replace(/[\s.]+/g, '_');
        return HEADER_MAP.hasOwnProperty(key);
    }

    function resetCommitButton() {
        var btn = document.getElementById('btnCommitGE');
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = 'Simpan Baris Valid';
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    function setCommitButtonLoading(loading) {
        var btn = document.getElementById('btnCommitGE');
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.textContent = 'Menyimpan…';
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        } else {
            resetCommitButton();
        }
    }

    function countSavable() {
        return getParsedRows().filter(function (r) { return !r._invalid; }).length;
    }

    function updateCommitState() {
        var savable = countSavable();
        var noteEl = document.getElementById('commitNoteGE');
        var btn = document.getElementById('btnCommitGE');
        if (savable === 0) {
            noteEl.textContent = 'Tidak ada baris valid untuk disimpan.';
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            return;
        }
        noteEl.textContent = savable + ' baris akan disimpan.';
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    function slotCell(row, field) {
        var info = (row._slots || {})[field];
        if (!info) return '<span style="color:#9ca3af;">—</span>';
        if (info.found) return escapeHtml(info.name);
        return '<span style="color:#dc2626;font-weight:600;" title="Kode tidak ditemukan">' + escapeHtml(info.code) + ' ✗</span>';
    }

    function renderPreview() {
        var rows = getParsedRows();
        var validCount = rows.filter(function (r) { return !r._invalid; }).length;
        var newCount = rows.filter(function (r) { return r._studentIsNew; }).length;
        document.getElementById('summaryBannerGE').innerHTML =
            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;font-size:13px;">' +
            '<strong>' + rows.length + '</strong> baris — <strong style="color:#15803d">' + validCount + ' valid</strong>' +
            (rows.length - validCount > 0 ? ' · <span style="color:#dc2626">' + (rows.length - validCount) + ' dilewati</span>' : '') +
            (newCount > 0 ? ' · <span style="color:#b45309">' + newCount + ' mahasiswa baru</span>' : '') +
            '</div>';

        var tbody = document.getElementById('previewBodyGE');
        tbody.innerHTML = '';
        rows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.style.background = row._invalid ? '#fef2f2' : '';
            var namaBadge = row._studentIsNew
                ? ' <span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;font-size:10px;">baru</span>'
                : '';
            var status = row._invalid
                ? '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">' + escapeHtml(row._reason || 'Tidak valid') + '</span>'
                : '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;">Akan disimpan</span>';
            tr.innerHTML =
                '<td style="padding:5px 10px;color:#9ca3af;border-bottom:1px solid #f3f4f6;">' + row._rowNum + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.npm || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row._studentName || '—') + namaBadge + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + slotCell(row, 'penguji_1') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + slotCell(row, 'penguji_2') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + slotCell(row, 'penguji_3') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + slotCell(row, 'pembimbing_1') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + slotCell(row, 'pembimbing_2') + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.tahun_angkatan || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + status + '</td>';
            tbody.appendChild(tr);
        });
        resetCommitButton();
        updateCommitState();
    }

    function fetchChecks(rows) {
        var csrfToken = getCsrfToken();
        document.getElementById('summaryBannerGE').innerHTML =
            '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;font-size:13px;color:#6b7280;">Memeriksa NPM &amp; kode dosen…</div>';
        document.getElementById('previewBodyGE').innerHTML = '';

        return fetch('{{ route('guideexaminers.paste-import-check') }}', {
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
                if (!res.ok) throw new Error(body.message || ('HTTP ' + res.status));
                return body;
            });
        })
        .then(function (data) {
            var checkMap = {};
            (data.checks || []).forEach(function (c) { checkMap[rowKey(c.row)] = c; });
            rows.forEach(function (row) {
                var info = checkMap[rowKey(row._rowNum)] || {};
                row._studentName = info.student_name || null;
                row._studentIsNew = !!info.student_is_new;
                row._slots = info.slots || {};
                if (info.invalid) {
                    row._invalid = true;
                    row._reason = info.message || 'Tidak valid';
                } else {
                    row._invalid = false;
                    row._reason = null;
                }
            });
            setParsedRows(rows);
            renderPreview();
        })
        .catch(function (e) {
            rows.forEach(function (row) {
                row._invalid = true;
                row._reason = 'Pemeriksaan gagal: ' + e.message;
            });
            setParsedRows(rows);
            renderPreview();
        });
    }

    window.doParseGE = function () {
        var errEl = document.getElementById('parseErrorGE');
        errEl.style.display = 'none';
        errEl.textContent = '';

        var raw = document.getElementById('pasteArea').value.trim();
        if (!raw) { showError('Belum ada data yang di-paste.'); return; }

        var lines = raw.split('\n').filter(function (l) { return l.trim() !== ''; });
        var firstLineCells = splitCells(lines[0]);
        var hasHeader = isHeaderRow(firstLineCells[0]);
        var dataLines = hasHeader ? lines.slice(1) : lines;
        var headerKeys = hasHeader
            ? firstLineCells.map(function (h) { return h.trim().toLowerCase().replace(/[\s.]+/g, '_'); })
            : null;

        var rows = dataLines.map(function (line, i) {
            var cells = splitCells(line);
            var row = { _rowNum: hasHeader ? i + 2 : i + 1 };
            if (hasHeader) {
                headerKeys.forEach(function (key, idx) {
                    var field = HEADER_MAP[key];
                    if (field) row[field] = (cells[idx] || '').trim();
                });
            } else {
                Object.assign(row, mapPositionalCells(cells));
            }
            return row;
        }).filter(function (r) { return r.npm; });

        if (!rows.length) {
            showError('Tidak ada data yang terbaca. Pastikan kolom dipisah TAB (hasil copy dari Excel/Sheets).');
            return;
        }

        setParsedRows(rows);
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = '';
        fetchChecks(rows);
    };

    window.goBackGE = function () {
        resetCommitButton();
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    window.resetAllGE = function () {
        setParsedRows([]);
        resetCommitButton();
        document.getElementById('pasteArea').value = '';
        document.getElementById('previewBodyGE').innerHTML = '';
        document.getElementById('resultBodyGE').innerHTML = '';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step1').style.display = '';
    };

    window.doCommitGE = function () {
        var validRows = getParsedRows().filter(function (r) { return !r._invalid; });
        if (!validRows.length) { alert('Tidak ada baris valid untuk disimpan.'); return; }

        var csrfToken = getCsrfToken();
        if (!csrfToken) { alert('CSRF token tidak ditemukan. Coba refresh halaman.'); return; }

        setCommitButtonLoading(true);

        fetch('{{ route('guideexaminers.paste-import') }}', {
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
                if (!res.ok) throw new Error(body.message || ('HTTP ' + res.status));
                return body;
            });
        })
        .then(function (data) {
            if (!data.results) throw new Error('Respons server tidak valid.');
            var results = data.results;
            var success = results.filter(function (r) { return r.status === 'success'; }).length;
            var skip = results.filter(function (r) { return r.status === 'skip'; }).length;
            var error = results.filter(function (r) { return r.status === 'error'; }).length;
            document.getElementById('resultBannerGE').innerHTML =
                '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;display:flex;gap:20px;font-size:14px;">' +
                '<span style="color:#15803d;font-weight:700;">✓ ' + success + ' berhasil</span>' +
                '<span style="color:#b45309;font-weight:700;">⏭ ' + skip + ' dilewati</span>' +
                '<span style="color:#dc2626;font-weight:700;">✗ ' + error + ' gagal</span></div>';
            var tbody = document.getElementById('resultBodyGE');
            tbody.innerHTML = '';
            results.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.style.background = r.status === 'success' ? '#f0fdf4' : (r.status === 'skip' ? '#fffbeb' : '#fef2f2');
                tr.innerHTML =
                    '<td style="padding:5px 10px;color:#9ca3af;border-bottom:1px solid #f3f4f6;">' + r.row + '</td>' +
                    '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(r.message) + '</td>';
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
        var el = document.getElementById('parseErrorGE');
        el.textContent = msg;
        el.style.display = 'inline';
    }
})();
    </script>
    @endscript
</div>
</x-filament-panels::page>
