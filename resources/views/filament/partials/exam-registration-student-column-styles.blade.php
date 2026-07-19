.fi-resource-exam-registrations .exam-registrations-student-cell {
    max-width: 14rem;
    min-width: 9rem;
}

.fi-resource-exam-registrations .exam-registrations-student-cell .fi-ta-col-wrp > div {
    display: block;
    width: 100%;
}

.fi-resource-exam-registrations .er-student-col {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    min-width: 0;
}

.fi-resource-exam-registrations .er-student-name {
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.35;
    color: rgb(17 24 39);
    word-break: break-word;
}

.dark .fi-resource-exam-registrations .er-student-name {
    color: rgb(243 244 246);
}

.fi-resource-exam-registrations .er-student-wa {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
}

.fi-resource-exam-registrations .er-wa-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
    line-height: 1.25rem;
    letter-spacing: 0.01em;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity 0.15s ease;
}

.fi-resource-exam-registrations a.er-wa-btn:hover {
    opacity: 0.85;
}

.fi-resource-exam-registrations .er-wa-btn.er-wa-pesan {
    background: rgb(220 252 231);
    color: rgb(21 128 61);
}

.fi-resource-exam-registrations .er-wa-btn.er-wa-undang {
    background: rgb(224 242 254);
    color: rgb(3 105 161);
}

.fi-resource-exam-registrations .er-wa-btn.er-wa-ralat {
    background: rgb(254 243 199);
    color: rgb(180 83 9);
}

.fi-resource-exam-registrations .er-wa-btn.er-wa-done {
    background: rgb(243 244 246);
    color: rgb(107 114 128);
}

.dark .fi-resource-exam-registrations .er-wa-btn.er-wa-done {
    background: rgb(55 65 81);
    color: rgb(156 163 175);
}

.fi-resource-exam-registrations .er-wa-btn.er-wa-disabled {
    background: rgb(249 250 251);
    color: rgb(156 163 175);
    cursor: not-allowed;
    opacity: 0.75;
}

.fi-resource-exam-registrations .er-student-status {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    font-size: 0.625rem;
    line-height: 1.3;
    color: rgb(107 114 128);
}

.fi-resource-exam-registrations .er-student-status span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Waktu & Lokasi (App\Support\ExamScheduleFormat) — dipakai di kartu
   ExamRegistrationResource & ExamRegistrationsByDateWidget (keduanya
   reuse getCardColumns(), lihat komentar di sana). Nama file partial ini
   "student-column-styles" tapi sudah jadi lokasi CSS bersama antara
   resource & widget (sama-sama @include partial ini), jadi ditaruh di
   sini juga daripada disalin ulang di kedua blade view. */
.fi-resource-exam-registrations .exam-waktu-item {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: rgb(55 65 81);
}

.dark .fi-resource-exam-registrations .exam-waktu-item {
    color: rgb(209 213 219);
}

.fi-resource-exam-registrations .exam-waktu-icon {
    font-size: 0.6875rem;
}

.fi-resource-exam-registrations .exam-waktu-sep {
    color: rgb(156 163 175);
    margin: 0 0.1rem;
}
