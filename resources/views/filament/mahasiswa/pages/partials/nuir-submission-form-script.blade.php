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
        indexers: config.indexers ?? [],
        maxRefOrder: 10,
        maxWords: config.maxWords ?? 300,
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

        filledOrders() {
            return Object.keys(this.references)
                .map(Number)
                .filter((order) => this.isReferenceFilled(this.references[order]))
                .sort((a, b) => a - b);
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
            const order = this.nextOrder();

            if (! order) {
                return;
            }

            this.editingOrder = order;
            this.modalForm = emptyReference();
            this.modalOpen = true;
        },

        openEditModal(order) {
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

            if (charLimit) {
                return `${this.charCount(this.$refs[field]?.value ?? '')} / ${charLimit} karakter`;
            }

            const words = this.wordCount(this.$refs[field]?.value ?? '');

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
