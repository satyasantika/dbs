<style>
    .fi-nuir-submission-form .nui-field {
        min-height: 6rem;
        resize: none;
        overflow-y: hidden;
        line-height: 1.5;
    }

    .fi-nuir-submission-form .reference-card {
        border: 1px solid rgb(229 231 235);
        border-radius: 0.75rem;
        background: white;
        padding: 1rem;
    }

    .dark .fi-nuir-submission-form .reference-card {
        border-color: rgb(255 255 255 / 0.1);
        background: rgb(17 24 39);
    }

    .fi-nuir-submission-form .reference-card--rejected {
        border-color: rgb(252 165 165);
        background: rgb(254 242 242);
    }

    .dark .fi-nuir-submission-form .reference-card--rejected {
        border-color: rgb(239 68 68 / 0.4);
        background: rgb(69 10 10 / 0.25);
    }

    .fi-nuir-submission-form .reference-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 40;
        background: rgb(0 0 0 / 0.5);
    }

    .fi-nuir-submission-form .reference-modal-panel {
        position: fixed;
        inset: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .fi-nuir-submission-form .reference-modal-content {
        width: 100%;
        max-width: 48rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
        border-radius: 0.75rem;
        background: white;
        padding: 1.5rem;
        box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
    }

    .dark .fi-nuir-submission-form .reference-modal-content {
        background: rgb(17 24 39);
    }

    .fi-nuir-submission-form .reference-modal-textarea {
        min-height: 10rem;
        resize: vertical;
    }
</style>
