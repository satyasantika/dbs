<?php

namespace App\Support;

use App\Filament\NuirValidator\Resources\NuirReferenceResource;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource;

class NuirValidatorListReturn
{
    public const LIST_SUBMISSIONS = 'submissions';

    public const LIST_REFERENCES = 'references';

    public static function key(string $list, ?string $view = null): string
    {
        return $list.':'.($view ?? '');
    }

    public static function submissionKey(?string $view = null): string
    {
        return self::key(
            self::LIST_SUBMISSIONS,
            $view ?? NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
        );
    }

    public static function referenceKey(?string $view = null): string
    {
        return self::key(self::LIST_REFERENCES, $view);
    }

    public static function url(?string $returnKey, string $panel = 'nuir-validator'): string
    {
        if (blank($returnKey) || ! str_contains($returnKey, ':')) {
            return NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED, $panel);
        }

        [$list, $view] = explode(':', $returnKey, 2);

        return match ($list) {
            self::LIST_REFERENCES => NuirReferenceResource::listUrl(filled($view) ? $view : null, $panel),
            default => NuirSubmissionResource::listUrl(
                filled($view) ? $view : NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED,
                $panel,
            ),
        };
    }

    public static function label(?string $returnKey): string
    {
        if (blank($returnKey) || ! str_contains($returnKey, ':')) {
            return 'Kembali ke Daftar Submission';
        }

        [$list, $view] = explode(':', $returnKey, 2);

        $title = match ($list) {
            self::LIST_REFERENCES => NuirReferenceResource::dashboardViewLabel(filled($view) ? $view : null),
            default => NuirSubmissionResource::dashboardViewLabel(filled($view) ? $view : null),
        };

        return filled($title)
            ? 'Kembali ke '.$title
            : match ($list) {
                self::LIST_REFERENCES => 'Kembali ke Daftar Referensi',
                default => 'Kembali ke Daftar Submission',
            };
    }
}
