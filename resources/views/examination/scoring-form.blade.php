@extends('layouts.general')
@push('title')
    Menilai {{ $scoring->registration->student->name }}
@endpush
@push('header')
<br>Penilaian {{ $scoring->registration->examtype->name }}
@if (auth()->user()->hasRole('admin'))
<a href="{{ route('examregistrations.examscores.index',$scoring->exam_registration_id) }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@else
<a href="{{ route('scoring.index') }}" class="btn btn-outline-primary btn-sm float-end">kembali</a>
@endif
@endpush

@push('body')
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

    /* ── Revision toggle (Ya/Tidak pills) ── */
    .revision-card {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 16px 18px; margin-bottom: 10px;
    }
    .revision-card-title { font-size: .9rem; font-weight: 700; color: #334155; margin-bottom: 12px; }
    .rev-toggle-group { display: flex; gap: 8px; position: relative; }
    .btn-check { position: absolute; clip: rect(0,0,0,0); pointer-events: none; }
    .rev-pill {
        flex: 1; text-align: center; cursor: pointer; border-radius: 10px;
        padding: 10px 14px; font-weight: 800; font-size: .88rem;
        border: 2px solid #e2e8f0; background: #fff; color: #64748b;
        transition: all .2s; user-select: none;
    }
    .rev-pill:hover { border-color: #a5b4fc; color: #334155; }
    .btn-check:checked + .rev-pill-tidak {
        background: linear-gradient(135deg, #15803d, #22c55e);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(34,197,94,.3);
    }
    .btn-check:checked + .rev-pill-ya {
        background: linear-gradient(135deg, #c2410c, #f97316);
        border-color: transparent; color: #fff;
        box-shadow: 0 4px 14px rgba(249,115,22,.3);
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
</style>

@php
    $tanggal_sekarang = Carbon\Carbon::now()->isoFormat('Y-MM-DD');
    $exam_start_at = Carbon\Carbon::parse(
        $examregistration->exam_date->format('Y-m-d').' '.trim((string) $examregistration->exam_time)
    );
    $exam_not_started_yet = Carbon\Carbon::now()->lt($exam_start_at);
    $available_check  = ($examregistration->exam_date < $tanggal_sekarang && $examregistration->pass_exam)
                        && !Auth::user()->can('force edit score');

    $score_cols = ['score01','score02','score03','score04','score05'];
    $filled_scores = [];
    foreach ($score_cols as $col) {
        $v = $scoring->{$col};
        if ($v !== null && $v !== '') {
            $filled_scores[] = (float) $v;
        }
    }
    $n_filled = count($filled_scores);
    $has_stored_grade = filled($scoring->grade);

    if ($n_filled === 5) {
        $init_grade = array_sum($filled_scores) / 5;
    } elseif ($has_stored_grade) {
        $init_grade = (float) $scoring->grade;
    } elseif ($n_filled > 0) {
        $init_grade = array_sum($filled_scores) / $n_filled;
    } else {
        $init_grade = null;
    }

    $has_scores = $init_grade !== null;
    $init_letter = '';
    if ($has_scores && filled($scoring->letter)) {
        $init_letter = $scoring->letter;
    } elseif ($has_scores) {
        $g = $init_grade;
        if      ($g < 21) $init_letter = 'E';
        elseif  ($g < 29) $init_letter = 'D';
        elseif  ($g < 37) $init_letter = 'C-';
        elseif  ($g < 45) $init_letter = 'C';
        elseif  ($g < 53) $init_letter = 'C+';
        elseif  ($g < 61) $init_letter = 'B-';
        elseif  ($g < 69) $init_letter = 'B';
        elseif  ($g < 77) $init_letter = 'B+';
        elseif  ($g < 85) $init_letter = 'A-';
        else              $init_letter = 'A';
    }

    $grades_map = [
        'A'  => ['min'=>85, 'max'=>100, 'mid'=>92],
        'A-' => ['min'=>77, 'max'=>84,  'mid'=>80],
        'B+' => ['min'=>69, 'max'=>76,  'mid'=>72],
        'B'  => ['min'=>61, 'max'=>68,  'mid'=>64],
        'B-' => ['min'=>53, 'max'=>60,  'mid'=>56],
        'C+' => ['min'=>45, 'max'=>52,  'mid'=>48],
        'C'  => ['min'=>37, 'max'=>44,  'mid'=>40],
        'C-' => ['min'=>29, 'max'=>36,  'mid'=>32],
        'D'  => ['min'=>21, 'max'=>28,  'mid'=>24],
        'E'  => ['min'=>0,  'max'=>20,  'mid'=>10],
    ];

    $exam_type  = $scoring->registration->exam_type_id;
    $pass_verdict = match($exam_type) {
        1       => 'Rencana penelitian layak dilanjutkan untuk diteliti',
        2       => 'Seminar Hasil Penelitian layak disidangkan',
        default => 'Mahasiswa dinyatakan LULUS',
    };
    $fail_verdict = match($exam_type) {
        1       => 'Rencana penelitian tidak layak dilanjutkan untuk diteliti',
        2       => 'Seminar Hasil Penelitian tidak layak disidangkan',
        default => 'Mahasiswa dinyatakan TIDAK LULUS',
    };
    $scoring_id = $scoring->id;
    $user_id    = auth()->id();
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
        <span class="dbs-penilai">Penilai: {{ $scoring->lecture->name }}</span>
    </div>
</div>

@if ($exam_not_started_yet)
    <div style="text-align:center;padding:52px 0;color:#ef4444;font-size:1.6rem;font-weight:900;letter-spacing:-.5px">
        ujian belum dimulai
    </div>
@else

<form id="formAction" action="{{ route('scoring.update',$scoring->id) }}" method="post">
    @csrf
    @method('PUT')
    <input type="hidden" value="{{ $scoring->exam_registration_id }}" name="exam_registration_id">

    {{-- ── Mode toggle ── --}}
    <div style="text-align:center;margin-bottom:20px">
        <div class="mode-toggle-wrap">
            <button type="button" class="mode-btn active" id="btn-direct"
                onclick="switchMode('direct')" @disabled($available_check)>
                ⊕&nbsp; Pilih Nilai Huruf
            </button>
            <button type="button" class="mode-btn" id="btn-detail"
                onclick="switchMode('detail')" @disabled($available_check)>
                ≡&nbsp; Penilaian Per Aspek
            </button>
        </div>
    </div>

    {{-- ══ Panel 1 – Direct letter selection ══ --}}
    <div id="panel-direct">
        <div class="dbs-section-label">Pilih Nilai</div>
        <div class="grade-grid">
            @foreach ($grades_map as $letter => $range)
            <button type="button"
                class="grade-btn {{ ($has_scores && $init_letter === $letter) ? 'selected' : '' }}"
                data-grade="{{ $letter }}"
                onclick="selectDirectGrade('{{ $letter }}')"
                @disabled($available_check)>
                {{ $letter }}
                <span class="grade-range">{{ $range['min'] }}–{{ $range['max'] }}</span>
            </button>
            @endforeach
        </div>
        <div class="grade-hint">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="#94a3b8"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            Memilih nilai huruf akan mengatur semua aspek ke nilai yang sama
        </div>
    </div>

    {{-- ══ Panel 2 – Five-aspect detail scoring ══ --}}
    <div id="panel-detail" style="display:none">
        <div class="dbs-section-label">Penilaian Per Aspek</div>
        @foreach ($form_items as $item)
        @php
            if      ($item->exam_type_id == 3) { $order = ($item->id) - 10; }
            elseif  ($item->exam_type_id == 2) { $order = ($item->id) - 5; }
            else                               { $order = ($item->id); }
            $item_order = 'score0'.$order;
        @endphp
        <div class="aspect-item">
            <div class="aspect-num">{{ $order }}</div>
            <div class="aspect-name">{{ $item->name }}</div>
            <div class="aspect-select">
                <select class="form-select form-select-sm"
                    name="{{ $item_order }}" id="{{ $item_order }}"
                    onchange="updateFromAspects()" @disabled($available_check)>
                    <option value="">—</option>
                    <option value="{{ rand(86,98) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=85)>A</option>
                    <option value="{{ rand(78,82) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=77 && intval($scoring->$item_order)<=84)>A-</option>
                    <option value="{{ rand(70,74) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=69 && intval($scoring->$item_order)<=76)>B+</option>
                    <option value="{{ rand(62,66) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=61 && intval($scoring->$item_order)<=68)>B</option>
                    <option value="{{ rand(54,58) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=53 && intval($scoring->$item_order)<=60)>B-</option>
                    <option value="{{ rand(46,50) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=45 && intval($scoring->$item_order)<=52)>C+</option>
                    <option value="{{ rand(38,42) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=37 && intval($scoring->$item_order)<=44)>C</option>
                    <option value="{{ rand(30,34) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=29 && intval($scoring->$item_order)<=36)>C-</option>
                    <option value="{{ rand(22,26) }}" @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=21 && intval($scoring->$item_order)<=28)>D</option>
                    <option value="{{ rand(0,18) }}"  @selected($scoring->$item_order !== null && intval($scoring->$item_order)>=0  && intval($scoring->$item_order)<=20)>E</option>
                </select>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Result bar (shared) ── --}}
    <div class="dbs-result">
        <div>
            <div class="dbs-result-label">Simpulan Nilai Akhir</div>
            <div class="dbs-result-letter" id="letterout">{{ $has_scores ? $init_letter : '—' }}</div>
        </div>
        <div class="dbs-result-divider"></div>
        <div>
            <div class="dbs-result-label">Nilai Angka</div>
            <div class="dbs-result-number" id="gradeout">{{ $has_scores ? number_format($init_grade,2) : '—' }}</div>
        </div>
        <div style="margin-left:auto;font-size:.75rem;opacity:.5;align-self:center;text-align:right;line-height:1.4">
            rata-rata<br>5 aspek
        </div>
    </div>

    <hr style="border-color:#e2e8f0;margin:4px 0 6px">

    {{-- ══ Keputusan Revisi ══ --}}
    <div class="dbs-section-label">Keputusan Revisi</div>
    <div class="revision-card">
        <div class="revision-card-title">Perlu direvisi?</div>
        <div class="rev-toggle-group">
            <input type="radio" class="btn-check" name="revision" id="revisi2"
                autocomplete="off" @checked($scoring->revision==0) value=0 @disabled($available_check)
                onClick='toggleRevisionNotes(false)' @required(true)>
            <label for="revisi2" class="rev-pill rev-pill-tidak">✓ Tidak Perlu Revisi</label>

            <input type="radio" class="btn-check" name="revision" id="revisi1"
                autocomplete="off" @checked($scoring->revision==1) value=1 @disabled($available_check)
                onClick='toggleRevisionNotes(true)' @required(true)>
            <label for="revisi1" class="rev-pill rev-pill-ya">✎ Perlu Revisi</label>
        </div>
    </div>

    {{-- ── Revision notes (expandable) ── --}}
    <div id="revision_row" @style($scoring->revision==1 ? "display:block" : "display:none")>
        {{-- Draft restore banner --}}
        <div id="draftBanner" class="draft-banner" style="display:none">
            <div>
                <span style="font-size:1rem;margin-right:6px">⚡</span>
                <span class="draft-banner-text" id="draftBannerText">Ditemukan draf yang belum disimpan</span>
            </div>
            <div class="draft-banner-actions">
                <button type="button" class="draft-btn-restore" onclick="restoreDraft()">Pulihkan</button>
                <button type="button" class="draft-btn-dismiss" onclick="dismissDraft()">Abaikan</button>
            </div>
        </div>
        <div class="notes-card">
            <div class="notes-header">
                <div class="notes-title">Catatan Revisi</div>
                <div class="autosave-pill" id="autosavePill">
                    <span class="autosave-dot"></span>
                    <span id="autosaveText">belum ada perubahan</span>
                </div>
            </div>
            <textarea name="revision_note" rows="7" class="form-control"
                id="revision_note" @disabled($available_check)
                oninput="onRevisionInput()" onblur="saveRevisionDraft()">{{ $scoring->revision_note }}</textarea>
            <div class="notes-hint">Jika kosong, tercetak <em>belum diisi</em> pada lembar revisi mahasiswa</div>
        </div>
    </div>

    {{-- ══ Keputusan Akhir (otomatis, hanya pemberitahuan) ══ --}}
    {{-- hidden input — nilainya diset otomatis oleh JS berdasarkan nilai angka --}}
    @php
        $init_pass = $has_scores ? ($init_grade >= 37 ? 1 : 0) : ($scoring->pass_approved ?? '');
    @endphp
    <input type="hidden" name="pass_approved" id="pass_approved_input" value="{{ $init_pass }}">

    <div class="dbs-section-label">Keputusan Akhir</div>
    @php
        if (!$has_scores)         { $notice_state = 'pending'; }
        elseif ($init_grade >= 37){ $notice_state = 'pass'; }
        else                      { $notice_state = 'fail'; }
    @endphp
    <div class="decision-notice decision-notice-{{ $notice_state }}" id="decisionNotice">
        <div class="decision-notice-icon" id="decisionIcon">
            @if ($notice_state === 'pass') ✓
            @elseif ($notice_state === 'fail') ✗
            @else —
            @endif
        </div>
        <div>
            <div class="decision-notice-verdict" id="decisionVerdict">
                @if ($notice_state === 'pass') {{ $pass_verdict }}
                @elseif ($notice_state === 'fail') {{ $fail_verdict }}
                @else Nilai belum diisi
                @endif
            </div>
            <div class="decision-notice-sub" id="decisionSub">
                @if ($notice_state === 'pass') Nilai ≥ C (≥ 37) — ditentukan otomatis dari nilai yang diberikan
                @elseif ($notice_state === 'fail') Nilai &lt; C (&lt; 37) — ditentukan otomatis dari nilai yang diberikan
                @else Keputusan akan muncul setelah nilai diisi
                @endif
            </div>
        </div>
    </div>

    <hr style="border-color:#e2e8f0;margin:20px 0 16px">

    <div style="display:flex;gap:10px;justify-content:flex-end;align-items:center">
        @if (auth()->user()->hasRole('admin'))
        <a href="{{ route('examregistrations.examscores.index',$scoring->exam_registration_id) }}"
            class="btn btn-outline-secondary btn-sm">Batal</a>
        @else
        <a href="{{ route('scoring.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
        @endif
        <button type="submit" class="dbs-save-btn" id="saveBtn" @disabled($available_check)>
            Simpan Penilaian
        </button>
    </div>
</form>

@endif
@endpush

@push('scripts')
<script>
    // ══ Constants ════════════════════════════════
    const STORAGE_KEY   = 'dbs_rev_{{ $scoring_id }}_{{ $user_id }}';
    const PASS_THRESHOLD = 37; // minimum numeric grade for C (pass)
    const INITIAL_AVG = @json($has_scores ? round((float) $init_grade, 5) : null);
    const INITIAL_LETTER = @json($has_scores ? $init_letter : null);

    // ══ Grade helpers ════════════════════════════
    function gradeToLetter(g) {
        if      (g < 21) return 'E';
        else if (g < 29) return 'D';
        else if (g < 37) return 'C-';
        else if (g < 45) return 'C';
        else if (g < 53) return 'C+';
        else if (g < 61) return 'B-';
        else if (g < 69) return 'B';
        else if (g < 77) return 'B+';
        else if (g < 85) return 'A-';
        else              return 'A';
    }

    function calcAverage() {
        let sum = 0, count = 0;
        for (let i = 1; i <= 5; i++) {
            const el = document.getElementById('score0' + i);
            if (el && el.value !== '') { sum += Number(el.value); count++; }
        }
        return count === 5 ? sum / count : null;
    }

    // ══ Grade display sync ═══════════════════════
    function syncResultDisplay(avg, ltr) {
        document.getElementById('letterout').textContent = ltr  || '—';
        document.getElementById('gradeout').textContent  = avg !== null ? avg.toFixed(2) : '—';
    }

    function syncDirectButtons(ltr) {
        document.querySelectorAll('.grade-btn').forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.grade === ltr);
        });
    }

    // ══ Auto pass/fail decision (notice only) ════
    const PASS_VERDICT = '{{ $pass_verdict }}';
    const FAIL_VERDICT = '{{ $fail_verdict }}';
    function autoSetDecision(avg) {
        const input   = document.getElementById('pass_approved_input');
        const notice  = document.getElementById('decisionNotice');
        const icon    = document.getElementById('decisionIcon');
        const verdict = document.getElementById('decisionVerdict');
        const sub     = document.getElementById('decisionSub');
        if (!input || !notice) return;

        if (avg === null) {
            input.value = '';
            notice.className    = 'decision-notice decision-notice-pending';
            icon.textContent    = '—';
            verdict.textContent = 'Nilai belum diisi';
            sub.textContent     = 'Keputusan akan muncul setelah nilai diisi';
            return;
        }
        const pass = avg >= PASS_THRESHOLD;
        input.value = pass ? 1 : 0;
        notice.className    = 'decision-notice decision-notice-' + (pass ? 'pass' : 'fail');
        icon.textContent    = pass ? '✓' : '✗';
        verdict.textContent = pass ? PASS_VERDICT : FAIL_VERDICT;
        sub.textContent     = pass
            ? 'dengan nilai akhir ' + gradeToLetter(avg) + ' — ditentukan otomatis dari nilai yang diberikan'
            : 'dengan nilai akhir ' + gradeToLetter(avg) + ' — ditentukan otomatis dari nilai yang diberikan';
    }

    // ══ Called on any aspect change ══════════════
    function updateFromAspects() {
        const avg = calcAverage();
        const ltr = avg !== null ? gradeToLetter(avg) : null;
        syncResultDisplay(avg, ltr);
        syncDirectButtons(ltr);
        autoSetDecision(avg);
        autoSetRevision(ltr);
    }

    // ══ Called when direct grade button clicked ══
    function selectDirectGrade(gradeLabel) {
        for (let i = 1; i <= 5; i++) {
            const sel = document.getElementById('score0' + i);
            if (!sel) continue;
            for (const opt of sel.options) {
                if (opt.text.trim() === gradeLabel) { sel.value = opt.value; break; }
            }
        }
        syncDirectButtons(gradeLabel);
        const avg = calcAverage();
        syncResultDisplay(avg, gradeLabel);
        autoSetDecision(avg);
        autoSetRevision(gradeLabel);
    }

    // ══ Mode switch ══════════════════════════════
    function switchMode(mode) {
        document.getElementById('btn-direct').classList.toggle('active', mode === 'direct');
        document.getElementById('btn-detail').classList.toggle('active', mode === 'detail');
        document.getElementById('panel-direct').style.display = mode === 'direct' ? 'block' : 'none';
        document.getElementById('panel-detail').style.display = mode === 'detail' ? 'block' : 'none';
        if (mode === 'direct') {
            const avg = calcAverage();
            if (avg !== null) syncDirectButtons(gradeToLetter(avg));
        }
    }

    // ══ Auto revision based on grade ════════════
    function autoSetRevision(ltr) {
        if (!ltr) return;
        const needsRevision = ltr !== 'A';
        document.getElementById(needsRevision ? 'revisi1' : 'revisi2').checked = true;
        toggleRevisionNotes(needsRevision);
    }

    // ══ Revision notes toggle ════════════════════
    function toggleRevisionNotes(show) {
        document.getElementById('revision_row').style.display = show ? 'block' : 'none';
    }

    // ══ Auto-save to localStorage ════════════════
    let autosaveTimer  = null;
    let autosavePeriod = null;
    let draftData      = null;

    function setAutosaveStatus(state, msg) {
        const pill = document.getElementById('autosavePill');
        const text = document.getElementById('autosaveText');
        pill.className = 'autosave-pill ' + state;
        text.textContent = msg;
    }

    function saveRevisionDraft() {
        const ta = document.getElementById('revision_note');
        if (!ta) return;
        const payload = { note: ta.value, ts: Date.now() };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
            draftData = payload;
            const t = new Date(payload.ts);
            const hm = t.getHours().toString().padStart(2,'0') + ':' + t.getMinutes().toString().padStart(2,'0');
            setAutosaveStatus('saved', '✓ tersimpan — ' + hm);
        } catch(e) {
            setAutosaveStatus('unsaved', '⚠ gagal menyimpan');
        }
    }

    let inputDebounce = null;
    function onRevisionInput() {
        setAutosaveStatus('unsaved', '● belum tersimpan');
        clearTimeout(inputDebounce);
        inputDebounce = setTimeout(saveRevisionDraft, 2000); // save 2s after typing stops
    }

    function checkRevisionDraft() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const saved = JSON.parse(raw);
            const ta    = document.getElementById('revision_note');
            if (!ta || !saved.note) return;
            // Only show banner if saved note differs from current
            if (saved.note === ta.value) {
                const t  = new Date(saved.ts);
                const hm = t.getHours().toString().padStart(2,'0') + ':' + t.getMinutes().toString().padStart(2,'0');
                setAutosaveStatus('saved', '✓ tersimpan — ' + hm);
                return;
            }
            draftData = saved;
            const t  = new Date(saved.ts);
            const hm = t.toLocaleDateString('id-ID', {day:'numeric',month:'short'}) + ' ' +
                       t.getHours().toString().padStart(2,'0') + ':' + t.getMinutes().toString().padStart(2,'0');
            document.getElementById('draftBannerText').textContent =
                '⚡  Draf catatan revisi ditemukan dari sesi sebelumnya (' + hm + ')';
            document.getElementById('draftBanner').style.display = 'flex';
        } catch(e) { /* ignore */ }
    }

    function restoreDraft() {
        if (!draftData) return;
        const ta = document.getElementById('revision_note');
        if (ta) { ta.value = draftData.note; onRevisionInput(); }
        document.getElementById('draftBanner').style.display = 'none';
    }

    function dismissDraft() {
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
        draftData = null;
        document.getElementById('draftBanner').style.display = 'none';
    }

    // ══ Init ═════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function () {
        let avg = calcAverage();
        if (avg === null && INITIAL_AVG !== null) {
            avg = INITIAL_AVG;
            const ltr = INITIAL_LETTER || gradeToLetter(avg);
            syncResultDisplay(avg, ltr);
            syncDirectButtons(ltr);
        }
        autoSetDecision(avg);

        // Check localStorage for revision draft
        const revRow = document.getElementById('revision_row');
        if (revRow && revRow.style.display !== 'none') checkRevisionDraft();

        // Periodic auto-save every 60s while the page is open
        autosavePeriod = setInterval(function () {
            const ta = document.getElementById('revision_note');
            const revVisible = document.getElementById('revision_row')?.style.display !== 'none';
            if (ta && revVisible && ta.value) saveRevisionDraft();
        }, 60000);
    });

    // Clear draft from localStorage on successful form submit
    document.getElementById('formAction')?.addEventListener('submit', function () {
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    });
</script>
@endpush
