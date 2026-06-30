<x-filament-panels::page>
    <x-filament::section heading="Usulan Calon Pembimbing">
        <div class="mb-4 rounded-lg border border-info-300 bg-info-50 p-3 text-sm text-info-800">
            NUIR: {{ $this->submission->title }} (v{{ $this->submission->version }})
            @if ($this->previousRejected)
                <div class="mt-2">NUIR Anda sudah diverifikasi (v{{ $this->submission->version }}). Anda dapat menggunakan NUIR yang sama.</div>
            @endif
        </div>

        <form method="POST" action="{{ route('nuir.proposal.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="nuir_submission_id" value="{{ $this->submission->id }}">

            @if ($this->lockedSeats['guide1'])
                <input type="hidden" name="guide1_id" value="{{ $this->lockedSeats['guide1']['id'] }}">
                <p class="text-sm text-gray-600">
                    <strong>Pembimbing 1 (terkunci):</strong>
                    {{ $this->lecturersP1->firstWhere('id', $this->lockedSeats['guide1']['id'])?->name ?? 'Dosen #' . $this->lockedSeats['guide1']['id'] }}
                </p>
            @endif

            @if ($this->lockedSeats['guide2'])
                <input type="hidden" name="guide2_id" value="{{ $this->lockedSeats['guide2']['id'] }}">
                <p class="text-sm text-gray-600">
                    <strong>Pembimbing 2 (terkunci):</strong>
                    {{ $this->lecturersP2->firstWhere('id', $this->lockedSeats['guide2']['id'])?->name ?? 'Dosen #' . $this->lockedSeats['guide2']['id'] }}
                </p>
            @endif

            @if (! $this->lockedSeats['guide1'])
            <div>
                <label class="mb-1 block text-sm font-medium" for="guide1_id">Pembimbing 1</label>
                <select
                    id="guide1_id"
                    name="guide1_id"
                    class="fi-input block w-full rounded-lg border-gray-300 @error('guide1_id') border-danger-600 @enderror"
                    required
                >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($this->lecturersP1 as $lecturer)
                        <option value="{{ $lecturer->id }}" @selected(old('guide1_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                    @endforeach
                </select>
                @error('guide1_id')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            @if (! $this->lockedSeats['guide2'])
            <div>
                <label class="mb-1 block text-sm font-medium" for="guide2_id">Pembimbing 2</label>
                <select
                    id="guide2_id"
                    name="guide2_id"
                    class="fi-input block w-full rounded-lg border-gray-300 @error('guide2_id') border-danger-600 @enderror"
                    required
                >
                    <option value="">-- Pilih Dosen --</option>
                    @foreach ($this->lecturersP2 as $lecturer)
                        <option value="{{ $lecturer->id }}" @selected(old('guide2_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                    @endforeach
                </select>
                @error('guide2_id')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="flex gap-2">
                <x-filament::button type="submit" size="sm">Kirim Usulan</x-filament::button>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Mahasiswa\Pages\NuirProposalOverview::getUrl(panel: 'mahasiswa') }}"
                    size="sm"
                    color="gray"
                >
                    Batal
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
