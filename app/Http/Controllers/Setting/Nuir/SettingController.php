<?php

namespace App\Http\Controllers\Setting\Nuir;

use App\DataTables\NuirSettingsDataTable;
use App\Http\Controllers\Controller;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(NuirSettingsDataTable $dataTable)
    {
        return redirect(\App\Filament\Dbs\Resources\NuirSettingResource::getUrl('index', panel: 'dbs'));
    }

    public function create()
    {
        return redirect(\App\Filament\Dbs\Resources\NuirSettingResource::getUrl('create', panel: 'dbs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        NuirSetting::create($data);

        return to_route('nuir-settings.index')->with('success', 'data telah ditambahkan');
    }

    public function edit(NuirSetting $nuirSetting)
    {
        return redirect(\App\Filament\Dbs\Resources\NuirSettingResource::getUrl('edit', [
            'record' => $nuirSetting,
        ], panel: 'dbs'));
    }

    public function update(Request $request, NuirSetting $nuirSetting)
    {
        $data = $this->validated($request, $nuirSetting);
        $nuirSetting->fill($data)->save();

        return to_route('nuir-settings.index')->with('success', 'data telah diperbarui');
    }

    public function destroy(NuirSetting $nuirSetting)
    {
        if (NuirSubmission::where('year_generation', $nuirSetting->year_generation)->exists()) {
            return back()->with('warning', 'Setting tidak dapat dihapus karena masih ada submission terkait.');
        }

        $nuirSetting->delete();

        return to_route('nuir-settings.index')->with('warning', 'data telah dihapus');
    }

    public function toggle(NuirSetting $nuirSetting)
    {
        $nuirSetting->update(['active' => ! $nuirSetting->active]);

        return back()->with('success', 'status aktif diperbarui');
    }

    private function validated(Request $request, ?NuirSetting $nuirSetting = null): array
    {
        $uniqueRule = 'unique:nuir_settings,year_generation';
        if ($nuirSetting) {
            $uniqueRule .= ','.$nuirSetting->id;
        }

        $data = $request->validate([
            'year_generation' => ['required', 'string', $uniqueRule],
            'stage' => ['required', 'integer', 'in:1,2,3'],
            'deadline' => ['nullable', 'date'],
            'min_references_approved' => ['required', 'integer', 'min:1', 'max:20'],
            'max_references' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_references_approved'],
            'min_words_novelty' => ['nullable', 'integer', 'min:1'],
            'max_words_novelty' => ['nullable', 'integer', 'min:1'],
            'min_words_urgency' => ['nullable', 'integer', 'min:1'],
            'max_words_urgency' => ['nullable', 'integer', 'min:1'],
            'min_words_impact' => ['nullable', 'integer', 'min:1'],
            'max_words_impact' => ['nullable', 'integer', 'min:1'],
            'max_chars_novelty' => ['nullable', 'integer', 'min:100'],
            'max_chars_urgency' => ['nullable', 'integer', 'min:100'],
            'max_chars_impact' => ['nullable', 'integer', 'min:100'],
        ]);

        $data['active'] = $request->boolean('active');

        return $data;
    }
}
