<x-filament-panels::page>
    <x-filament::section>
        <p><strong>Mahasiswa:</strong> {{ auth()->user()->name }} ({{ auth()->user()->username }})</p>

        <form
            method="POST"
            action="{{ $this->revisionParent ? route('nuir.submission.store-revision', $this->revisionParent) : ($this->submission->id ? route('nuir.submission.update', $this->submission) : route('nuir.submission.store')) }}"
            class="mt-4 space-y-4"
        >
            @csrf
            @if ($this->submission->id)
                @method('PUT')
            @endif

            @foreach (['title' => 'Judul', 'novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                @if ($field === 'title' || $this->stage === 1)
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="{{ $field }}">{{ $label }}</label>
                        <textarea
                            id="{{ $field }}"
                            name="{{ $field }}"
                            rows="{{ $field === 'title' ? 2 : 4 }}"
                            class="fi-input block w-full rounded-lg border-gray-300 shadow-sm @error($field) border-danger-600 @enderror"
                            @if($this->stage === 1 || $field === 'title') required @endif
                        >{{ old($field, $this->submission->{$field}) }}</textarea>
                        @error($field)
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            @endforeach

            @if ($this->stage === 1)
                <h6 class="font-semibold">Referensi (1-10)</h6>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-2 py-1">#</th>
                                <th class="px-2 py-1">Link OJS</th>
                                <th class="px-2 py-1">Indexer</th>
                                <th class="px-2 py-1">Link Index</th>
                                <th class="px-2 py-1">Link Drive</th>
                                <th class="px-2 py-1">Kutipan</th>
                                <th class="px-2 py-1">Relevansi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($indexers = ['WoS', 'Scopus', 'Thomson', 'Elsevier', 'Springer', 'Wiley', 'Taylor&Francis', 'DOAJ', 'Sinta 2'])
                            @for ($i = 1; $i <= 10; $i++)
                                @php($ref = $this->submission->references->firstWhere('ref_order', $i))
                                <tr @class(['bg-danger-50' => isset($this->rejectedRefs[$i])])>
                                    <td class="px-2 py-1 align-top">
                                        {{ $i }}
                                        @if (isset($this->rejectedRefs[$i]))
                                            <div class="text-xs text-danger-600">ditolak DBS: {{ $this->rejectedRefs[$i] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-2 py-1"><input type="text" class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][link_ojs]" value="{{ old("references.$i.link_ojs", $ref?->link_ojs ?? '') }}"></td>
                                    <td class="px-2 py-1">
                                        <select class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][indexer_name]">
                                            <option value="">--</option>
                                            @foreach ($indexers as $indexer)
                                                <option value="{{ $indexer }}" @selected(old("references.$i.indexer_name", $ref?->indexer_name ?? '') === $indexer)>{{ $indexer }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-1"><input type="text" class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][link_index]" value="{{ old("references.$i.link_index", $ref?->link_index ?? '') }}"></td>
                                    <td class="px-2 py-1"><input type="text" class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][link_drive]" value="{{ old("references.$i.link_drive", $ref?->link_drive ?? '') }}"></td>
                                    <td class="px-2 py-1"><textarea class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][quote]" rows="2">{{ old("references.$i.quote", $ref?->quote ?? '') }}</textarea></td>
                                    <td class="px-2 py-1"><textarea class="fi-input w-full rounded-lg border-gray-300 text-sm" name="references[{{ $i }}][relevance]" rows="2">{{ old("references.$i.relevance", $ref?->relevance ?? '') }}</textarea></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex gap-2">
                <x-filament::button type="submit" size="sm">
                    {{ $this->stage === 2 ? 'Simpan' : 'Simpan Draft' }}
                </x-filament::button>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Mahasiswa\Pages\NuirSubmissionOverview::getUrl(panel: 'mahasiswa') }}"
                    size="sm"
                    color="gray"
                >
                    Batal
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
