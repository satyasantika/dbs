<table class="table table-hover">
    <thead>
        <tr>
            {{-- <th scope="col">#</th> --}}
            <th scope="col">Tahap</th>
            <th scope="col">Status</th>
            <th scope="col">Tanggal Daftar</th>
            <th scope="col">Usulan</th>
        </tr>
    </thead>
    <tbody>
        @foreach (App\Models\SelectionStage::where('user_id',auth()->user()->id)->latest()->get() as $key => $stage)
        <tr>
            {{-- <th scope="row">{{ $key+1 }}</th> --}}
            <td>{{ $stage->stage_order }}</td>
            <td>{{ ($stage->final ? 'acc' : 'onProcess') }}</td>
            <td>
                <span class="badge bg-secondary">diusulkan</span>
                {{ $stage->created_at->format('d-m-Y H:i:s') }}
                @if ($stage->final)
                <br>
                <span class="badge bg-success">disetujui</span>
                {{ $stage->updated_at->format('d-m-Y H:i:s') }}
                @endif
            </td>
            <td>
                @can('create selection elements')
                <a href="{{ route('home') }}" class="btn btn-outline-primary btn-sm">NUIR</a>
                @endcan
                @can('create selection guides')
                <a href="{{ route('guides.index',$stage->id) }}" class="btn btn-outline-primary btn-sm">pembimbing</a>
                @endcan
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
