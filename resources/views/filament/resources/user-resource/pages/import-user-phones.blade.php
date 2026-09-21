<x-filament-panels::page>
<div id="import-phone-page" wire:ignore style="font-size:14px;max-width:900px;">

    <div id="step1">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;color:#15803d;margin:0 0 10px;font-size:15px;">Cara import nomor HP</p>
            <ol style="margin:0;padding-left:22px;color:#166534;line-height:2;">
                <li>Siapkan dua kolom di <strong>Google Sheets</strong> atau <strong>Excel</strong>: NPM dan nomor HP</li>
                <li>Salin barisnya — header boleh ikut, boleh tidak</li>
                <li>Tempel di area bawah, lalu klik <strong>Tampilkan Preview</strong></li>
                <li>Hanya pengguna yang NPM-nya sudah ada yang diubah; akun baru tidak dibuat</li>
            </ol>
        </div>

        <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;margin:0 0 12px;color:#111827;font-size:15px;">Urutan kolom</p>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:#1f2937;color:#fff;text-align:left;">
                            <th style="padding:6px 10px;">No</th>
                            <th style="padding:6px 10px;">Kolom</th>
                            <th style="padding:6px 10px;">Alias header</th>
                            <th style="padding:6px 10px;">Contoh</th>
                            <th style="padding:6px 10px;text-align:center;">Wajib</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#9ca3af;text-align:center;">1</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;font-family:monospace;">npm</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">npm, nim, username</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">222151146</td>
                            <td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;text-align:center;color:#dc2626;font-weight:700;">✓</td>
                        </tr>
                        <tr style="background:#f9fafb;">
                            <td style="padding:5px 10px;color:#9ca3af;text-align:center;">2</td>
                            <td style="padding:5px 10px;font-family:monospace;">phone</td>
                            <td style="padding:5px 10px;color:#6b7280;">hp, no_hp, phone, kontak</td>
                            <td style="padding:5px 10px;">081234567890</td>
                            <td style="padding:5px 10px;text-align:center;color:#dc2626;font-weight:700;">✓</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e;line-height:1.7;">
                Nomor disimpan tanpa awalan 0 atau +62 (contoh: <code>81234567890</code>), sama seperti format WhatsApp di sistem.
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-weight:700;font-size:15px;color:#111827;margin-bottom:8px;">Area Paste Data</label>
            <textarea
                id="pasteArea"
                rows="9"
                placeholder="Paste data di sini (Ctrl+V)…&#10;&#10;Contoh tanpa header:&#10;222151146&#9;081234567890&#10;&#10;Contoh dengan header:&#10;npm&#9;hp&#10;222151146&#9;081234567890"
                style="width:100%;font-family:monospace;font-size:12px;border:2px solid #d1d5db;border-radius:8px;padding:12px;resize:vertical;background:#fff;color:#111827;line-height:1.5;"
            ></textarea>
            <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                <button type="button" onclick="doParse()" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">
                    Tampilkan Preview →
                </button>
                <span id="parseError" style="color:#dc2626;font-size:13px;display:none;"></span>
            </div>
        </div>
    </div>

    <div id="step2" style="display:none;">
        <div id="summaryBanner" style="margin-bottom:12px;"></div>
        <div style="overflow:auto;max-height:420px;border:1px solid #e5e7eb;border-radius:8px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:7px 10px;">#</th>
                        <th style="padding:7px 10px;">NPM</th>
                        <th style="padding:7px 10px;">Nama</th>
                        <th style="padding:7px 10px;">HP lama</th>
                        <th style="padding:7px 10px;">HP baru</th>
                        <th style="padding:7px 10px;">Status</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button type="button" onclick="goBack()" style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;">← Paste Ulang</button>
            <button id="btnCommit" type="button" onclick="doCommit()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">Simpan Nomor Valid</button>
            <span id="commitNote" style="color:#6b7280;font-size:13px;"></span>
        </div>
    </div>

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
            <button type="button" onclick="resetAll()" style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;color:#374151;font-weight:500;">← Import Lagi</button>
            <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">Lihat Daftar Pengguna</a>
        </div>
    </div>

    @script
    <script>
(function () {
    var STATE = window.userPhonePasteImport = window.userPhonePasteImport || { parsedRows: [], previewHasHeader: false };
    if (!Array.isArray(STATE.parsedRows)) STATE.parsedRows = [];

    var POSITIONAL_FIELDS = ['npm', 'phone'];
    var HEADER_MAP = {
        'no': null, 'no.': null,
        'npm': 'npm', 'nim': 'npm', 'username': 'npm',
        'hp': 'phone', 'no_hp': 'phone', 'nomor_hp': 'phone', 'phone': 'phone',
        'telepon': 'phone', 'kontak': 'phone', 'wa': 'phone', 'whatsapp': 'phone'
    };

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
        var first = (cells[0] || '').trim();
        var offset = (/^\d+$/.test(first) && cells.length > POSITIONAL_FIELDS.length) ? 1 : 0;
        POSITIONAL_FIELDS.forEach(function (field, idx) {
            row[field] = (cells[idx + offset] || '').trim();
        });
        return row;
    }

    function isHeaderRow(firstCell) {
        var key = firstCell.trim().toLowerCase().replace(/[\s.]+/g, '_');
        return HEADER_MAP.hasOwnProperty(key);
    }

    function resetCommitButton() {
        var btn = document.getElementById('btnCommit');
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = 'Simpan Nomor Valid';
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    function setCommitButtonLoading(loading) {
        var btn = document.getElementById('btnCommit');
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
        var noteEl = document.getElementById('commitNote');
        var btn = document.getElementById('btnCommit');
        if (savable === 0) {
            noteEl.textContent = 'Tidak ada baris valid untuk disimpan.';
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            return;
        }
        noteEl.textContent = savable + ' nomor akan disimpan.';
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    function renderPreview() {
        var rows = getParsedRows();
        var validCount = rows.filter(function (r) { return !r._invalid; }).length;
        var modeTxt = STATE.previewHasHeader ? 'mode: dengan header' : 'mode: tanpa header';
        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;font-size:13px;">' +
            '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:4px;font-size:11px;margin-right:8px;">' + modeTxt + '</span>' +
            '<strong>' + rows.length + '</strong> baris — <strong style="color:#15803d">' + validCount + ' valid</strong>' +
            (rows.length - validCount > 0 ? ' · <span style="color:#dc2626">' + (rows.length - validCount) + ' dilewati</span>' : '') +
            '</div>';

        var tbody = document.getElementById('previewBody');
        tbody.innerHTML = '';
        rows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.style.background = row._invalid ? '#fef2f2' : '';
            var status = row._invalid
                ? '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">' + escapeHtml(row._reason || 'Tidak valid') + '</span>'
                : '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;">Akan diperbarui</span>';
            tr.innerHTML =
                '<td style="padding:5px 10px;color:#9ca3af;border-bottom:1px solid #f3f4f6;">' + row._rowNum + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row.npm || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row._name || '—') + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row._currentPhone || '—') + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;border-bottom:1px solid #f3f4f6;">' + escapeHtml(row._normalizedPhone || row.phone || '-') + '</td>' +
                '<td style="padding:5px 10px;border-bottom:1px solid #f3f4f6;">' + status + '</td>';
            tbody.appendChild(tr);
        });
        resetCommitButton();
        updateCommitState();
    }

    function fetchChecks(rows) {
        var csrfToken = getCsrfToken();
        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;font-size:13px;color:#6b7280;">Memeriksa NPM…</div>';
        document.getElementById('previewBody').innerHTML = '';

        return fetch('{{ route('users.paste-import-phones-check') }}', {
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
                row._name = info.name || null;
                row._currentPhone = info.current_phone || null;
                row._normalizedPhone = info.phone || row.phone;
                if (info.found) {
                    row._invalid = false;
                    row._reason = null;
                } else {
                    row._invalid = true;
                    row._reason = info.message || 'NPM tidak ditemukan';
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

    window.doParse = function () {
        var errEl = document.getElementById('parseError');
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
            row._invalid = !row.npm || !row.phone;
            row._reason = row._invalid ? 'NPM dan nomor HP wajib' : null;
            return row;
        }).filter(function (r) { return r.npm || r.phone; });

        if (!rows.length) {
            showError('Tidak ada data yang terbaca. Pastikan kolom dipisah TAB.');
            return;
        }

        setParsedRows(rows);
        STATE.previewHasHeader = hasHeader;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = '';
        fetchChecks(rows);
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
        var validRows = getParsedRows().filter(function (r) { return !r._invalid; });
        if (!validRows.length) { alert('Tidak ada baris valid untuk disimpan.'); return; }

        var csrfToken = getCsrfToken();
        if (!csrfToken) { alert('CSRF token tidak ditemukan. Coba refresh halaman.'); return; }

        setCommitButtonLoading(true);

        fetch('{{ route('users.paste-import-phones') }}', {
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
            var error = results.filter(function (r) { return r.status === 'error'; }).length;
            document.getElementById('resultBanner').innerHTML =
                '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;display:flex;gap:20px;font-size:14px;">' +
                '<span style="color:#15803d;font-weight:700;">✓ ' + success + ' berhasil</span>' +
                '<span style="color:#dc2626;font-weight:700;">✗ ' + error + ' gagal</span></div>';
            var tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            results.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.style.background = r.status === 'success' ? '#f0fdf4' : '#fef2f2';
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
        var el = document.getElementById('parseError');
        el.textContent = msg;
        el.style.display = 'inline';
    }
})();
    </script>
    @endscript
</div>
</x-filament-panels::page>
