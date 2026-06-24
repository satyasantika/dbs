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

                    @if ($this->submission->dbs_note)
                        <div class="rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800">
                            {{ $this->submission->dbs_note }}
                        </div>
                    @endif

                    @if ($this->submission->status === 'revision')
                        <div class="rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800">
                            <strong>Diminta Revisi</strong>
                            <div>{{ $this->submission->dbs_note }}</div>
                        </div>
                        @php($rejected = $this->submission->references()->where('ref_approved', false)->get())
                        @if ($rejected->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="px-2 py-1 text-left">#</th>
                                            <th class="px-2 py-1 text-left">Catatan DBS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rejected as $ref)
                                            <tr class="border-b">
                                                <td class="px-2 py-1">{{ $ref->ref_order }}</td>
                                                <td class="px-2 py-1">{{ $ref->ref_note }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
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
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
