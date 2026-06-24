<x-filament-panels::page>
    <div class="space-y-6">
        <h3 class="text-lg font-semibold">Selamat datang, {{ auth()->user()->name }}</h3>

        @can('join exam')
            <x-filament::section heading="Halaman Ujian">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Untuk melihat hasil ujian, silakan klik tombol berikut:
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ route('exam.student.index') }}"
                    size="sm"
                    class="mt-3"
                >
                    Hasil Ujian
                </x-filament::button>
            </x-filament::section>
        @endcan

        @can('join stage 2')
            @if (App\Models\SelectionStage::where('user_id', auth()->user()->id)->doesntExist())
                <x-filament::section heading="Pemilihan Pembimbing Tahap 2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Halaman ini akan menuntun Anda pada proses pemilihan pembimbing tahap 2 di Jurusan Pendidikan Matematika.
                        Silakan klik tombol berikut untuk bergabung dalam proses pemilihan pembimbing tahap 2.
                    </p>
                    <form id="stage-form" action="{{ route('stages.store') }}" method="POST" class="mt-4">
                        @csrf
                        <x-filament::button
                            type="submit"
                            size="sm"
                            onclick="return confirm('Yakin akan bergabung?');"
                        >
                            {{ __('Join Tahap 2') }}
                        </x-filament::button>
                    </form>
                </x-filament::section>
            @else
                <x-filament::section heading="Pemilihan Pembimbing Tahap 2">
                    <p class="text-sm font-medium">Halo, {{ auth()->user()->name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Selamat bergabung di program pemilihan pembimbing tahap 2.
                        Halaman ini merupakan bagian dari proses pemilihan pembimbing tahap 2 di Jurusan Pendidikan Matematika.
                        Anda terdeteksi sudah bergabung dalam tahapan pemilihan ini, silakan ikuti teknis berikut:
                    </p>
                    <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">
                        <li>Anda dapat mengusulkan hingga (5) lima usulan pasangan pembimbing</li>
                        <li>Jika Bapak/Ibu menerima usulan tersebut dan pasangan calon pembimbing juga menerimanya, maka pasangan calon pembimbing akan langsung ditetapkan</li>
                        <li>Jika Bapak/Ibu menerima usulan tersebut, sementara pasangan calon pembimbing yang diusulkan menolak, maka usulan pasangan ini dibatalkan oleh sistem dan mahasiswa dapat mengganti usulan pasangan yang baru.</li>
                        <li>Jika Bapak/Ibu menolak usulan tersebut, maka secara otomatis usulan terhadap pasangan calon pembimbing lain juga ditolak.</li>
                        <li>Jika satu usulan telah diterima oleh dua calon pembimbing yang berpasangan, maka usulan pasangan lain otomatis ditolak oleh sistem.</li>
                    </ol>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Jika sudah siap memilih, silakan klik tombol pembimbing untuk mulai memilih.
                    </p>
                    @includeWhen(auth()->user()->can('read selection stages'), 'selection.stage-submission')
                </x-filament::section>
            @endif
        @endcan

        @php
            $guideExaminer = App\Models\GuideExaminer::where('user_id', auth()->id())->first();
            $nuirSetting = $guideExaminer
                ? App\Models\NuirSetting::where('year_generation', $guideExaminer->year_generation)
                    ->where('active', true)->first()
                : null;
        @endphp

        @if ($nuirSetting && in_array($nuirSetting->stage, [1, 2]))
            <x-filament::section heading="Pengajuan NUIR">
                <div class="flex flex-wrap gap-2">
                    @can('access nuir/submission')
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Mahasiswa\Pages\NuirSubmissionOverview::getUrl(panel: 'mahasiswa') }}"
                            size="sm"
                        >
                            NUIR Saya
                        </x-filament::button>
                    @endcan
                    @can('read nuir proposal')
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Mahasiswa\Pages\NuirProposalOverview::getUrl(panel: 'mahasiswa') }}"
                            size="sm"
                            color="gray"
                        >
                            Proposal Pembimbing
                        </x-filament::button>
                    @endcan
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
