@include('filament.mahasiswa.pages.partials.nuir-submission-form-script')
@include('filament.mahasiswa.pages.partials.nuir-submission-form-styles')

<x-filament-panels::page class="fi-nuir-submission-form">
    <div
        x-data="nuirSubmissionForm(@js([
            'references' => $this->getReferencesForForm(),
            'rejectedRefs' => $this->rejectedRefs,
            'indexers' => $this->getIndexerOptions(),
            'maxWords' => $this->getNuiMaxWords(),
            'charLimits' => $this->getNuiCharLimits(),
        ]))"
        x-init="initNuiFields()"
    >
        <x-filament::section>
            <p><strong>Mahasiswa:</strong> {{ auth()->user()->name }} ({{ auth()->user()->username }})</p>

            <form
                method="POST"
                action="{{ $this->revisionParent ? route('nuir.submission.store-revision', $this->revisionParent) : ($this->submission->id ? route('nuir.submission.update', $this->submission) : route('nuir.submission.store')) }}"
                class="mt-4 space-y-6"
            >
                @csrf
                @if ($this->submission->id)
                    @method('PUT')
                @endif

                @foreach (['title' => 'Judul', 'novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                    @if ($field === 'title' || $this->stage === 1)
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium" for="{{ $field }}">{{ $label }}</label>
                                @if ($field !== 'title')
                                    <span
                                        class="text-xs text-gray-500"
                                        x-text="(() => {
                                            const value = $refs['{{ $field }}']?.value ?? '';
                                            const charLimit = charLimits['{{ $field }}'];
                                            if (charLimit) {
                                                return `${charCount(value)} / ${charLimit} karakter`;
                                            }
                                            return `${wordCount(value)} / ${maxWords} kata`;
                                        })()"
                                    ></span>
                                @endif
                            </div>
                            <textarea
                                id="{{ $field }}"
                                name="{{ $field }}"
                                x-ref="{{ $field }}"
                                @if ($field === 'title')
                                    rows="2"
                                    class="fi-input block w-full rounded-lg border-gray-300 shadow-sm @error($field) border-danger-600 @enderror"
                                @else
                                    rows="1"
                                    data-nui-autoresize
                                    x-init="$nextTick(() => autoResize({ target: $el }))"
                                    @input="autoResize($event)"
                                    class="fi-input nui-field block w-full rounded-lg border-gray-300 shadow-sm @error($field) border-danger-600 @enderror"
                                @endif
                                @if($this->stage === 1 || $field === 'title') required @endif
                            >{{ old($field, $this->submission->{$field}) }}</textarea>
                            @error($field)
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                @endforeach

                @if ($this->stage === 1)
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h6 class="text-base font-semibold">Referensi</h6>
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                x-show="nextOrder() !== null"
                                @click="openAddModal()"
                            >
                                Tambah Referensi
                            </x-filament::button>
                        </div>

                        <template x-if="filledOrders().length === 0">
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                Belum ada referensi. Klik <strong>Tambah Referensi</strong> untuk menambahkan (maks. 10).
                            </div>
                        </template>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <template x-for="order in filledOrders()" :key="order">
                                <div
                                    class="reference-card"
                                    :class="rejectedRefs[order] ? 'reference-card--rejected' : ''"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="font-semibold">Referensi #<span x-text="order"></span></h4>
                                            <p class="text-sm text-gray-500" x-show="references[order].indexer_name" x-text="references[order].indexer_name"></p>
                                        </div>
                                        <div class="flex shrink-0 gap-2">
                                            <x-filament::button type="button" size="xs" color="gray" @click="openEditModal(order)">
                                                Edit
                                            </x-filament::button>
                                            <x-filament::button type="button" size="xs" color="danger" @click="removeReference(order)">
                                                Hapus
                                            </x-filament::button>
                                        </div>
                                    </div>

                                    <template x-if="rejectedRefs[order]">
                                        <p class="mb-2 text-xs text-danger-600" x-text="'Ditolak DBS: ' + rejectedRefs[order]"></p>
                                    </template>

                                    <dl class="space-y-2 text-sm">
                                        <div x-show="references[order].link_ojs">
                                            <dt class="font-medium text-gray-500">Link OJS</dt>
                                            <dd class="break-all" x-text="references[order].link_ojs"></dd>
                                        </div>
                                        <div x-show="references[order].link_index">
                                            <dt class="font-medium text-gray-500">Link Index</dt>
                                            <dd class="break-all" x-text="references[order].link_index"></dd>
                                        </div>
                                        <div x-show="references[order].link_drive">
                                            <dt class="font-medium text-gray-500">Link Drive</dt>
                                            <dd class="break-all" x-text="references[order].link_drive"></dd>
                                        </div>
                                        <div x-show="references[order].quote">
                                            <dt class="font-medium text-gray-500">Kutipan</dt>
                                            <dd class="whitespace-pre-wrap text-gray-700 dark:text-gray-300" x-text="references[order].quote"></dd>
                                        </div>
                                        <div x-show="references[order].relevance">
                                            <dt class="font-medium text-gray-500">Relevansi</dt>
                                            <dd class="whitespace-pre-wrap text-gray-700 dark:text-gray-300" x-text="references[order].relevance"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </template>
                        </div>

                        @for ($order = 1; $order <= 10; $order++)
                            <template x-if="isReferenceFilled(references[{{ $order }}])">
                                <div class="hidden">
                                    <input type="hidden" name="references[{{ $order }}][link_ojs]" x-model="references[{{ $order }}].link_ojs">
                                    <input type="hidden" name="references[{{ $order }}][indexer_name]" x-model="references[{{ $order }}].indexer_name">
                                    <input type="hidden" name="references[{{ $order }}][link_index]" x-model="references[{{ $order }}].link_index">
                                    <input type="hidden" name="references[{{ $order }}][link_drive]" x-model="references[{{ $order }}].link_drive">
                                    <input type="hidden" name="references[{{ $order }}][quote]" x-model="references[{{ $order }}].quote">
                                    <input type="hidden" name="references[{{ $order }}][relevance]" x-model="references[{{ $order }}].relevance">
                                </div>
                            </template>
                        @endfor
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

        <div x-show="modalOpen" x-cloak class="reference-modal-backdrop" @click="closeModal()"></div>

        <div x-show="modalOpen" x-cloak class="reference-modal-panel" @keydown.escape.window="closeModal()">
            <div class="reference-modal-content" @click.stop>
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold" x-text="editingOrder ? ('Referensi #' + editingOrder) : 'Referensi'"></h3>
                        <p class="text-sm text-gray-500">Isi detail referensi. Kutipan dan relevansi dapat ditulis lebih panjang di sini.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="closeModal()">✕</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Link OJS</label>
                        <input type="url" class="fi-input block w-full rounded-lg border-gray-300" x-model="modalForm.link_ojs">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Indexer</label>
                        <select class="fi-input block w-full rounded-lg border-gray-300" x-model="modalForm.indexer_name">
                            <option value="">-- Pilih indexer --</option>
                            <template x-for="indexer in indexers" :key="indexer">
                                <option :value="indexer" x-text="indexer"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Link Index</label>
                        <input type="url" class="fi-input block w-full rounded-lg border-gray-300" x-model="modalForm.link_index">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Link Drive</label>
                        <input type="url" class="fi-input block w-full rounded-lg border-gray-300" x-model="modalForm.link_drive">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Kutipan</label>
                        <textarea rows="8" class="fi-input reference-modal-textarea block w-full rounded-lg border-gray-300" x-model="modalForm.quote"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Relevansi</label>
                        <textarea rows="8" class="fi-input reference-modal-textarea block w-full rounded-lg border-gray-300" x-model="modalForm.relevance"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-filament::button type="button" size="sm" color="gray" @click="closeModal()">
                        Batal
                    </x-filament::button>
                    <x-filament::button type="button" size="sm" @click="saveModal()">
                        Simpan Referensi
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
