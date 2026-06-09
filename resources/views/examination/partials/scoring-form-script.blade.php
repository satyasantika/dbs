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
    const FAIL_RESULT_LETTERS = ['C-', 'D', 'E'];

    function isFailResultLetter(ltr) {
        return FAIL_RESULT_LETTERS.includes(ltr);
    }

    function syncResultDisplay(avg, ltr) {
        const letterEl = document.getElementById('letterout');
        const gradeEl = document.getElementById('gradeout');
        const displayLetter = ltr || '—';

        if (letterEl) {
            letterEl.textContent = displayLetter;
            letterEl.classList.toggle('dbs-result-fail', isFailResultLetter(ltr));
        }

        if (gradeEl) {
            gradeEl.textContent = avg !== null ? avg.toFixed(2) : '—';
            gradeEl.classList.toggle('dbs-result-fail', isFailResultLetter(ltr));
        }
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
    const MODE_DESCRIPTIONS = {
        direct: 'Menggunakan <strong>Nilai Huruf</strong>: pilih nilai huruf langsung dan semua aspek penilaian diset ke nilai yang sama.',
        detail: 'Menggunakan <strong>Nilai Per Aspek</strong>: nilai setiap aspek diisi terpisah, lalu nilai akhir dihitung dari rata-rata 5 aspek.',
    };

    function syncModeDescription(mode) {
        const el = document.getElementById('scoringModeDescription');
        if (el) {
            el.innerHTML = MODE_DESCRIPTIONS[mode] || MODE_DESCRIPTIONS.direct;
        }
    }

    function switchMode(mode) {
        document.getElementById('btn-direct').classList.toggle('active', mode === 'direct');
        document.getElementById('btn-detail').classList.toggle('active', mode === 'detail');
        document.getElementById('panel-direct').style.display = mode === 'direct' ? 'block' : 'none';
        document.getElementById('panel-detail').style.display = mode === 'detail' ? 'block' : 'none';
        syncModeDescription(mode);
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
        toggleRevisionNotes(needsRevision, !needsRevision);
    }

    // ══ Revision notes toggle ════════════════════
    function toggleRevisionNotes(show, clearValue = false) {
        const row = document.getElementById('revision_row');
        const ta = document.getElementById('revision_note');
        const hint = document.getElementById('revisionNoteHint');

        if (row) {
            row.style.display = show ? 'block' : 'none';
        }

        if (!ta) {
            return;
        }

        if (!show && clearValue) {
            ta.value = '';
            setAutosaveStatus('saved', 'catatan revisi dikosongkan');
        }

        if (show) {
            ta.setAttribute('required', 'required');
            if (hint) {
                hint.textContent = 'Wajib diisi jika mahasiswa perlu revisi.';
            }
        } else {
            ta.removeAttribute('required');
            if (hint) {
                hint.textContent = 'Diabaikan jika tidak perlu revisi.';
            }
        }
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
        syncModeDescription(document.getElementById('btn-detail')?.classList.contains('active') ? 'detail' : 'direct');

        let avg = calcAverage();
        if (avg === null && INITIAL_AVG !== null) {
            avg = INITIAL_AVG;
            const ltr = INITIAL_LETTER || gradeToLetter(avg);
            syncResultDisplay(avg, ltr);
            syncDirectButtons(ltr);
        }
        autoSetDecision(avg);

        // Sync revision field UI on load without clearing saved note.
        const needsRevisionOnLoad = document.getElementById('revisi1')?.checked;
        toggleRevisionNotes(!!needsRevisionOnLoad, false);

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
    document.getElementById('formAction')?.addEventListener('submit', function (event) {
        const needsRevision = document.getElementById('revisi1')?.checked;
        const noteField = document.getElementById('revision_note');
        const note = noteField?.value.trim() ?? '';

        if (needsRevision && note === '') {
            event.preventDefault();
            alert('Catatan revisi wajib diisi jika mahasiswa perlu revisi.');
            noteField?.focus();
            return;
        }

        if (!needsRevision && noteField) {
            noteField.value = '';
        }

        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    });
</script>
