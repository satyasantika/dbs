{{-- Script HARUS berada di atas elemen x-data agar fungsi terdaftar sebelum Alpine memprosesnya --}}
<script>
window.pasteImport = function () {
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

    const HEADER_MAP = {
        no: null, 'no.': null,
        nama_mahasiswa: 'nama_mahasiswa',
        e1: 'penguji1',    penguji1: 'penguji1',
        e2: 'penguji2',    penguji2: 'penguji2',
        e3: 'penguji3',    penguji3: 'penguji3',
        g1: 'pembimbing1', pembimbing1: 'pembimbing1',
        g2: 'pembimbing2', pembimbing2: 'pembimbing2',
        jenis_ujian: 'jenis_ujian',
        ketua: 'ketua_penguji', ketua_penguji: 'ketua_penguji',
        tanggal_ujian: 'tanggal_ujian',
        ruang: 'ruang', waktu: 'waktu',
        npm: 'npm', ipk: 'ipk', judul: 'judul', kontak: 'kontak',
        file_ujian: 'file_ujian', status_publikasi_sinta: null,
        meeting_id: 'meeting_id', passcode: 'passcode', link_room: 'link_room',
    };

    const REQUIRED = ['npm', 'jenis_ujian', 'tanggal_ujian', 'judul', 'ruang', 'waktu'];

    return {
        step: 1,
        rawText: '',
        parsedRows: [],
        parseError: '',
        committing: false,
        importResults: [],
        summaryHtml: '',
        resultSummaryHtml: '',

        get validCount() {
            return this.parsedRows.filter(r => !r._invalid).length;
        },

        reset() {
            this.step              = 1;
            this.rawText           = '';
            this.parsedRows        = [];
            this.parseError        = '';
            this.committing        = false;
            this.importResults     = [];
            this.summaryHtml       = '';
            this.resultSummaryHtml = '';
        },

        doParse() {
            this.parseError = '';
            const raw = this.rawText.trim();
            if (!raw) { this.parseError = 'Belum ada data yang di-paste.'; return; }

            const lines = raw.split('\n').filter(l => l.trim() !== '');
            if (!lines.length) { this.parseError = 'Data kosong.'; return; }

            const firstCell = lines[0].split('\t')[0].trim();
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

                const missing = REQUIRED.filter(f => !row[f]);
                row._invalid  = missing.length > 0;
                row._missing  = missing;
                return row;
            }).filter(r => r.npm || r.nama_mahasiswa);

            if (!rows.length) {
                this.parseError = 'Tidak ada data terbaca. Pastikan kolom dipisah dengan TAB (bukan spasi).';
                return;
            }

            this.parsedRows  = rows;
            const valid      = rows.filter(r => !r._invalid).length;
            const modeBadge  = hasHeader
                ? '<span style="background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:4px;font-size:11px;">mode: dengan header</span>'
                : '<span style="background:#f3f4f6;color:#374151;padding:1px 6px;border-radius:4px;font-size:11px;">mode: tanpa header (posisi tetap)</span>';

            this.summaryHtml = `<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:8px 12px;font-size:13px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                ${modeBadge}
                <span><strong>${rows.length}</strong> baris terdeteksi —
                <strong style="color:#15803d">${valid} siap kirim</strong>
                ${rows.length - valid > 0 ? `· <span style="color:#dc2626">${rows.length - valid} perlu diperbaiki</span>` : ''}
                </span></div>`;

            this.step = 2;
        },

        async doCommit() {
            this.committing = true;
            try {
                const res = await fetch('{{ route('examregistrations.paste-import') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ rows: this.parsedRows }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'HTTP ' + res.status);
                }

                const data         = await res.json();
                this.importResults = data.results;

                const success = data.results.filter(r => r.status === 'success').length;
                const skip    = data.results.filter(r => r.status === 'skip').length;
                const error   = data.results.filter(r => r.status === 'error').length;

                this.resultSummaryHtml = `<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;font-size:13px;display:flex;gap:16px;flex-wrap:wrap;">
                    <span style="color:#15803d;font-weight:600;">✓ ${success} berhasil</span>
                    <span style="color:#d97706;font-weight:600;">⚠ ${skip} dilewati</span>
                    <span style="color:#dc2626;font-weight:600;">✗ ${error} gagal</span>
                </div>`;

                this.step = 3;

                if (success > 0) {
                    setTimeout(() => { window.Livewire?.dispatch('$refresh'); }, 400);
                }
            } catch (e) {
                alert('Terjadi kesalahan: ' + e.message);
            } finally {
                this.committing = false;
            }
        },
    };
};
</script>

<div x-data="pasteImport()" style="font-size:14px;">

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — PETUNJUK + TEXTAREA
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 1">

        {{-- Petunjuk cara pakai --}}
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
            <p style="font-weight:600;color:#15803d;margin:0 0 8px;">Cara menggunakan Import Banyak:</p>
            <ol style="margin:0;padding-left:20px;color:#166534;line-height:1.8;">
                <li>Buka data ujian di <strong>Google Sheets / Excel</strong></li>
                <li>Pilih semua baris data yang ingin diimpor (boleh dengan atau tanpa baris header)</li>
                <li>Tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 5px;">Ctrl+C</kbd> untuk menyalin</li>
                <li>Klik area teks di bawah, lalu tekan <kbd style="background:#fff;border:1px solid #ccc;border-radius:3px;padding:1px 5px;">Ctrl+V</kbd> untuk paste</li>
                <li>Klik <strong>Tampilkan Preview</strong> untuk memeriksa data sebelum disimpan</li>
            </ol>
        </div>

        {{-- Urutan kolom --}}
        <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
            <p style="font-weight:600;margin:0 0 8px;color:#374151;">Urutan kolom spreadsheet yang didukung:</p>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:#374151;color:#fff;">
                            <th style="padding:4px 8px;border:1px solid #4b5563;">Posisi</th>
                            <th style="padding:4px 8px;border:1px solid #4b5563;">Nama Kolom</th>
                            <th style="padding:4px 8px;border:1px solid #4b5563;">Alias Header</th>
                            <th style="padding:4px 8px;border:1px solid #4b5563;">Contoh</th>
                            <th style="padding:4px 8px;border:1px solid #4b5563;">Wajib?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            [1,  'no',           'no, no.',                    '1',                          false],
                            [2,  'nama_mahasiswa','nama_mahasiswa',             'BUDI SANTOSO',               false],
                            [3,  'E1 (Penguji 1)','e1, penguji1',              'DDN',                        false],
                            [4,  'E2 (Penguji 2)','e2, penguji2',              'RAT',                        false],
                            [5,  'E3 (Penguji 3)','e3, penguji3',              'LIN',                        false],
                            [6,  'G1 (Pembimbing 1)','g1, pembimbing1',        'SIN',                        false],
                            [7,  'G2 (Pembimbing 2)','g2, pembimbing2',        'MAD',                        false],
                            [8,  'jenis_ujian',  'jenis_ujian',                'Seminar Hasil Penelitian',   true],
                            [9,  'ketua',        'ketua, ketua_penguji',       'DDN',                        false],
                            [10, 'tanggal_ujian','tanggal_ujian',              '19-Jun-2026',                true],
                            [11, 'ruang',        'ruang',                      '1',                          true],
                            [12, 'waktu',        'waktu',                      '07.00 - 08.00',              true],
                            [13, 'npm',          'npm',                        '222151084',                  true],
                            [14, 'ipk',          'ipk',                        '3,68',                       false],
                            [15, 'judul',        'judul',                      'Efektivitas...',             true],
                            [16, 'kontak',       'kontak',                     '085156778922',               false],
                            [17, 'file_ujian',   'file_ujian',                 'https://drive.google.com/…', false],
                            [18, 'meeting_id',   'meeting_id',                 '995 3668 7665',              false],
                            [19, 'passcode',     'passcode',                   'matematika',                 false],
                            [20, 'link_room',    'link_room',                  'https://zoom.us/j/…',        false],
                        ] as [$pos, $col, $alias, $contoh, $wajib])
                        <tr style="{{ $loop->even ? 'background:#f9fafb;' : '' }}">
                            <td style="padding:3px 8px;border:1px solid #e5e7eb;text-align:center;color:#6b7280;">{{ $pos }}</td>
                            <td style="padding:3px 8px;border:1px solid #e5e7eb;font-family:monospace;">{{ $col }}</td>
                            <td style="padding:3px 8px;border:1px solid #e5e7eb;color:#6b7280;font-size:11px;">{{ $alias }}</td>
                            <td style="padding:3px 8px;border:1px solid #e5e7eb;color:#374151;font-size:11px;">{{ $contoh }}</td>
                            <td style="padding:3px 8px;border:1px solid #e5e7eb;text-align:center;">
                                @if($wajib)
                                    <span style="color:#dc2626;font-weight:600;">✓</span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin:8px 0 0;font-size:11px;color:#6b7280;">
                💡 Penguji & pembimbing diisi dengan <strong>inisial dosen</strong> (bukan nama lengkap). NPM yang belum terdaftar akan <strong>otomatis dibuatkan akun</strong> dengan password = NPM.
            </p>
        </div>

        {{-- Textarea paste --}}
        <label style="display:block;font-weight:600;margin-bottom:6px;color:#374151;">
            Area Paste Data
        </label>
        <textarea
            x-model="rawText"
            rows="7"
            placeholder="Paste data di sini (Ctrl+V)…&#10;&#10;Contoh baris tanpa header:&#10;1	WULAN SRI WAHYUNI	DDN	RAT	LIN	SIN	MAD	Seminar Hasil Penelitian	DDN	19-Jun-2026	1	07.00 - 08.00	222151084	3,68	Judul Skripsi...	085156778922		995 3668 7665	matematika	https://zoom.us/j/..."
            style="width:100%;font-family:monospace;font-size:12px;border:1px solid #d1d5db;border-radius:6px;padding:10px;resize:vertical;background:#fff;color:#111;"
        ></textarea>

        <div style="display:flex;align-items:center;gap:12px;margin-top:12px;">
            <button
                type="button"
                @click="doParse()"
                style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-size:14px;cursor:pointer;font-weight:500;"
            >
                Tampilkan Preview →
            </button>
            <span x-show="parseError" x-text="parseError" style="color:#dc2626;font-size:13px;"></span>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 2 — PREVIEW TABEL
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 2" style="display:none;">

        <div x-html="summaryHtml" style="margin-bottom:12px;"></div>

        <div style="overflow:auto;max-height:380px;border:1px solid #e5e7eb;border-radius:6px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:6px 8px;white-space:nowrap;">#</th>
                        <th style="padding:6px 8px;white-space:nowrap;">NPM</th>
                        <th style="padding:6px 8px;white-space:nowrap;">Nama</th>
                        <th style="padding:6px 8px;white-space:nowrap;">Jenis Ujian</th>
                        <th style="padding:6px 8px;white-space:nowrap;">Tanggal</th>
                        <th style="padding:6px 8px;white-space:nowrap;">Ruang · Waktu</th>
                        <th style="padding:6px 8px;white-space:nowrap;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in parsedRows" :key="row._rowNum">
                        <tr :style="row._invalid ? 'background:#fef2f2;' : ''">
                            <td style="padding:4px 8px;color:#9ca3af;border-bottom:1px solid #f3f4f6;" x-text="row._rowNum"></td>
                            <td style="padding:4px 8px;font-family:monospace;border-bottom:1px solid #f3f4f6;" x-text="row.npm || '-'"></td>
                            <td style="padding:4px 8px;border-bottom:1px solid #f3f4f6;" x-text="row.nama_mahasiswa || '—'"></td>
                            <td style="padding:4px 8px;border-bottom:1px solid #f3f4f6;" x-text="row.jenis_ujian || '-'"></td>
                            <td style="padding:4px 8px;white-space:nowrap;border-bottom:1px solid #f3f4f6;" x-text="row.tanggal_ujian || '-'"></td>
                            <td style="padding:4px 8px;white-space:nowrap;border-bottom:1px solid #f3f4f6;" x-text="(row.ruang || '-') + (row.waktu ? ' · ' + row.waktu : '')"></td>
                            <td style="padding:4px 8px;border-bottom:1px solid #f3f4f6;">
                                <span
                                    :style="row._invalid
                                        ? 'background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:11px;'
                                        : 'background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:11px;'"
                                    x-text="row._invalid ? ('Kurang: ' + row._missing.join(', ')) : 'Siap'"
                                ></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div style="display:flex;gap:12px;margin-top:16px;align-items:center;">
            <button
                type="button"
                @click="step = 1"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:14px;cursor:pointer;color:#374151;"
            >
                ← Paste Ulang
            </button>
            <button
                type="button"
                @click="doCommit()"
                :disabled="committing || validCount === 0"
                style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-size:14px;cursor:pointer;font-weight:500;"
                :style="(committing || validCount === 0) ? 'opacity:0.5;cursor:not-allowed;background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-size:14px;' : ''"
            >
                <span x-show="!committing">✓ Simpan (<span x-text="validCount"></span> baris valid)</span>
                <span x-show="committing">Menyimpan…</span>
            </button>
            <span x-show="validCount === 0" style="color:#dc2626;font-size:13px;">Tidak ada baris yang valid untuk disimpan</span>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 3 — HASIL IMPORT
    ══════════════════════════════════════════════════════ --}}
    <div x-show="step === 3" style="display:none;">

        <div x-html="resultSummaryHtml" style="margin-bottom:12px;"></div>

        <div style="overflow:auto;max-height:380px;border:1px solid #e5e7eb;border-radius:6px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:#1f2937;color:#fff;">
                    <tr>
                        <th style="padding:6px 8px;width:50px;">#</th>
                        <th style="padding:6px 8px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in importResults" :key="r.row">
                        <tr :style="{
                            success: 'background:#f0fdf4;',
                            skip:    'background:#fffbeb;',
                            error:   'background:#fef2f2;'
                        }[r.status]">
                            <td style="padding:4px 8px;color:#9ca3af;border-bottom:1px solid #f3f4f6;" x-text="r.row"></td>
                            <td style="padding:4px 8px;border-bottom:1px solid #f3f4f6;">
                                <span x-text="{ success: '✓', skip: '⚠', error: '✗' }[r.status]"></span>
                                <span x-text="r.message"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            <button
                type="button"
                @click="reset()"
                style="background:#fff;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:14px;cursor:pointer;color:#374151;"
            >
                ← Import Lagi
            </button>
        </div>

    </div>

</div>
