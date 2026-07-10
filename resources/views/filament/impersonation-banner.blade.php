@if (is_impersonating())
    <style>
        :root {
            --impersonate-banner-height: 42px;
            --impersonate-bg-color: #1f2937;
            --impersonate-text-color: #f3f4f6;
            --impersonate-border-color: #374151;
            --impersonate-button-bg-color: 243, 244, 246;
            --impersonate-button-text-color: #1f2937;
        }

        html {
            margin-top: var(--impersonate-banner-height);
        }

        #impersonate-banner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--impersonate-banner-height);
            z-index: 45;
            display: flex;
            column-gap: 20px;
            justify-content: center;
            align-items: center;
            background-color: var(--impersonate-bg-color);
            color: var(--impersonate-text-color);
            border-bottom: 1px solid var(--impersonate-border-color);
            font-size: 0.875rem;
        }

        #impersonate-banner a {
            display: block;
            padding: 4px 16px;
            border-radius: 6px;
            background-color: rgba(var(--impersonate-button-bg-color), 0.7);
            color: var(--impersonate-button-text-color);
            font-weight: 600;
            text-decoration: none;
        }

        #impersonate-banner a:hover {
            background-color: rgb(var(--impersonate-button-bg-color));
        }

        div.fi-layout > aside.fi-sidebar {
            top: var(--impersonate-banner-height);
            height: calc(100vh - var(--impersonate-banner-height));
        }

        .fi-topbar {
            top: var(--impersonate-banner-height);
        }

        .fi-modal.fi-modal-slide-over > .fi-modal-window-ctn > .fi-modal-window {
            padding-top: var(--impersonate-banner-height);
        }

        @media print {
            aside,
            body {
                margin-top: 0;
            }

            #impersonate-banner {
                display: none;
            }
        }
    </style>

    <div id="impersonate-banner">
        <div>
            Anda sedang berpura-pura menjadi <strong>{{ auth()->user()->name }}</strong>
        </div>

        <a href="{{ route('impersonate.leave') }}">Tinggalkan</a>
    </div>
@endif
