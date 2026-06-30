<script>
window.nuirSubmissionForm = function (config) {
    const emptyReference = () => ({
        link_ojs: '',
        indexer_name: '',
        link_index: '',
        link_drive: '',
        quote: '',
        relevance: '',
    });

    return {
        references: config.references ?? {},
        rejectedRefs: config.rejectedRefs ?? {},
        refStatuses: config.refStatuses ?? {},
        refNotes: config.refNotes ?? {},
        indexers: config.indexers ?? [],
        maxRefOrder: config.maxReferences ?? 10,
        minReferences: config.minReferences ?? 10,
        maxReferences: config.maxReferences ?? 10,
        maxWords: config.maxWords ?? 300,
        wordLimits: config.wordLimits ?? {},
        charLimits: config.charLimits ?? {},
        modalOpen: false,
        editingOrder: null,
        modalForm: emptyReference(),

        isReferenceFilled(ref) {
            if (! ref) {
                return false;
            }

            return Object.values(ref).some((value) => String(value ?? '').trim() !== '');
        },

        reviewStatus(order) {
            const dbStatus = this.refStatuses[order];

            if (dbStatus === true) {
                return 'approved';
            }

            if (dbStatus === false) {
                return 'rejected';
            }

            if (this.isReferenceFilled(this.references[order])) {
                return 'pending';
            }

            return 'empty';
        },

        filledOrders() {
            return Object.keys(this.references)
                .map(Number)
                .filter((order) => this.isReferenceFilled(this.references[order]))
                .sort((a, b) => a - b);
        },

        editableOrders() {
            return this.filledOrders().filter((order) => this.reviewStatus(order) !== 'approved');
        },

        groupedOrders(status) {
            return this.filledOrders().filter((order) => this.reviewStatus(order) === status);
        },

        countByStatus(status) {
            return this.groupedOrders(status).length;
        },

        canAddReference() {
            if (this.nextOrder() === null) {
                return false;
            }

            const approved = this.countByStatus('approved');
            const pending = this.countByStatus('pending');

            if (approved >= this.minReferences) {
                return false;
            }

            if (approved + pending >= this.minReferences) {
                return false;
            }

            return true;
        },

        referenceNote(order) {
            return this.refNotes[order] ?? this.rejectedRefs[order] ?? '';
        },

        nextOrder() {
            for (let order = 1; order <= this.maxRefOrder; order++) {
                if (! this.references[order]) {
                    this.references[order] = emptyReference();
                }

                if (! this.isReferenceFilled(this.references[order])) {
                    return order;
                }
            }

            return null;
        },

        openAddModal() {
            if (! this.canAddReference()) {
                return;
            }

            const order = this.nextOrder();

            if (! order) {
                return;
            }

            this.editingOrder = order;
            this.modalForm = emptyReference();
            this.modalOpen = true;
        },

        openEditModal(order) {
            if (this.reviewStatus(order) === 'approved') {
                return;
            }

            this.editingOrder = order;
            this.modalForm = { ...this.references[order] };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.editingOrder = null;
            this.modalForm = emptyReference();
        },

        saveModal() {
            if (! this.editingOrder) {
                return;
            }

            this.references[this.editingOrder] = { ...this.modalForm };
            this.closeModal();
        },

        removeReference(order) {
            if (this.reviewStatus(order) === 'approved') {
                return;
            }

            if (! confirm('Hapus referensi #' + order + '?')) {
                return;
            }

            this.references[order] = emptyReference();
        },

        wordCount(text) {
            const trimmed = String(text ?? '').trim();

            return trimmed ? trimmed.split(/\s+/).length : 0;
        },

        charCount(text) {
            return String(text ?? '').length;
        },

        limitLabel(field) {
            const charLimit = this.charLimits[field];
            const wordLimit = this.wordLimits[field] ?? {};
            const words = this.wordCount(this.$refs[field]?.value ?? '');

            if (charLimit) {
                return `${this.charCount(this.$refs[field]?.value ?? '')} / ${charLimit} karakter`;
            }

            if (wordLimit.min || wordLimit.max) {
                const maxLabel = wordLimit.max ?? '—';

                return `${words} kata (min ${wordLimit.min ?? '—'}, max ${maxLabel})`;
            }

            return `${words} / ${this.maxWords} kata`;
        },

        autoResize(event) {
            const element = event.target;
            const style = window.getComputedStyle(element);
            const lineHeight = parseFloat(style.lineHeight) || 24;
            const maxHeight = lineHeight * Math.ceil(this.maxWords / 8);

            element.style.height = 'auto';
            element.style.height = Math.min(element.scrollHeight, maxHeight) + 'px';
            element.style.overflowY = element.scrollHeight > maxHeight ? 'auto' : 'hidden';
        },

        initNuiFields() {
            this.$nextTick(() => {
                this.$root.querySelectorAll('[data-nui-autoresize]').forEach((element) => {
                    this.autoResize({ target: element });
                });
            });
        },
    };
};
</script>
