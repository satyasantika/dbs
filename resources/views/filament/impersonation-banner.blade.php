@if (is_impersonating())
    <style>
        :root {
            --impersonate-banner-height: 6px;
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
            background: linear-gradient(90deg, #ef4444, #f97316, #eab308, #22c55e, #06b6d4, #3b82f6, #a855f7, #ef4444);
            background-size: 200% 100%;
            animation: impersonate-banner-cycle 4s linear infinite;
        }

        @keyframes impersonate-banner-cycle {
            from {
                background-position: 0% 0;
            }
            to {
                background-position: -200% 0;
            }
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

    <div id="impersonate-banner"></div>
@endif
