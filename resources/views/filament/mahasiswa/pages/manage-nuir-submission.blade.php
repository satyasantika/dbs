@include('filament.mahasiswa.pages.partials.nuir-submission-form-script')
@include('filament.mahasiswa.pages.partials.nuir-submission-form-styles')

<x-filament-panels::page class="fi-nuir-submission-form">
    <div
        x-data="nuirSubmissionForm(@js([
            'references' => $this->getReferencesForForm(),
            'rejectedRefs' => $this->rejectedRefs,
            'refStatuses' => $this->getRefStatusesForForm(),
            'refNotes' => $this->getRefNotesForForm(),
            'indexers' => $this->getIndexerOptions(),
            'minReferences' => $this->getMinReferences(),
            'maxReferences' => $this->getMaxReferences(),
            'maxWords' => $this->getNuiMaxWords(),
            'wordLimits' => $this->getNuiWordLimits(),
            'charLimits' => $this->getNuiCharLimits(),
        ]))"
        x-init="initNuiFields()"
    >
        <x-filament::section>
            <p><strong>Mahasiswa:</strong> {{ auth()->user()->name }} ({{ auth()->user()->username }})</p>

            @if ($this->submission->isTitleSlot())
                <div class="mt-3 rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-800">
                    Slot judul sudah dibuat. Lengkapi Novelty, Urgency, Impact, dan referensi untuk melanjutkan.
                </div>
            @endif

            @if ($this->partialNuiOnly)
                <div class="mt-3 rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-800">
                    Perbaiki elemen NUI yang diminta revisi pembimbing sebelum melanjutkan usulan pembimbing.
                </div>
            @endif

            @if ($this->referencesOnly && ! $this->partialNuiOnly)
                <div class="mt-3 rounded-lg border border-info-200 bg-info-50 px-3 py-2 text-sm text-info-800">
                    Konten NUIR sudah diajukan. Anda dapat menambah atau memperbaiki referensi selama kuota masih tersedia.
                </div>
                <p class="mt-3"><strong>Judul:</strong> {{ $this->submission->title }}</p>
            @endif

            <form
                method="POST"
                action="{{ $this->revisionParent ? route('nuir.submission.store-revision', $this->revisionParent) : ($this->submission->id ? route('nuir.submission.update', $this->submission) : route('nuir.submission.store')) }}"
                class="mt-4 space-y-6"
            >
                @csrf
                @if ($this->submission->id)
                    @method('PUT')
                @endif
                @if ($this->titleSlotOnly)
                    <input type="hidden" name="title_only" value="1">
                @endif

                @if (! $this->referencesOnly || $this->partialNuiOnly)
                    @foreach (['title' => 'Judul', 'novelty' => 'Novelty', 'urgency' => 'Urgency', 'impact' => 'Impact'] as $field => $label)
                        @php
                            $showField = $this->partialNuiOnly
                                ? in_array($field, $this->rejectedNuiFields, true)
                                : ($field === 'title' || ($this->stage === 1 && ! $this->titleSlotOnly));
                        @endphp
                        @if ($showField)
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
                @endif

                @if (($this->stage === 1 && ! $this->titleSlotOnly) || $this->referencesOnly || $this->partialNuiOnly)
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h6 class="text-base font-semibold">Referensi</h6>
                                <p class="text-xs text-gray-500">
                                    Minimum {{ $this->getMinReferences() }} referensi disetujui atau menunggu review.
                                    <span x-text="`${countByStatus('approved')} disetujui, ${countByStatus('pending')} menunggu review`"></span>
                                </p>
                            </div>
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                x-show="canAddReference()"
                                @click="openAddModal()"
                            >
                                Tambah Referensi
                            </x-filament::button>
                        </div>

                        <p
                            x-show="!canAddReference() && nextOrder() !== null"
                            x-cloak
                            class="rounded-lg border border-info-200 bg-info-50 px-3 py-2 text-sm text-info-800"
                        >
                            Kuota referensi tercapai
                            (<span x-text="minReferences"></span> disetujui atau menunggu review).
                            Hapus atau perbaiki referensi ditolak untuk menambah slot baru.
                        </p>

                        <template x-if="editableOrders().length === 0 && groupedOrders('approved').length === 0">
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                Belum ada referensi. Klik <strong>Tambah Referensi</strong> untuk menambahkan
                                (minimum {{ $this->getMinReferences() }}, maks. 10).
                            </div>
                        </template>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <template x-for="order in editableOrders()" :key="'edit-' + order">
                                <div
                                    class="reference-card"
                                    :class="reviewStatus(order) === 'rejected' ? 'reference-card--rejected' : ''"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="font-semibold">Referensi #<span x-text="order"></span></h4>
                                            <p class="text-sm text-gray-500" x-show="references[order].indexer_name" x-text="references[order].indexer_name"></p>
                                        </div>
                                        <div class="flex shrink-0 gap-2">
                                            <x-filament::button type="button" size="xs" color="gray" icon="heroicon-o-pencil-square" @click="openEditModal(order)">
                                                Edit
                                            </x-filament::button>
                                            <x-filament::button type="button" size="xs" color="danger" icon="heroicon-o-trash" @click="removeReference(order)">
                                                Hapus
                                            </x-filament::button>
                                        </div>
                                    </div>

                                    <template x-if="reviewStatus(order) === 'rejected' && referenceNote(order)">
                                        <p class="mb-2 text-xs text-danger-600" x-text="'Diminta Revisi validator: ' + referenceNote(order)"></p>
                                    </template>
                                    <template x-if="reviewStatus(order) === 'pending'">
                                        <p class="mb-2 text-xs text-warning-600">Menunggu review validator</p>
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

                        <div class="space-y-4 border-t border-gray-200 pt-4">
                            <h6 class="text-sm font-semibold text-gray-700">Ringkasan Status Referensi</h6>

                            <template x-if="groupedOrders('pending').length > 0">
                                <div class="rounded-lg border border-warning-200 bg-warning-50 p-3">
                                    <p class="mb-2 text-sm font-semibold text-warning-800">Masih Direview</p>
                                    <ul class="space-y-1 text-sm text-warning-900">
                                        <template x-for="order in groupedOrders('pending')" :key="'pending-summary-' + order">
                                            <li>
                                                #<span x-text="order"></span>
                                                <span x-show="references[order].indexer_name"> — <span x-text="references[order].indexer_name"></span></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="groupedOrders('approved').length > 0">
                                <div class="rounded-lg border border-success-200 bg-success-50 p-3">
                                    <p class="mb-2 text-sm font-semibold text-success-800">Disetujui</p>
                                    <ul class="space-y-1 text-sm text-success-900">
                                        <template x-for="order in groupedOrders('approved')" :key="'approved-summary-' + order">
                                            <li>
                                                #<span x-text="order"></span>
                                                <span x-show="references[order].indexer_name"> — <span x-text="references[order].indexer_name"></span></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="groupedOrders('rejected').length > 0">
                                <div class="rounded-lg border border-danger-200 bg-danger-50 p-3">
                                    <p class="mb-2 text-sm font-semibold text-danger-800">Diminta Revisi</p>
                                    <ul class="space-y-2 text-sm text-danger-900">
                                        <template x-for="order in groupedOrders('rejected')" :key="'rejected-summary-' + order">
                                            <li>
                                                <span class="font-medium">#<span x-text="order"></span></span>
                                                <span x-show="references[order].indexer_name"> — <span x-text="references[order].indexer_name"></span></span>
                                                <span x-show="referenceNote(order)" class="block text-xs text-danger-700" x-text="referenceNote(order)"></span>
                                            </li>
                                        </template>
                                    </ul>
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
                        @if ($this->titleSlotOnly)
                            Buat Slot Judul
                        @elseif ($this->partialNuiOnly)
                            Simpan Revisi NUI
                        @elseif ($this->referencesOnly)
                            Simpan Referensi
                        @elseif ($this->stage === 2)
                            Simpan
                        @else
                            Simpan Draft
                        @endif
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
