<div class="space-y-4 py-2">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Klik tombol berikut untuk membuka WhatsApp dan mengirim informasi hasil ujian kepada mahasiswa.
        Setelah mengirim pesan, konfirmasi di bawah untuk menandai sebagai terkirim.
    </p>

    @if ($waUrl)
        <a
            href="{{ $waUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none"
        >
            <svg viewBox="0 0 31 30" height="20" fill="none" class="shrink-0">
                <path d="M30.3139 14.3245C30.174 10.4932 28.5594 6.864 25.8073 4.1948C23.0552 1.52559 19.3784 0.0227244 15.5446 4.10118e-06H15.4722C12.8904 -0.00191309 10.3527 0.668375 8.10857 1.94491C5.86449 3.22145 3.99142 5.06026 2.67367 7.28039C1.35592 9.50053 0.6389 12.0255 0.593155 14.6068C0.547411 17.1882 1.17452 19.737 2.41278 22.0024L1.09794 29.8703C1.0958 29.8865 1.09712 29.9029 1.10182 29.9185C1.10651 29.9341 1.11448 29.9485 1.12518 29.9607C1.13588 29.973 1.14907 29.9828 1.16387 29.9896C1.17867 29.9964 1.19475 29.9999 1.21103 30H1.23365L9.01561 28.269C11.0263 29.2344 13.2282 29.7353 15.4586 29.7346C15.6004 29.7346 15.7421 29.7346 15.8838 29.7346C17.8458 29.6786 19.7773 29.2346 21.5667 28.4282C23.3562 27.6218 24.9682 26.469 26.3098 25.0363C27.6514 23.6036 28.696 21.9194 29.3832 20.0809C30.0704 18.2423 30.3867 16.2859 30.3139 14.3245Z" fill="currentColor"/>
            </svg>
            Buka WhatsApp — {{ $record->student?->name ?? '-' }}
        </a>
    @else
        <p class="text-sm text-danger-600">Nomor telepon mahasiswa tidak ditemukan. Periksa data mahasiswa terlebih dahulu.</p>
    @endif

    @if ($record->sent_at)
        <p class="text-xs text-gray-500">Terakhir terkirim: {{ $record->sent_at->isoFormat('D MMMM Y, HH:mm') }}</p>
    @endif
</div>
