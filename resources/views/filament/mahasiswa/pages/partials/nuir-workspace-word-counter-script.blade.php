<script>
    window.nuirFieldWordCounter = window.nuirFieldWordCounter ?? function (config) {
        return {
            field: config.field ?? 'title',
            label: config.label ?? '',
            limit: config.limit ?? {},
            lastSaved: config.lastSaved ?? '',
            initialValue: config.initialValue ?? '',
            uiAction: config.uiAction ?? 'compose',
            canPersist: config.canPersist ?? true,
            showEdit: config.showEdit ?? false,
            saveLabel: config.saveLabel ?? 'Simpan',
            editLabel: config.editLabel ?? 'Edit',
            versionLabel: config.versionLabel ?? null,
            statusLabel: config.statusLabel ?? null,
            statusColor: config.statusColor ?? 'gray',
            showWorkflowBadge: config.showWorkflowBadge ?? false,
            accordionOpen: config.accordionOpen ?? false,
            keepExpandedOnSave: config.keepExpandedOnSave ?? false,
            isEditing: (config.uiAction ?? 'compose') === 'compose',
            inputVersion: 0,

            init() {
                this.$nextTick(() => {
                    this.ensureInputValue();
                    this.onFieldInput();
                    this.autoResize({ target: this.$refs.input });
                    this.syncTextareaLock();
                });

                if (typeof Livewire !== 'undefined') {
                    Livewire.hook('morph.updated', ({ el }) => {
                        if (this.$root.contains(el)) {
                            this.ensureInputValue();
                            this.onFieldInput();
                            this.autoResize({ target: this.$refs.input });
                            this.isEditing = this.uiAction === 'compose';
                            this.syncTextareaLock();
                        }
                    });

                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => {
                            if (! this.$root?.isConnected) {
                                return;
                            }

                            this.ensureInputValue();
                            this.onFieldInput();
                            this.autoResize({ target: this.$refs.input });
                            this.syncTextareaLock();
                        });
                    });
                }
            },

            ensureInputValue() {
                const input = this.$refs.input;

                if (! input || this.initialValue === '') {
                    return;
                }

                const corrupted = input.value.includes('() =>') || input.value.includes('function');

                if (input.value === '' || corrupted) {
                    input.value = this.initialValue;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },

            syncTextareaLock() {
                const input = this.$refs.input;

                if (! input) {
                    return;
                }

                input.readOnly = this.isTextareaReadonly();
            },

            onFieldInput() {
                this.inputVersion++;
            },

            autoResize(event) {
                const element = event?.target ?? this.$refs.input;

                if (! element) {
                    return;
                }

                element.style.height = 'auto';
                element.style.height = `${element.scrollHeight}px`;
            },

            fieldValue() {
                void this.inputVersion;

                return this.$refs.input?.value ?? '';
            },

            wordCount(text) {
                const trimmed = String(text ?? '').trim();

                return trimmed ? trimmed.split(/\s+/).length : 0;
            },

            charCount(text) {
                return String(text ?? '').length;
            },

            isFieldValid() {
                void this.inputVersion;

                return this.validationMessage() === null;
            },

            isTextareaReadonly() {
                if (this.uiAction === 'none') {
                    return true;
                }

                if (this.uiAction === 'compose') {
                    return false;
                }

                return ! this.isEditing;
            },

            showSaveButton() {
                return this.isEditing && this.uiAction !== 'none';
            },

            showEditButton() {
                if (this.isEditing || this.uiAction === 'none' || this.uiAction === 'compose') {
                    return false;
                }

                if (this.uiAction === 'revision') {
                    return true;
                }

                return this.uiAction === 'edit' && this.showEdit;
            },

            enterEditMode() {
                this.isEditing = true;
                this.$nextTick(() => {
                    this.syncTextareaLock();
                    this.autoResize({ target: this.$refs.input });
                    this.$refs.input?.focus();
                });
            },

validationMessage(value = null) {
                value = value ?? this.fieldValue();
                const words = this.wordCount(value);
                const min = this.limit.min != null ? Number(this.limit.min) : null;
                const max = this.limit.max != null ? Number(this.limit.max) : null;
                const maxChars = this.limit.maxChars != null ? Number(this.limit.maxChars) : null;

                if (this.field === 'title' && String(value).trim() === '') {
                    return 'Judul wajib diisi.';
                }

                if (min !== null && words < min) {
                    return `Minimal ${min} kata untuk ${this.label}.`;
                }

                if (max !== null && words > max) {
                    return `Maksimal ${max} kata untuk ${this.label}.`;
                }

                if (this.field !== 'title' && max === null && maxChars !== null && this.charCount(value) > maxChars) {
                    return `Maksimal ${maxChars} karakter untuk ${this.label}.`;
                }

                return null;
            },

            wordCountLimitText() {
                void this.inputVersion;

                if (this.limit.maxChars != null && this.limit.max == null) {
                    return `isi ${this.label} maks. ${this.limit.maxChars} karakter`;
                }

                const min = this.limit.min != null ? Number(this.limit.min) : null;
                const max = this.limit.max != null ? Number(this.limit.max) : null;

                if (min !== null && max !== null) {
                    return `isi ${this.label} antara ${min}-${max} kata`;
                }

                if (min !== null) {
                    return `isi ${this.label} min. ${min} kata`;
                }

                if (max !== null) {
                    return `isi ${this.label} maks. ${max} kata`;
                }

                return `isi ${this.label} wajib diisi`;
            },

            wordCountInputText() {
                void this.inputVersion;

                const value = this.fieldValue();

                if (this.limit.maxChars != null && this.limit.max == null) {
                    return `${this.charCount(value)} karakter diinput`;
                }

                return `${this.wordCount(value)} kata diinput`;
            },
        };
    };
</script>
