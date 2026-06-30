<x-filament-panels::page>
    <div class="space-y-4">
        @if (session('success'))
            <x-filament::section>
                <p class="text-success-600">{{ session('success') }}</p>
            </x-filament::section>
        @endif

        @if (session('info'))
            <x-filament::section>
                <p class="text-info-600">{{ session('info') }}</p>
            </x-filament::section>
        @endif

        @if (session('warning'))
            <x-filament::section>
                <p class="text-warning-600">{{ session('warning') }}</p>
            </x-filament::section>
        @endif

        <x-filament::section heading="Pengajuan NUIR">
            @if ($this->stage3)
                <p>Angkatan Anda tidak memerlukan pengajuan NUIR. Pembimbing akan ditetapkan langsung oleh DBS.</p>
            @elseif ($this->closed)
                <p class="text-gray-500">NUIR belum dibuka untuk angkatan Anda.</p>
            @elseif (!$this->submission)
                <p>Anda belum memiliki pengajuan NUIR aktif.</p>
                @can('create nuir submission')
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Mahasiswa\Pages\CreateNuirSubmission::getUrl(panel: 'mahasiswa') }}"
                        size="sm"
                        class="mt-3"
                    >
                        Buat Pengajuan NUIR
                    </x-filament::button>
                @endcan
            @else
                <div class="space-y-3">
                    <p>
                        Status:
                        <x-filament::badge color="gray">{{ $this->submission->status }}</x-filament::badge>
                    </p>
                    <p><strong>Judul:</strong> {{ $this->submission->title }}</p>

                    @if ($this->submission->dbs_note && $this->submission->status !== 'revision')
                        <div class="rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800">
                            {{ $this->submission->dbs_note }}
                        </div>
                    @endif

                    @php
                        $pendingRefs = $this->submission->references()->whereNull('ref_approved')->orderBy('ref_order')->get();
                        $approvedRefs = $this->submission->references()->where('ref_approved', true)->orderBy('ref_order')->get();
                        $rejectedRefs = $this->submission->references()->where('ref_approved', false)->orderBy('ref_order')->get();
                    @endphp

                    @if ($pendingRefs->isNotEmpty() || $approvedRefs->isNotEmpty() || $rejectedRefs->isNotEmpty())
                        <div class="space-y-3 rounded-lg border border-gray-200 p-3 text-sm">
                            <h6 class="font-semibold">Status Referensi</h6>

                            @if ($pendingRefs->isNotEmpty())
                                <div class="rounded-lg border border-warning-200 bg-warning-50 p-3">
                                    <p class="mb-2 font-medium text-warning-800">Masih Direview</p>
                                    <ul class="space-y-1 text-warning-900">
                                        @foreach ($pendingRefs as $ref)
                                            <li>#{{ $ref->ref_order }}@if ($ref->indexer_name) — {{ $ref->indexer_name }}@endif</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($approvedRefs->isNotEmpty())
                                <div class="rounded-lg border border-success-200 bg-success-50 p-3">
                                    <p class="mb-2 font-medium text-success-800">Disetujui</p>
                                    <ul class="space-y-1 text-success-900">
                                        @foreach ($approvedRefs as $ref)
                                            <li>#{{ $ref->ref_order }}@if ($ref->indexer_name) — {{ $ref->indexer_name }}@endif</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($rejectedRefs->isNotEmpty())
                                <div class="rounded-lg border border-danger-200 bg-danger-50 p-3">
                                    <p class="mb-2 font-medium text-danger-800">Ditolak</p>
                                    <ul class="space-y-2 text-danger-900">
                                        @foreach ($rejectedRefs as $ref)
                                            <li>
                                                <span class="font-medium">#{{ $ref->ref_order }}</span>@if ($ref->indexer_name) — {{ $ref->indexer_name }}@endif
                                                @if (filled($ref->ref_revision_fields))
                                                    <span class="block text-xs text-danger-700">
                                                        Bagian diperbaiki: {{ \App\Support\NuirReferenceRevisionFields::labelsText($ref->ref_revision_fields) }}
                                                    </span>
                                                @endif
                                                @if ($ref->ref_note)
                                                    <span class="block text-xs text-danger-700">{{ $ref->ref_note }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($this->submission->status === 'revision')
                        <div class="rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800">
                            <strong>Diminta Revisi</strong>
                            <div>{{ $this->submission->dbs_note }}</div>
                        </div>
                        @if (!\App\Models\NuirSubmission::where('parent_submission_id', $this->submission->id)->exists())
                            <x-filament::button
                                tag="a"
                                href="{{ \App\Filament\Mahasiswa\Pages\ReviseNuirSubmission::getUrl(['record' => $this->submission], panel: 'mahasiswa') }}"
                                size="sm"
                                color="warning"
                            >
                                Buat Revisi (v{{ $this->submission->version + 1 }})
                            </x-filament::button>
                        @endif
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @if ($this->submission->isEditable())
                            <x-filament::button
                                tag="a"
                                href="{{ \App\Filament\Mahasiswa\Pages\EditNuirSubmission::getUrl(['record' => $this->submission], panel: 'mahasiswa') }}"
                                size="sm"
                                color="gray"
                            >
                                Edit
                            </x-filament::button>
                        @elseif ($this->submission->isReferencesEditable())
                            <x-filament::button
                                tag="a"
                                href="{{ \App\Filament\Mahasiswa\Pages\EditNuirSubmission::getUrl(['record' => $this->submission], panel: 'mahasiswa') }}"
                                size="sm"
                                color="gray"
                            >
                                Kelola Referensi
                            </x-filament::button>
                        @endif
                        @if ($this->submission->status === 'draft')
                            <form action="{{ route('nuir.submission.submit', $this->submission) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <x-filament::button type="submit" size="sm">
                                    Kirim ke DBS
                                </x-filament::button>
                            </form>
                        @endif
                    </div>

                    @if ($this->versions->count() > 1)
                        <div>
                            <h6 class="mb-2 font-semibold">Riwayat Versi</h6>
                            <ul class="divide-y rounded-lg border text-sm">
                                @foreach ($this->versions as $version)
                                    <li class="px-3 py-2">
                                        v{{ $version->version }} — {{ $version->status }}
                                        @if ($version->dbs_note)
                                            <small class="text-gray-500">({{ $version->dbs_note }})</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($this->revisionHistory->isNotEmpty())
                        <div>
                            <h6 class="mb-2 font-semibold">Histori Revisi</h6>
                            <ul class="divide-y rounded-lg border text-sm">
                                @foreach ($this->revisionHistory as $event)
                                    <li class="px-3 py-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-filament::badge color="gray">v{{ $event->submission_version }}</x-filament::badge>
                                            <span class="font-medium">{{ $event->subjectLabel() }}</span>
                                            <span class="text-xs text-gray-500">{{ $event->recorded_at?->format('d-m-Y H:i') }}</span>
                                        </div>
                                        <p class="mt-1 text-gray-700">{{ $event->note }}</p>
                                        @if ($event->actor)
                                            <p class="text-xs text-gray-500">Oleh: {{ $event->actor->name }} ({{ $event->actor_role }})</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
