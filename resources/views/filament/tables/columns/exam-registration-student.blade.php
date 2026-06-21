@php
    /** @var \App\Models\ExamRegistration $record */
    $record = $getRecord();
    $name = $record->student?->name ?? '—';
    $hasPhone = \App\Support\ExamRegistrationWhatsappLinks::phoneDigits($record) !== null;
@endphp

<div class="er-student-col">
    <div class="er-student-name">{{ $name }}</div>

    <div class="er-student-wa">
        @if ($hasPhone)
            <a href="{{ \App\Support\ExamRegistrationWhatsappLinks::simpleChatUrl($record) }}"
                class="er-wa-btn er-wa-pesan" target="_blank" rel="noopener noreferrer"
                title="Pesan WhatsApp">Pesan</a>
        @else
            <span class="er-wa-btn er-wa-pesan er-wa-disabled" title="Nomor HP belum diisi">Pesan</span>
        @endif

        @php
            $inviteTitle = $record->invited_at
                ? 'Undangan terkirim ' . $record->invited_at->locale('id')->isoFormat('D MMM Y, HH.mm') . ' — kirim ulang'
                : 'Undang ujian via WhatsApp';
            $inviteClass = 'er-wa-btn er-wa-undang' . ($record->invited_at ? ' er-wa-done' : '');
        @endphp
        @if ($hasPhone)
            <a href="{{ route('examregistrations.whatsapp-invite', $record) }}"
                class="{{ $inviteClass }}" target="_blank" rel="noopener noreferrer"
                title="{{ $inviteTitle }}">Undang</a>
        @else
            <span class="{{ $inviteClass }} er-wa-disabled" title="Nomor HP belum diisi">Undang</span>
        @endif

        @if ($record->hasScheduleChangedSinceInvite())
            @php
                $ralatTitle = $record->corrected_at
                    ? 'Ralat terkirim ' . $record->corrected_at->locale('id')->isoFormat('D MMM Y, HH.mm') . ' — kirim ulang'
                    : 'Ralat jadwal ujian via WhatsApp';
                $ralatClass = 'er-wa-btn er-wa-ralat' . ($record->corrected_at ? ' er-wa-done' : '');
            @endphp
            @if ($hasPhone)
                <a href="{{ route('examregistrations.whatsapp-ralat', $record) }}"
                    class="{{ $ralatClass }}" target="_blank" rel="noopener noreferrer"
                    title="{{ $ralatTitle }}">Ralat</a>
            @else
                <span class="{{ $ralatClass }} er-wa-disabled" title="Nomor HP belum diisi">Ralat</span>
            @endif
        @endif
    </div>

    @if ($record->invited_at || $record->corrected_at)
        <div class="er-student-status">
            @if ($record->invited_at)
                <span>✓ Undang {{ $record->invited_at->locale('id')->isoFormat('D MMM Y, HH.mm') }}</span>
            @endif
            @if ($record->corrected_at)
                <span>✓ Ralat {{ $record->corrected_at->locale('id')->isoFormat('D MMM Y, HH.mm') }}</span>
            @endif
        </div>
    @endif
</div>
