<x-filament-panels::page>
<div id="bulk-edit-page" style="font-size:14px;max-width:960px;">

    <div id="step1">
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <p style="font-weight:700;color:#1d4ed8;margin:0 0 10px;font-size:15px;">Cara menggunakan Edit Banyak</p>
            <ol style="margin:0;padding-left:22px;color:#1e3a8a;line-height:2;">
                <li>Format kolom <strong>sama dengan Import Banyak</strong> (copy-paste dari spreadsheet)</li>
                <li>Sistem mencocokkan baris berdasarkan <strong>NPM</strong> + <strong>jenis ujian</strong></li>
                <li>Jika mahasiswa punya <strong>lebih dari satu</strong> pendaftaran jenis ujian yang sama, pilih <strong>ujian ke-</strong> mana yang diupdate</li>
                <li>Klik <strong>Update Data Valid</strong> — perubahan disinkronkan ke <code>guide_examiners</code> dan <code>exam_scores</code></li>
            </ol>
        </div>

        <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:12px;color:#6b7280;">
            <strong>Catatan:</strong> Edit Banyak hanya mengupdate pendaftaran yang <em>sudah ada</em>. Untuk data baru gunakan <a href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl('import') }}" style="color:#2563eb;">Import Banyak</a>.
            Kolom wajib: npm, jenis_ujian, tanggal_ujian, judul, ruang, waktu.
        </div>

        <label style="display:block;font-weight:700;font-size:15px;color:#111827;margin-bottom:8px;">Area Paste Data</label>
        <textarea id="pasteArea" rows="9"
            placeholder="Paste data di sini (Ctrl+V)…"
            style="width:100%;font-family:monospace;font-size:12px;border:2px solid #d1d5db;border-radius:8px;padding:12px;resize:vertical;"></textarea>
        <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
            <button type="button" onclick="doParse()"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">
                Tampilkan Preview →
            </button>
            <span id="parseError" style="color:#dc2626;font-size:13px;display:none;"></span>
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
                        <th style="padding:7px 10px;">Jenis Ujian</th>
                        <th style="padding:7px 10px;">Tanggal</th>
                        <th style="padding:7px 10px;min-width:240px;">Status / Pilih Ujian Ke-</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
            <button type="button" onclick="goBack()"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;">← Paste Ulang</button>
            <button id="btnCommit" type="button" onclick="doCommit()"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;cursor:pointer;">
                Update Data Valid
            </button>
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
        <div style="display:flex;gap:12px;margin-top:16px;">
            <button type="button" onclick="resetAll()"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:9px 20px;font-size:14px;cursor:pointer;">← Edit Lagi</button>
            <a href="{{ \App\Filament\Resources\ExamRegistrationResource::getUrl('index') }}"
                style="background:#2563eb;color:#fff;border-radius:6px;padding:9px 24px;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">
                Lihat Daftar Ujian
            </a>
        </div>
    </div>

    @script
    <script>
(function () {
    var POSITIONAL = [null,'nama_mahasiswa','penguji1','penguji2','penguji3','pembimbing1','pembimbing2','jenis_ujian','ketua_penguji','tanggal_ujian','ruang','waktu','npm','ipk','judul','kontak','file_ujian','meeting_id','passcode','link_room'];
    var HEADER_MAP = {
        'no': null, 'no.': null, 'nama_mahasiswa': 'nama_mahasiswa',
        'e1': 'penguji1', 'penguji1': 'penguji1', 'e2': 'penguji2', 'penguji2': 'penguji2',
        'e3': 'penguji3', 'penguji3': 'penguji3', 'g1': 'pembimbing1', 'pembimbing1': 'pembimbing1',
        'g2': 'pembimbing2', 'pembimbing2': 'pembimbing2', 'jenis_ujian': 'jenis_ujian',
        'ketua': 'ketua_penguji', 'ketua_penguji': 'ketua_penguji', 'tanggal_ujian': 'tanggal_ujian',
        'ruang': 'ruang', 'waktu': 'waktu', 'npm': 'npm', 'ipk': 'ipk', 'judul': 'judul', 'kontak': 'kontak',
        'file_ujian': 'file_ujian', 'status_publikasi_sinta': null,
        'meeting_id': 'meeting_id', 'passcode': 'passcode', 'link_room': 'link_room',
    };
    var REQUIRED = ['npm', 'jenis_ujian', 'tanggal_ujian', 'judul', 'ruang', 'waktu'];
    var parsedRows = [];
    var previewHasHeader = false;

    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function splitCells(line) {
        if (line.indexOf('\t') !== -1) return line.split('\t');
        return line.trim().split(/\s{2,}/);
    }
    function getCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '{{ csrf_token() }}';
    }
    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            if (!text) throw new Error('Respons server kosong (HTTP ' + res.status + ')');
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Respons bukan JSON: ' + text.slice(0, 300)); }
        });
    }
    function resetCommitButton() {
        var btn = document.getElementById('btnCommit');
        btn.disabled = false; btn.textContent = 'Update Data Valid';
        btn.style.opacity = '1'; btn.style.cursor = 'pointer';
    }
    function setCommitButtonLoading(on) {
        var btn = document.getElementById('btnCommit');
        if (on) { btn.disabled = true; btn.textContent = 'Menyimpan…'; btn.style.opacity = '0.7'; btn.style.cursor = 'not-allowed'; }
        else resetCommitButton();
    }

    function rowIsReady(r) {
        if (r._invalid) return false;
        if (!r._resolveInfo) return false;
        if (r._resolveInfo.status === 'not_found' || r._resolveInfo.status === 'error') return false;
        if (r._editAction === 'skip') return false;
        if (r._resolveInfo.status === 'pick_order' && !r._registration_order) return false;
        return r._resolveInfo.status === 'ready' || (r._resolveInfo.status === 'pick_order' && r._registration_order);
    }

    function countPendingOrders() {
        return parsedRows.filter(function (r) {
            return !r._invalid && r._resolveInfo && r._resolveInfo.status === 'pick_order' && !r._registration_order;
        }).length;
    }

    function updateCommitState() {
        var ready = parsedRows.filter(rowIsReady).length;
        var pending = countPendingOrders();
        var note = document.getElementById('commitNote');
        var btn = document.getElementById('btnCommit');
        if (pending > 0) {
            note.textContent = pending + ' baris perlu dipilih ujian ke-';
            btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed';
            return;
        }
        if (ready === 0) {
            note.textContent = 'Tidak ada baris yang siap diupdate.';
            btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed';
            return;
        }
        note.textContent = ready + ' baris akan diupdate.';
        btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer';
    }

    function renderStatusCell(row) {
        if (row._invalid) {
            return '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">Kurang: ' + escapeHtml(row._missing.join(', ')) + '</span>';
        }
        var info = row._resolveInfo;
        if (!info) return '<span style="color:#9ca3af;">…</span>';

        if (info.status === 'not_found' || info.status === 'error') {
            return '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;">' + escapeHtml(info.message) + '</span>'
                + ' <button type="button" onclick="setEditAction(' + row._rowNum + ', \'skip\')" style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Lewati</button>';
        }

        if (row._editAction === 'skip') {
            return '<span style="background:#f3f4f6;color:#6b7280;padding:2px 6px;border-radius:4px;font-size:11px;">Dilewati</span>'
                + ' <button type="button" onclick="setEditAction(' + row._rowNum + ', null)" style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Ubah</button>';
        }

        if (info.status === 'ready' || (info.status === 'pick_order' && row._registration_order)) {
            var ord = row._registration_order;
            return '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;">Update ujian ke-' + ord + '</span>'
                + (info.status === 'pick_order' ? ' <button type="button" onclick="clearRegistrationOrder(' + row._rowNum + ')" style="margin-left:4px;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;">Ubah</button>' : '');
        }

        if (info.status === 'pick_order') {
            var html = '<div style="font-size:11px;color:#92400e;margin-bottom:6px;">' + escapeHtml(info.message) + '</div>';
            html += '<div style="display:flex;flex-direction:column;gap:4px;">';
            (info.matches || []).forEach(function (m) {
                html += '<button type="button" onclick="setRegistrationOrder(' + row._rowNum + ',' + m.registration_order + ',' + m.registration_id + ')"'
                    + ' style="text-align:left;background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;font-size:11px;cursor:pointer;">'
                    + '<strong>Ke-' + m.registration_order + '</strong> · ' + escapeHtml(m.exam_date) + ' · ' + escapeHtml(m.title || '—')
                    + '</button>';
            });
            html += '<button type="button" onclick="setEditAction(' + row._rowNum + ', \'skip\')" style="background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;font-size:11px;cursor:pointer;color:#6b7280;">Lewati baris ini</button>';
            html += '</div>';
            return html;
        }

        return '<span style="color:#6b7280;">—</span>';
    }

    function renderPreview() {
        var valid = parsedRows.filter(function (r) { return !r._invalid; }).length;
        var pending = countPendingOrders();
        var ready = parsedRows.filter(rowIsReady).length;
        var modeTxt = previewHasHeader ? 'dengan header' : 'tanpa header';

        document.getElementById('summaryBanner').innerHTML =
            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;font-size:13px;">'
            + '<strong>' + parsedRows.length + '</strong> baris · <strong style="color:#15803d">' + valid + ' valid</strong>'
            + ' · <strong style="color:#2563eb">' + ready + ' siap update</strong>'
            + (pending ? ' · <strong style="color:#dc2626">' + pending + ' tunggu pilihan ujian ke-</strong>' : '')
            + ' · <span style="color:#6b7280;">mode: ' + modeTxt + '</span></div>';

        var tbody = document.getElementById('previewBody');
        tbody.innerHTML = '';
        parsedRows.forEach(function (row) {
            var bg = row._invalid ? '#fef2f2' : '';
            if (!bg && row._resolveInfo && (row._resolveInfo.status === 'not_found' || row._resolveInfo.status === 'error')) bg = '#fef2f2';
            if (!bg && row._resolveInfo && row._resolveInfo.status === 'pick_order' && !row._registration_order) bg = '#fffbeb';
            var tr = document.createElement('tr');
            tr.style.background = bg;
            tr.innerHTML =
                '<td style="padding:5px 10px;color:#9ca3af;">' + row._rowNum + '</td>' +
                '<td style="padding:5px 10px;font-family:monospace;">' + escapeHtml(row.npm || '-') + '</td>' +
                '<td style="padding:5px 10px;">' + escapeHtml(row.nama_mahasiswa || (row._resolveInfo && row._resolveInfo.student_name) || '—') + '</td>' +
                '<td style="padding:5px 10px;">' + escapeHtml(row.jenis_ujian || (row._resolveInfo && row._resolveInfo.exam_type_name) || '-') + '</td>' +
                '<td style="padding:5px 10px;white-space:nowrap;">' + escapeHtml(row.tanggal_ujian || '-') + '</td>' +
                '<td style="padding:5px 10px;">' + renderStatusCell(row) + '</td>';
            tbody.appendChild(tr);
        });
        resetCommitButton();
        updateCommitState();
    }

    window.setRegistrationOrder = function (rowNum, order, regId) {
        var row = parsedRows.find(function (r) { return r._rowNum === rowNum; });
        if (!row) return;
        row._registration_order = order;
        row._registration_id = regId;
        row._editAction = null;
        renderPreview();
    };

    window.clearRegistrationOrder = function (rowNum) {
        var row = parsedRows.find(function (r) { return r._rowNum === rowNum; });
        if (!row) return;
        row._registration_order = null;
        row._registration_id = null;
        renderPreview();
    };

    window.setEditAction = function (rowNum, action) {
        var row = parsedRows.find(function (r) { return r._rowNum === rowNum; });
        if (!row) return;
        row._editAction = action === 'skip' ? 'skip' : null;
        if (action === 'skip') { row._registration_order = null; row._registration_id = null; }
        renderPreview();
    };

    function fetchResolve(rows) {
        document.getElementById('summaryBanner').innerHTML = '<div style="padding:10px;color:#6b7280;">Mencocokkan NPM + jenis ujian…</div>';
        document.getElementById('previewBody').innerHTML = '';
        return fetch('{{ route('examregistrations.paste-bulk-edit-resolve') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ rows: rows }),
        }).then(function (res) {
            return parseJsonResponse(res).then(function (body) {
                if (!res.ok) throw new Error(body.message || ('HTTP ' + res.status));
                return body;
            });
        }).then(function (data) {
            var map = {};
            (data.resolves || []).forEach(function (x) { map[x.row] = x; });
            rows.forEach(function (row) {
                row._resolveInfo = map[row._rowNum] || { status: 'error', message: 'Tidak bisa dicek' };
                row._registration_order = row._resolveInfo.selected_order || null;
                row._registration_id = row._resolveInfo.registration_id || null;
                row._editAction = null;
            });
            renderPreview();
        }).catch(function (e) {
            alert('Gagal memeriksa data: ' + e.message);
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = '';
        });
    }

    window.doParse = function () {
        var errEl = document.getElementById('parseError');
        errEl.style.display = 'none';
        var raw = document.getElementById('pasteArea').value.trim();
        if (!raw) { errEl.textContent = 'Belum ada data.'; errEl.style.display = 'inline'; return; }

        var lines = raw.split('\n').filter(function (l) { return l.trim(); });
        var firstCells = splitCells(lines[0]);
        var hasHeader = isNaN(Number(firstCells[0].trim())) || firstCells[0].trim() === '';
        var dataLines = hasHeader ? lines.slice(1) : lines;
        var headerKeys = hasHeader ? firstCells.map(function (h) { return h.trim().toLowerCase().replace(/[\s.]+/g, '_'); }) : null;

        parsedRows = dataLines.map(function (line, i) {
            var cells = splitCells(line);
            var row = { _rowNum: hasHeader ? i + 2 : i + 1 };
            if (hasHeader) {
                headerKeys.forEach(function (key, idx) { var f = HEADER_MAP[key]; if (f) row[f] = (cells[idx] || '').trim(); });
            } else {
                POSITIONAL.forEach(function (f, idx) { if (f) row[f] = (cells[idx] || '').trim(); });
            }
            row._missing = REQUIRED.filter(function (f) { return !row[f]; });
            row._invalid = row._missing.length > 0;
            return row;
        }).filter(function (r) { return r.npm || r.nama_mahasiswa; });

        if (!parsedRows.length) { errEl.textContent = 'Data tidak terbaca.'; errEl.style.display = 'inline'; return; }

        previewHasHeader = hasHeader;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = '';
        fetchResolve(parsedRows);
    };

    window.goBack = function () {
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = '';
        resetCommitButton();
    };

    window.resetAll = function () {
        parsedRows = [];
        document.getElementById('pasteArea').value = '';
        document.getElementById('previewBody').innerHTML = '';
        document.getElementById('resultBody').innerHTML = '';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step1').style.display = '';
        resetCommitButton();
    };

    window.doCommit = function () {
        if (countPendingOrders() > 0) { alert('Pilih ujian ke- untuk setiap baris yang memiliki lebih dari satu pendaftaran.'); return; }
        var toSave = parsedRows.filter(rowIsReady);
        if (!toSave.length) { alert('Tidak ada baris siap diupdate.'); return; }

        setCommitButtonLoading(true);
        fetch('{{ route('examregistrations.paste-bulk-edit') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ rows: parsedRows }),
        }).then(function (res) {
            return parseJsonResponse(res).then(function (body) {
                if (!res.ok) throw new Error(body.message || ('HTTP ' + res.status));
                return body;
            });
        }).then(function (data) {
            var results = data.results || [];
            var ok = results.filter(function (r) { return r.status === 'success'; }).length;
            var skip = results.filter(function (r) { return r.status === 'skip'; }).length;
            var err = results.filter(function (r) { return r.status === 'error'; }).length;
            document.getElementById('resultBanner').innerHTML =
                '<div style="padding:10px 14px;display:flex;gap:20px;font-size:14px;">'
                + '<span style="color:#15803d;font-weight:700;">✓ ' + ok + ' berhasil</span>'
                + '<span style="color:#d97706;">⚠ ' + skip + ' dilewati</span>'
                + '<span style="color:#dc2626;">✗ ' + err + ' gagal</span></div>';
            var tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            results.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td style="padding:5px 10px;color:#9ca3af;">' + r.row + '</td><td style="padding:5px 10px;">' + escapeHtml(r.message) + '</td>';
                tbody.appendChild(tr);
            });
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = '';
        }).catch(function (e) {
            alert('Terjadi kesalahan: ' + e.message);
            setCommitButtonLoading(false);
        });
    };
})();
    </script>
    @endscript
</div>
</x-filament-panels::page>
