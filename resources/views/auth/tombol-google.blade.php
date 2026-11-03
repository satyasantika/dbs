@if (config('services.google.aktif'))
    <div class="mt-3 text-center">
        @if (session('galat_google'))
            <p role="alert" class="text-danger small mb-2">{{ session('galat_google') }}</p>
        @endif
        <a href="{{ route('auth.google.redirect', ['asal' => url()->current()]) }}" class="btn btn-outline-secondary w-100">
            Masuk dengan Google
        </a>
    </div>
@endif
