<form id="formAction" action="{{ $formAction }}" method="post">
    @csrf
    @method('PUT')
    <input type="hidden" name="return_url" value="{{ $returnUrl }}">
    <input type="hidden" value="{{ $scoring->exam_registration_id }}" name="exam_registration_id">

    {{-- ── Mode toggle ── --}}
    <div @class([
        'mb-5 space-y-3' => ($for_filament_panel ?? false),
    ]) @style(! ($for_filament_panel ?? false) ? 'text-align:center;margin-bottom:20px' : null)>
        <div @class([
            'space-y-1 text-left' => ($for_filament_panel ?? false),
        ])>
            <p @class([
                'text-sm font-semibold text-gray-950 dark:text-white' => ($for_filament_panel ?? false),
            ]) @style(! ($for_filament_panel ?? false) ? 'font-size:.88rem;font-weight:800;color:#334155;margin:0 0 6px;text-align:left' : null)>
                Metode penilaian
            </p>
            <p id="scoringModeDescription" @class([
                'text-sm text-gray-600 dark:text-gray-300' => ($for_filament_panel ?? false),
            ]) @style(! ($for_filament_panel ?? false) ? 'font-size:.82rem;color:#64748b;margin:0 0 12px;text-align:left;line-height:1.5' : null)>
                Menggunakan <strong>Nilai Huruf</strong>: pilih nilai huruf langsung dan semua aspek penilaian diset ke nilai yang sama.
            </p>
        </div>

        <div @class([
            'flex justify-start' => ($for_filament_panel ?? false),
        ]) @style(! ($for_filament_panel ?? false) ? 'text-align:center' : null)>
            <div class="mode-toggle-wrap">
                <button type="button" class="mode-btn active" id="btn-direct"
                    onclick="switchMode('direct')" @disabled($formDisabled)>
                    Nilai Huruf
                </button>
                <button type="button" class="mode-btn" id="btn-detail"
                    onclick="switchMode('detail')" @disabled($formDisabled)>
                    Nilai Per Aspek
                </button>
            </div>
        </div>
    </div>

    {{-- ══ Panel 1 – Direct letter selection ══ --}}
    <div id="panel-direct">
        <div class="dbs-section-label">Nilai Huruf</div>
        <div class="grade-grid">
            @foreach ($grades_map as $letter => $range)
            <button type="button"
                class="grade-btn {{ ($has_scores && $init_letter === $letter) ? 'selected' : '' }}"
                data-grade="{{ $letter }}"
                onclick="selectDirectGrade('{{ $letter }}')"
                @disabled($formDisabled)>
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
        <div class="dbs-section-label">Nilai Per Aspek</div>
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
                <select class="form-select form-select-sm {{ ($for_filament_panel ?? false) ? 'fi-scoring-select' : '' }}"
                    name="{{ $item_order }}" id="{{ $item_order }}"
                    onchange="updateFromAspects()" @disabled($formDisabled)>
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
    @php
        $failResultGrades = ['C-', 'D', 'E'];
        $resultIsFailGrade = $has_scores && in_array($init_letter, $failResultGrades, true);
    @endphp
    <div class="dbs-result">
        <div>
            <div class="dbs-result-label">Simpulan Nilai Akhir</div>
            <div @class(['dbs-result-letter', 'dbs-result-fail' => $resultIsFailGrade]) id="letterout">{{ $has_scores ? $init_letter : '—' }}</div>
        </div>
        <div class="dbs-result-divider"></div>
        <div>
            <div class="dbs-result-label">Nilai Angka</div>
            <div @class(['dbs-result-number', 'dbs-result-fail' => $resultIsFailGrade]) id="gradeout">{{ $has_scores ? number_format($init_grade,2) : '—' }}</div>
        </div>
        <div style="margin-left:auto;font-size:.75rem;opacity:.5;align-self:center;text-align:right;line-height:1.4">
            rata-rata<br>5 aspek
        </div>
    </div>

    <hr @class([
        'my-4 border-gray-200 dark:border-white/10' => ($for_filament_panel ?? false),
    ]) @style(! ($for_filament_panel ?? false) ? 'border-color:#e2e8f0;margin:4px 0 6px' : null)>

    {{-- ══ Keputusan Revisi ══ --}}
    <div class="dbs-section-label">Keputusan Revisi</div>
    <div class="revision-card">
        <div class="revision-card-title">Perlu direvisi?</div>
        <div class="rev-toggle-group">
            <input type="radio" class="btn-check" name="revision" id="revisi2"
                autocomplete="off" @checked($scoring->revision==0) value=0 @disabled($formDisabled)
                onClick='toggleRevisionNotes(false, true)' @required(true)>
            <label for="revisi2" class="rev-pill rev-pill-tidak">✓ Tidak Perlu Revisi</label>

            <input type="radio" class="btn-check" name="revision" id="revisi1"
                autocomplete="off" @checked($scoring->revision==1) value=1 @disabled($formDisabled)
                onClick='toggleRevisionNotes(true, false)' @required(true)>
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
            <textarea name="revision_note" rows="7" class="form-control {{ ($for_filament_panel ?? false) ? 'fi-scoring-textarea' : '' }}"
                id="revision_note" @disabled($formDisabled)
                @required($scoring->revision == 1)
                oninput="onRevisionInput()" onblur="saveRevisionDraft()">{{ old('revision_note', $scoring->revision_note) }}</textarea>
            <div class="notes-hint" id="revisionNoteHint">
                @if ($scoring->revision == 1)
                    Wajib diisi jika mahasiswa perlu revisi.
                @else
                    Diabaikan jika tidak perlu revisi.
                @endif
            </div>
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

    <hr @class([
        'my-5 border-gray-200 dark:border-white/10' => ($for_filament_panel ?? false),
    ]) @style(! ($for_filament_panel ?? false) ? 'border-color:#e2e8f0;margin:20px 0 16px' : null)>

    <div class="{{ ($for_filament_panel ?? false) ? 'flex flex-wrap justify-end gap-3 pt-2' : '' }}" @style(! ($for_filament_panel ?? false) ? 'display:flex;gap:10px;justify-content:flex-end;align-items:center' : null)>
        @if ($for_filament_panel ?? false)
            <x-filament::button
                tag="a"
                href="{{ $returnUrl }}"
                color="gray"
            >
                Batal
            </x-filament::button>

            <x-filament::button
                type="submit"
                id="saveBtn"
                icon="heroicon-m-check"
                :disabled="$formDisabled"
            >
                {{ $saveButtonLabel }}
            </x-filament::button>
        @else
            <a href="{{ $returnUrl }}" class="btn btn-outline-secondary btn-sm">Batal</a>
            <button type="submit" class="dbs-save-btn" id="saveBtn" @disabled($formDisabled)>
                {{ $saveButtonLabel }}
            </button>
        @endif
    </div>
</form>
