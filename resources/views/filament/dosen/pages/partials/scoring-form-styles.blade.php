<style>
    .fi-dosen-edit-scoring-page .mode-toggle-wrap {
        background: rgb(243 244 246);
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 0.25rem;
        display: inline-flex;
        gap: 0.25rem;
    }

    .dark .fi-dosen-edit-scoring-page .mode-toggle-wrap {
        background: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .mode-btn {
        background: transparent;
        border: none;
        border-radius: 0.5rem;
        padding: 0.625rem 1.25rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: rgb(107 114 128);
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .dark .fi-dosen-edit-scoring-page .mode-btn {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .mode-btn.active {
        background: rgb(var(--primary-600));
        color: #fff;
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    }

    .fi-dosen-edit-scoring-page .mode-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .fi-dosen-edit-scoring-page .dbs-section-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgb(107 114 128);
        margin: 1.25rem 0 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgb(229 231 235);
    }

    .dark .fi-dosen-edit-scoring-page .dbs-section-label {
        color: rgb(156 163 175);
        border-bottom-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .grade-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        margin-bottom: 0.5rem;
    }

    .fi-dosen-edit-scoring-page .grade-btn {
        background: rgb(249 250 251);
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        font-weight: 700;
        font-size: 1rem;
        color: rgb(55 65 81);
        cursor: pointer;
        transition: all 0.2s;
        min-width: 4.5rem;
        text-align: center;
        line-height: 1;
    }

    .dark .fi-dosen-edit-scoring-page .grade-btn {
        background: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.1);
        color: rgb(229 231 235);
    }

    .fi-dosen-edit-scoring-page .grade-btn:hover:not(:disabled) {
        border-color: rgb(var(--primary-400));
    }

    .fi-dosen-edit-scoring-page .grade-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .fi-dosen-edit-scoring-page .grade-btn .grade-range {
        display: block;
        font-size: 0.6875rem;
        font-weight: 600;
        margin-top: 0.25rem;
        opacity: 0.7;
    }

    .fi-dosen-edit-scoring-page .grade-btn.selected {
        color: #fff;
        border-color: transparent;
        background: rgb(var(--primary-600));
        box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
    }

    .fi-dosen-edit-scoring-page .grade-btn.selected .grade-range {
        color: rgb(255 255 255 / 0.85);
        opacity: 1;
    }

    .fi-dosen-edit-scoring-page .grade-hint {
        font-size: 0.8125rem;
        color: rgb(107 114 128);
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .dark .fi-dosen-edit-scoring-page .grade-hint {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .aspect-item {
        background: rgb(249 250 251);
        border: 1px solid rgb(229 231 235);
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: border-color 0.2s;
    }

    .dark .fi-dosen-edit-scoring-page .aspect-item {
        background: rgb(255 255 255 / 0.03);
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .aspect-item:focus-within {
        border-color: rgb(var(--primary-400));
    }

    .fi-dosen-edit-scoring-page .aspect-num {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        background: rgb(var(--primary-600));
        color: #fff;
        font-weight: 700;
        font-size: 0.8125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .fi-dosen-edit-scoring-page .aspect-name {
        flex: 1;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(17 24 39);
    }

    .dark .fi-dosen-edit-scoring-page .aspect-name {
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .aspect-select {
        width: 7.5rem;
        flex-shrink: 0;
    }

    .fi-dosen-edit-scoring-page .fi-scoring-select {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid rgb(209 213 219);
        background: #fff;
        padding: 0.375rem 0.625rem;
        font-size: 0.875rem;
        color: rgb(17 24 39);
    }

    .dark .fi-dosen-edit-scoring-page .fi-scoring-select {
        border-color: rgb(255 255 255 / 0.2);
        background: rgb(255 255 255 / 0.05);
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .dbs-result {
        background: rgb(249 250 251);
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 1.125rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin: 1.25rem 0;
        flex-wrap: wrap;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-result {
        background: rgb(255 255 255 / 0.03);
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .dbs-result-label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgb(107 114 128);
        margin-bottom: 0.125rem;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-result-label {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .dbs-result-letter {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1;
        color: rgb(var(--primary-600));
        min-width: 3.75rem;
    }

    .fi-dosen-edit-scoring-page .dbs-result-number {
        font-size: 1.125rem;
        font-weight: 700;
        color: rgb(17 24 39);
    }

    .dark .fi-dosen-edit-scoring-page .dbs-result-number {
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .dbs-result-letter.dbs-result-fail,
    .fi-dosen-edit-scoring-page .dbs-result-number.dbs-result-fail {
        color: rgb(220 38 38) !important;
        -webkit-text-fill-color: rgb(220 38 38);
    }

    .dark .fi-dosen-edit-scoring-page .dbs-result-letter.dbs-result-fail,
    .dark .fi-dosen-edit-scoring-page .dbs-result-number.dbs-result-fail {
        color: rgb(248 113 113) !important;
        -webkit-text-fill-color: rgb(248 113 113);
    }

    .fi-dosen-edit-scoring-page .dbs-result-divider {
        width: 1px;
        height: 2.75rem;
        background: rgb(229 231 235);
        flex-shrink: 0;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-result-divider {
        background: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .revision-card {
        background: rgb(249 250 251);
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 1rem 1.125rem;
        margin-bottom: 0.625rem;
    }

    .dark .fi-dosen-edit-scoring-page .revision-card {
        background: rgb(255 255 255 / 0.03);
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .revision-card-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(17 24 39);
        margin-bottom: 0.75rem;
    }

    .dark .fi-dosen-edit-scoring-page .revision-card-title {
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .rev-toggle-group {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        column-gap: 1rem;
        row-gap: 0.875rem;
        position: relative;
    }

    @media (max-width: 640px) {
        .fi-dosen-edit-scoring-page .rev-toggle-group {
            grid-template-columns: 1fr;
        }
    }

    .fi-dosen-edit-scoring-page .btn-check {
        position: absolute;
        clip: rect(0, 0, 0, 0);
        pointer-events: none;
    }

    .fi-dosen-edit-scoring-page .rev-pill {
        width: 100%;
        text-align: center;
        cursor: pointer;
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        font-weight: 700;
        font-size: 0.875rem;
        border: 1px solid rgb(209 213 219);
        background: #fff;
        color: rgb(107 114 128);
        transition: all 0.2s;
        user-select: none;
    }

    .dark .fi-dosen-edit-scoring-page .rev-pill {
        background: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.15);
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .btn-check:checked + .rev-pill-tidak {
        background: rgb(22 163 74);
        border-color: rgb(22 163 74);
        color: #fff;
    }

    .fi-dosen-edit-scoring-page .btn-check:checked + .rev-pill-minor {
        background: rgb(217 119 6);
        border-color: rgb(217 119 6);
        color: #fff;
    }

    .fi-dosen-edit-scoring-page .btn-check:checked + .rev-pill-mayor {
        background: rgb(220 38 38);
        border-color: rgb(220 38 38);
        color: #fff;
    }

    .fi-dosen-edit-scoring-page .notes-card {
        background: #fff;
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 1rem 1.125rem;
        margin-bottom: 0.625rem;
        border-left: 3px solid rgb(249 115 22);
    }

    .fi-dosen-edit-scoring-page .notes-card-minor {
        border-left-color: rgb(245 158 11);
    }

    .fi-dosen-edit-scoring-page .notes-card-mayor {
        border-left-color: rgb(239 68 68);
    }

    .dark .fi-dosen-edit-scoring-page .notes-card {
        background: rgb(255 255 255 / 0.03);
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .notes-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.625rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .fi-dosen-edit-scoring-page .notes-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(17 24 39);
    }

    .dark .fi-dosen-edit-scoring-page .notes-title {
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .notes-hint {
        font-size: 0.8125rem;
        color: rgb(107 114 128);
        margin-top: 0.5rem;
    }

    .dark .fi-dosen-edit-scoring-page .notes-hint {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .fi-scoring-textarea {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid rgb(209 213 219);
        background: #fff;
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        color: rgb(17 24 39);
        line-height: 1.5;
        resize: vertical;
    }

    .dark .fi-dosen-edit-scoring-page .fi-scoring-textarea {
        border-color: rgb(255 255 255 / 0.2);
        background: rgb(255 255 255 / 0.05);
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .autosave-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3125rem;
        background: rgb(243 244 246);
        border: 1px solid rgb(229 231 235);
        border-radius: 9999px;
        padding: 0.1875rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(107 114 128);
    }

    .fi-dosen-edit-scoring-page .autosave-pill.saving {
        background: rgb(239 246 255);
        border-color: rgb(191 219 254);
        color: rgb(29 78 216);
    }

    .fi-dosen-edit-scoring-page .autosave-pill.saved {
        background: rgb(240 253 244);
        border-color: rgb(187 247 208);
        color: rgb(21 128 61);
    }

    .fi-dosen-edit-scoring-page .autosave-pill.unsaved {
        background: rgb(255 251 235);
        border-color: rgb(253 230 138);
        color: rgb(180 83 9);
    }

    .dark .fi-dosen-edit-scoring-page .autosave-pill {
        background: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.1);
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .autosave-dot {
        width: 0.4375rem;
        height: 0.4375rem;
        border-radius: 9999px;
        background: currentColor;
        flex-shrink: 0;
    }

    .fi-dosen-edit-scoring-page .draft-banner {
        background: rgb(239 246 255);
        border: 1px solid rgb(191 219 254);
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.625rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .dark .fi-dosen-edit-scoring-page .draft-banner {
        background: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.15);
    }

    .fi-dosen-edit-scoring-page .draft-banner-text {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(29 78 216);
    }

    .dark .fi-dosen-edit-scoring-page .draft-banner-text {
        color: rgb(147 197 253);
    }

    .fi-dosen-edit-scoring-page .draft-banner-actions {
        display: flex;
        gap: 0.5rem;
    }

    .fi-dosen-edit-scoring-page .draft-btn-restore,
    .fi-dosen-edit-scoring-page .draft-btn-dismiss {
        border-radius: 0.4375rem;
        padding: 0.3125rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .fi-dosen-edit-scoring-page .draft-btn-restore {
        background: rgb(var(--primary-600));
        color: #fff;
        border: none;
    }

    .fi-dosen-edit-scoring-page .draft-btn-dismiss {
        background: transparent;
        color: rgb(107 114 128);
        border: 1px solid rgb(209 213 219);
    }

    .dark .fi-dosen-edit-scoring-page .draft-btn-dismiss {
        color: rgb(156 163 175);
        border-color: rgb(255 255 255 / 0.2);
    }

    .fi-dosen-edit-scoring-page .decision-notice {
        border-radius: 0.75rem;
        padding: 0.875rem 1.125rem;
        display: flex;
        align-items: center;
        gap: 0.875rem;
    }

    .fi-dosen-edit-scoring-page .decision-notice-pass {
        background: rgb(240 253 244);
        border: 1px solid rgb(134 239 172);
    }

    .fi-dosen-edit-scoring-page .decision-notice-fail {
        background: rgb(255 241 242);
        border: 1px solid rgb(253 164 175);
    }

    .fi-dosen-edit-scoring-page .decision-notice-pending {
        background: rgb(249 250 251);
        border: 1px dashed rgb(203 213 225);
    }

    .dark .fi-dosen-edit-scoring-page .decision-notice-pass {
        background: rgb(22 163 74 / 0.15);
        border-color: rgb(34 197 94 / 0.35);
    }

    .dark .fi-dosen-edit-scoring-page .decision-notice-fail {
        background: rgb(225 29 72 / 0.12);
        border-color: rgb(244 63 94 / 0.35);
    }

    .dark .fi-dosen-edit-scoring-page .decision-notice-pending {
        background: rgb(255 255 255 / 0.03);
        border-color: rgb(255 255 255 / 0.15);
    }

    .fi-dosen-edit-scoring-page .decision-notice-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        font-weight: 800;
    }

    .fi-dosen-edit-scoring-page .decision-notice-pass .decision-notice-icon {
        background: rgb(22 163 74);
        color: #fff;
    }

    .fi-dosen-edit-scoring-page .decision-notice-fail .decision-notice-icon {
        background: rgb(225 29 72);
        color: #fff;
    }

    .fi-dosen-edit-scoring-page .decision-notice-pending .decision-notice-icon {
        background: rgb(229 231 235);
        color: rgb(107 114 128);
    }

    .dark .fi-dosen-edit-scoring-page .decision-notice-pending .decision-notice-icon {
        background: rgb(255 255 255 / 0.1);
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .decision-notice-verdict {
        font-size: 0.9375rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .fi-dosen-edit-scoring-page .decision-notice-pass .decision-notice-verdict {
        color: rgb(21 128 61);
    }

    .fi-dosen-edit-scoring-page .decision-notice-fail .decision-notice-verdict {
        color: rgb(190 18 60);
    }

    .fi-dosen-edit-scoring-page .decision-notice-pending .decision-notice-verdict {
        color: rgb(107 114 128);
    }

    .fi-dosen-edit-scoring-page .decision-notice-sub {
        font-size: 0.8125rem;
        font-weight: 500;
        margin-top: 0.125rem;
        color: rgb(107 114 128);
    }

    .dark .fi-dosen-edit-scoring-page .decision-notice-sub {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog {
        width: min(720px, calc(100vw - 32px));
        border: none;
        border-radius: 0.75rem;
        padding: 0;
        box-shadow: 0 20px 50px rgb(0 0 0 / 0.25);
        background: #fff;
        color: rgb(17 24 39);
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-dialog {
        background: rgb(17 24 39);
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog::backdrop {
        background: rgb(0 0 0 / 0.5);
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgb(229 231 235);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-dialog-header {
        border-bottom-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog-title {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog-close {
        background: rgb(243 244 246);
        border: none;
        color: rgb(55 65 81);
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        cursor: pointer;
        font-size: 1.125rem;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-dialog-close {
        background: rgb(255 255 255 / 0.1);
        color: rgb(243 244 246);
    }

    .fi-dosen-edit-scoring-page .dbs-history-dialog-body {
        padding: 1.125rem 1.25rem 1.375rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    .fi-dosen-edit-scoring-page .dbs-history-item {
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        padding: 0.875rem 1rem;
        margin-bottom: 0.75rem;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-item {
        border-color: rgb(255 255 255 / 0.1);
    }

    .fi-dosen-edit-scoring-page .dbs-history-item:last-child {
        margin-bottom: 0;
    }

    .fi-dosen-edit-scoring-page .dbs-history-meta {
        font-size: 0.8125rem;
        color: rgb(107 114 128);
        margin-bottom: 0.5rem;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-meta {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .dbs-history-title {
        font-size: 0.9375rem;
        font-weight: 600;
        margin-bottom: 0.625rem;
        line-height: 1.5;
    }

    .fi-dosen-edit-scoring-page .dbs-history-grid {
        display: grid;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .fi-dosen-edit-scoring-page .dbs-history-grid dt {
        color: rgb(107 114 128);
        font-weight: 600;
    }

    .dark .fi-dosen-edit-scoring-page .dbs-history-grid dt {
        color: rgb(156 163 175);
    }

    .fi-dosen-edit-scoring-page .dbs-history-grid dd {
        margin: 0;
    }
</style>
