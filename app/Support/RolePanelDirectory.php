<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;

class RolePanelDirectory
{
    /**
     * @var array<string, array{panel: string, label: string}>
     */
    private const MAP = [
        'dosen' => ['panel' => 'dosen', 'label' => 'Portal Dosen'],
        'mahasiswa' => ['panel' => 'mahasiswa', 'label' => 'Portal Mahasiswa'],
        'manajer nuir' => ['panel' => 'nuir-manajer', 'label' => 'Portal Manajer NUIR'],
        'validator nuir' => ['panel' => 'nuir-validator', 'label' => 'Portal Validator NUIR'],
        'dbs' => ['panel' => 'dbs', 'label' => 'Portal DBS'],
    ];

    /**
     * @return list<array{panel: string, label: string, url: string}>
     */
    public static function optionsForUser(User $user): array
    {
        $options = [];

        foreach (self::MAP as $role => $entry) {
            if (! $user->hasRole($role)) {
                continue;
            }

            // Panel::getUrl() resolves to the first navigation item, which can
            // be a resource list rather than the dashboard — use the panel's
            // own base path instead, matching Filament's own root fallback.
            $options[] = [
                'panel' => $entry['panel'],
                'label' => $entry['label'],
                'url' => url(Filament::getPanel($entry['panel'])->getPath()),
            ];
        }

        return $options;
    }
}
