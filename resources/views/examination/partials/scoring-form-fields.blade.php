<style>
    :root {
        --dbs-blue: #1e40af;
        --dbs-blue-mid: #2563eb;
        --dbs-purple: #7c3aed;
        --dbs-cyan: #0891b2;
        --dbs-green: #15803d;
        --dbs-red: #991b1b;
    }
    body, .card, button, input, textarea, select { font-family: 'Nunito', sans-serif; }

    /* ── Student header ── */
    .dbs-student-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #4f46e5 100%);
        border-radius: 14px;
        color: #fff;
        padding: 22px 26px;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
    }
    .dbs-student-card::before {
        content: '';
        position: absolute;
        top: -80px; right: -50px;
        width: 260px; height: 260px;
        background: radial-gradient(circle, rgba(99,102,241,.28) 0%, transparent 70%);
        pointer-events: none;
    }
    .dbs-student-card::after {
        content: '';
        position: absolute;
        bottom: -60px; left: -30px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(14,165,233,.18) 0%, transparent 70%);
        pointer-events: none;
    }
    .dbs-nim  { font-size: .82rem; opacity: .7; font-weight: 600; letter-spacing: .3px; }
    .dbs-name { font-size: 1.25rem; font-weight: 900; letter-spacing: -.4px; margin: 2px 0 6px; }
    .dbs-title { font-size: .88rem; color: #c4b5fd; font-style: italic; line-height: 1.5; margin-bottom: 14px; }
    .dbs-file-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(34,197,94,.2); border: 1px solid rgba(34,197,94,.4);
        color: #86efac; text-decoration: none; border-radius: 8px;
        padding: 5px 14px; font-size: .82rem; font-weight: 700; transition: background .2s;
    }
    .dbs-file-btn:hover { background: rgba(34,197,94,.32); color: #86efac; }
    .dbs-penilai { display: inline-block; margin-left: 12px; font-size: .8rem; opacity: .65; }

    /* ── Mode toggle ── */
    .mode-toggle-wrap {
        background: #f1f5f9; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 5px;
        display: inline-flex; gap: 4px;
    }
    .mode-btn {
        background: transparent; border: none; border-radius: 8px;
        padding: 9px 22px; font-family: 'Nunito', sans-serif;
        font-weight: 700; font-size: .875rem; color: #64748b;
        cursor: pointer; transition: all .22s; white-space: nowrap;
    }
    .mode-btn.active {
        background: linear-gradient(135deg, #1e3a8a, #4f46e5);
        color: #fff; box-shadow: 0 2px 10px rgba(79,70,229,.35);
    }
    .mode-btn:disabled { opacity: .45; cursor: not-allowed; }

    /* ── Section labels ── */
    .dbs-section-label {
        font-size: .76rem; font-weight: 800; letter-spacing: .8px;
        text-transform: uppercase; color: var(--dbs-blue);
        margin: 20px 0 12px; padding-bottom: 7px;
        border-bottom: 2px solid #e0e7ff;
        display: flex; align-items: center; gap: 8px;
    }
    .dbs-section-label::before {
        content: ''; width: 4px; height: 16px;
        background: linear-gradient(180deg, #1e3a8a, #4f46e5);
        border-radius: 2px; display: inline-block; flex-shrink: 0;
    }

    /* ── Direct grade buttons ── */
    .grade-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 8px; }
    .grade-btn {
        background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px;
        padding: 13px 16px; font-family: 'Nunito', sans-serif;
        font-weight: 800; font-size: 1.1rem; color: #334155;
        cursor: pointer; transition: all .2s; min-width: 72px; text-align: center; line-height: 1;
    }
    .grade-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,.12); border-color: #a5b4fc; }
    .grade-btn:disabled { opacity: .5; cursor: not-allowed; }
    .grade-btn .grade-range { display: block; font-size: .68rem; font-weight: 600; margin-top: 4px; opacity: .65; }
    .grade-btn.selected { color: #fff; border-color: transparent; transform: translateY(-3px); }
    .grade-btn.selected .grade-range { opacity: .75; color: #fff; }
    .grade-btn[data-grade="A"].selected  { background: linear-gradient(135deg,#15803d,#22c55e); box-shadow: 0 6px 18px rgba(34,197,94,.35); }
    .grade-btn[data-grade="A-"].selected { background: linear-gradient(135deg,#16a34a,#4ade80); box-shadow: 0 6px 18px rgba(74,222,128,.3); color: #14532d; }
    .grade-btn[data-grade="B+"].selected { background: linear-gradient(135deg,#1d4ed8,#3b82f6); box-shadow: 0 6px 18px rgba(59,130,246,.35); }
    .grade-btn[data-grade="B"].selected  { background: linear-gradient(135deg,#2563eb,#60a5fa); box-shadow: 0 6px 18px rgba(96,165,250,.3); }
    .grade-btn[data-grade="B-"].selected { background: linear-gradient(135deg,#1e40af,#93c5fd); box-shadow: 0 6px 18px rgba(147,197,253,.3); color: #1e3a8a; }
    .grade-btn[data-grade="C+"].selected { background: linear-gradient(135deg,#b45309,#f59e0b); box-shadow: 0 6px 18px rgba(245,158,11,.35); }
    .grade-btn[data-grade="C"].selected  { background: linear-gradient(135deg,#d97706,#fbbf24); box-shadow: 0 6px 18px rgba(251,191,36,.3); color: #78350f; }
    .grade-btn[data-grade="C-"].selected { background: linear-gradient(135deg,#ca8a04,#fde68a); box-shadow: 0 6px 18px rgba(253,230,138,.3); color: #78350f; }
    .grade-btn[data-grade="D"].selected  { background: linear-gradient(135deg,#c2410c,#f97316); box-shadow: 0 6px 18px rgba(249,115,22,.35); }
    .grade-btn[data-grade="E"].selected  { background: linear-gradient(135deg,#991b1b,#ef4444); box-shadow: 0 6px 18px rgba(239,68,68,.35); }
    .grade-btn[data-grade="A"]  .grade-range { color: #16a34a; }
    .grade-btn[data-grade="A-"] .grade-range { color: #16a34a; }
    .grade-btn[data-grade="B+"] .grade-range { color: #2563eb; }
    .grade-btn[data-grade="B"]  .grade-range { color: #2563eb; }
    .grade-btn[data-grade="B-"] .grade-range { color: #3b82f6; }
    .grade-btn[data-grade="C+"] .grade-range { color: #d97706; }
    .grade-btn[data-grade="C"]  .grade-range { color: #d97706; }
    .grade-btn[data-grade="C-"] .grade-range { color: #ca8a04; }
    .grade-btn[data-grade="D"]  .grade-range { color: #ea580c; }
    .grade-btn[data-grade="E"]  .grade-range { color: #dc2626; }
    .grade-hint { font-size: .77rem; color: #94a3b8; margin-top: 4px; display: flex; align-items: center; gap: 6px; }

    /* ── Aspect items ── */
    .aspect-item {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: 12px 16px; margin-bottom: 8px;
        display: flex; align-items: center; gap: 12px; transition: border-color .2s;
    }
    .aspect-item:focus-within { border-color: #a5b4fc; background: #fff; }
    .aspect-num {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #1e3a8a, #4f46e5);
        color: #fff; font-weight: 800; font-size: .8rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .aspect-name { flex: 1; font-size: .9rem; font-weight: 600; color: #334155; }
    .aspect-select { width: 120px; flex-shrink: 0; }

    /* ── Result bar ── */
    .dbs-result {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #4f46e5 100%);
        border-radius: 14px; padding: 18px 24px; color: #fff;
        display: flex; align-items: center; gap: 22px; margin: 20px 0; flex-wrap: wrap;
    }
    .dbs-result-label { font-size: .72rem; font-weight: 800; letter-spacing: .7px; text-transform: uppercase; opacity: .6; margin-bottom: 2px; }
    .dbs-result-letter {
        font-size: 2.4rem; font-weight: 900; letter-spacing: -1px; line-height: 1;
        background: linear-gradient(90deg, #93c5fd, #c4b5fd);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text; min-width: 60px; transition: all .3s;
    }
    .dbs-result-number { font-size: 1.15rem; font-weight: 700; color: rgba(255,255,255,.85); transition: all .3s; }
    .dbs-result-divider { width: 1px; height: 44px; background: rgba(255,255,255,.18); flex-shrink: 0; }
    .dbs-result-letter.dbs-result-fail {
        background: none;
        -webkit-text-fill-color: #f87171;
        background-clip: unset;
        color: #f87171;
    }
    .dbs-result-number.dbs-result-fail { color: #fca5a5; }

    /* ── Revision toggle (Ya/Tidak pills) ── */
    .revision-card {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 16px 18px; margin-bottom: 10px;
    }
    .revision-card-title { font-size: .9rem; font-weight: 700; color: #334155; margin-bottom: 12px; }
    .rev-toggle-group {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        column-gap: 1rem;
        row-gap: 0.875rem;
        position: relative;
    }
    @media (max-width: 640px) {
        .rev-toggle-group { grid-template-columns: 1fr; }
    }
    .btn-check { position: absolute; clip: rect(0,0,0,0); pointer-events: none; }
    .rev-pill {
        width: 100%; text-align: center; cursor: pointer; border-radius: 10px;
        padding: 12px 16px; font-weight: 800; font-size: .88rem;
        border: 2px solid #e2e8f0; background: #fff; color: #64748b;
        transition: all .2s; user-select: none;
    }
    .rev-pill:hover { border-color: #a5b4fc; color: #334155; }
    .btn-check:checked + .rev-pill-tidak {
        background: linear-gradient(135deg, #15803d, #22c55e);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(34,197,94,.3);
    }
    .btn-check:checked + .rev-pill-minor {
        background: linear-gradient(135deg, #b45309, #f59e0b);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(245,158,11,.35);
    }
    .btn-check:checked + .rev-pill-mayor {
        background: linear-gradient(135deg, #b91c1c, #ef4444);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(239,68,68,.35);
    }

    /* ── Revision notes & autosave ── */
    .notes-card {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 16px 18px; margin-bottom: 10px;
        border-left: 3px solid #f97316;
    }
    .notes-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
    .notes-title { font-size: .88rem; font-weight: 700; color: #334155; }
    .notes-hint { font-size: .77rem; color: #94a3b8; margin-top: 8px; font-style: italic; }
    .autosave-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: #f1f5f9; border: 1px solid #e2e8f0;
        border-radius: 20px; padding: 3px 10px;
        font-size: .73rem; font-weight: 700; color: #64748b;
        transition: all .3s;
    }
    .autosave-pill.saving  { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .autosave-pill.saved   { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
    .autosave-pill.unsaved { background: #fffbeb; border-color: #fde68a; color: #d97706; }
    .autosave-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .autosave-pill.saving .autosave-dot { animation: pulse .8s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

    /* ── Draft restore banner ── */
    .draft-banner {
        background: linear-gradient(135deg, #1e3a8a18, #4f46e518);
        border: 1px solid #a5b4fc;
        border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
    }
    .draft-banner-text { font-size: .85rem; font-weight: 700; color: #1e3a8a; }
    .draft-banner-actions { display: flex; gap: 8px; }
    .draft-btn-restore {
        background: linear-gradient(135deg, #1e3a8a, #4f46e5);
        color: #fff; border: none; border-radius: 7px;
        padding: 5px 14px; font-size: .8rem; font-weight: 700;
        cursor: pointer; font-family: 'Nunito', sans-serif; transition: opacity .2s;
    }
    .draft-btn-restore:hover { opacity: .88; }
    .draft-btn-dismiss {
        background: transparent; color: #64748b;
        border: 1px solid #cbd5e1; border-radius: 7px;
        padding: 5px 14px; font-size: .8rem; font-weight: 700;
        cursor: pointer; font-family: 'Nunito', sans-serif; transition: all .2s;
    }
    .draft-btn-dismiss:hover { background: #f1f5f9; }

    /* ── Decision notice (pass/fail) ── */
    .decision-notice {
        border-radius: 12px; padding: 14px 18px;
        display: flex; align-items: center; gap: 14px;
        transition: all .35s;
    }
    .decision-notice-pass {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 1px solid #86efac;
    }
    .decision-notice-fail {
        background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        border: 1px solid #fda4af;
    }
    .decision-notice-pending {
        background: #f8fafc; border: 1px dashed #cbd5e1;
    }
    .decision-notice-icon {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; font-weight: 900; transition: all .35s;
    }
    .decision-notice-pass .decision-notice-icon { background: linear-gradient(135deg,#15803d,#22c55e); color: #fff; }
    .decision-notice-fail .decision-notice-icon { background: linear-gradient(135deg,#991b1b,#f43f5e); color: #fff; }
    .decision-notice-pending .decision-notice-icon { background: #e2e8f0; color: #94a3b8; }
    .decision-notice-verdict {
        font-size: 1rem; font-weight: 800; transition: color .35s;
        line-height: 1.3;
    }
    .decision-notice-pass .decision-notice-verdict { color: #15803d; }
    .decision-notice-fail .decision-notice-verdict { color: #be123c; }
    .decision-notice-pending .decision-notice-verdict { color: #94a3b8; }
    .decision-notice-sub { font-size: .77rem; font-weight: 600; margin-top: 2px; transition: color .35s; }
    .decision-notice-pass .decision-notice-sub { color: #16a34a; }
    .decision-notice-fail .decision-notice-sub { color: #e11d48; }
    .decision-notice-pending .decision-notice-sub { color: #cbd5e1; }
    @keyframes noticeIn { from{opacity:.4;transform:scale(.97)} to{opacity:1;transform:scale(1)} }
    .decision-notice-pass, .decision-notice-fail { animation: noticeIn .3s ease; }

    /* ── Save button ── */
    .dbs-save-btn {
        background: linear-gradient(135deg, #1e3a8a, #4f46e5);
        color: #fff; border: none; border-radius: 10px;
        padding: 11px 32px; font-family: 'Nunito', sans-serif;
        font-weight: 800; font-size: .95rem; cursor: pointer;
        transition: all .2s; box-shadow: 0 2px 10px rgba(79,70,229,.3);
        letter-spacing: .1px;
    }
    .dbs-save-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,.4); }
    .dbs-save-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

    .dbs-history-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.28);
        color: #e0e7ff; border-radius: 8px; padding: 5px 14px;
        font-size: .82rem; font-weight: 700; cursor: pointer;
        font-family: 'Nunito', sans-serif; transition: background .2s;
    }
    .dbs-history-btn:hover { background: rgba(255,255,255,.24); color: #fff; }

    .dbs-history-dialog {
        width: min(720px, calc(100vw - 32px));
        border: none; border-radius: 14px; padding: 0;
        box-shadow: 0 20px 50px rgba(15,23,42,.25);
    }
    .dbs-history-dialog::backdrop { background: rgba(15,23,42,.55); }
    .dbs-history-dialog-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #4f46e5 100%);
        color: #fff; padding: 18px 22px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .dbs-history-dialog-title { font-size: 1rem; font-weight: 800; margin: 0; }
    .dbs-history-dialog-close {
        background: rgba(255,255,255,.15); border: none; color: #fff;
        width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 1.1rem;
    }
    .dbs-history-dialog-body { padding: 18px 22px 22px; max-height: 70vh; overflow-y: auto; background: #fff; }
    .dbs-history-item {
        border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;
    }
    .dbs-history-item:last-child { margin-bottom: 0; }
    .dbs-history-meta { font-size: .78rem; color: #64748b; margin-bottom: 8px; }
    .dbs-history-type {
        display: inline-block; margin-bottom: 8px; font-size: .75rem; font-weight: 800;
        color: #1e40af; background: #eff6ff; border-radius: 999px; padding: 2px 10px;
    }
    .dbs-history-title { font-size: .92rem; font-weight: 700; color: #334155; margin-bottom: 10px; line-height: 1.5; }
    .dbs-history-grid { display: grid; gap: 8px; font-size: .84rem; }
    .dbs-history-grid dt { color: #64748b; font-weight: 700; }
    .dbs-history-grid dd { margin: 0; color: #334155; }
</style>

@php
    $previousExams = $previousExams ?? [];
@endphp


{{-- ── Student info header ── --}}
<div class="dbs-student-card">
    <div class="dbs-nim">{{ $scoring->registration->student->username }}</div>
    <div class="dbs-name">{{ $scoring->registration->student->name }}</div>
    <div class="dbs-title">
        Judul {{ $scoring->registration->exam_type_id == 1 ? 'Proposal' : 'Skripsi' }}:
        {{ $scoring->registration->title }}
    </div>
    <div>
        @if ($scoring->registration->exam_file)
            <a href="{{ $scoring->registration->exam_file }}" target="_blank" class="dbs-file-btn">
                ↗ file ujian
            </a>
        @else
            <span style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);color:#fca5a5;border-radius:7px;padding:4px 12px;font-size:.8rem;font-weight:700">File Ujian Belum Ada</span>
        @endif

        @if (count($previousExams) > 0)
            <button type="button" class="dbs-history-btn" onclick="document.getElementById('previousExamsModal')?.showModal()">
                🕘 riwayat penilaian
            </button>
        @endif

        <span class="dbs-penilai">Penilai: {{ $scoring->lecture->name }}</span>
    </div>
</div>

@if (count($previousExams) > 0)
    <dialog id="previousExamsModal" class="dbs-history-dialog">
        <div class="dbs-history-dialog-header">
            <h2 class="dbs-history-dialog-title">Riwayat Penilaian Ujian Mahasiswa</h2>
            <button type="button" class="dbs-history-dialog-close" onclick="document.getElementById('previousExamsModal')?.close()" aria-label="Tutup">×</button>
        </div>
        <div class="dbs-history-dialog-body">
            @foreach ($previousExams as $pastExam)
                <article class="dbs-history-item">
                    <div class="dbs-history-meta">
                        {{ $pastExam['exam_date'] }} · {{ $pastExam['exam_time'] }} WIB
                    </div>
                    <div class="dbs-history-type">{{ $pastExam['exam_type_name'] }}</div>
                    <div class="dbs-history-title">{{ $pastExam['title'] }}</div>
                    <dl class="dbs-history-grid">
                        <div>
                            <dt>Nilai</dt>
                            <dd>{{ $pastExam['grade_display'] }}</dd>
                        </div>
                        <div>
                            <dt>Catatan revisi</dt>
                            <dd>{{ $pastExam['revision_note'] }}</dd>
                        </div>
                        <div>
                            <dt>Keputusan akhir</dt>
                            <dd>{{ $pastExam['final_decision'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </dialog>
@endif

@if ($exam_not_started_yet)
    <div style="text-align:center;padding:52px 0;color:#ef4444;font-size:1.6rem;font-weight:900;letter-spacing:-.5px">
        ujian belum dimulai
    </div>
@else

@php
    $formDisabled = $form_disabled ?? $available_check ?? false;
    $saveButtonLabel = $save_button_label ?? 'Simpan Penilaian';
@endphp

@if ($errors->has('revision_note'))
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.88rem;font-weight:700">
        {{ $errors->first('revision_note') }}
    </div>
@endif

@include('examination.partials.scoring-form-body', ['for_filament_panel' => false])

@endif

@include('examination.partials.scoring-form-script')
