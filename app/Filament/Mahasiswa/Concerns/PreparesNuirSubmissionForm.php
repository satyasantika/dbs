<?php

namespace App\Filament\Mahasiswa\Concerns;

trait PreparesNuirSubmissionForm
{
    public function getReferencesForForm(): array
    {
        $references = [];

        for ($order = 1; $order <= 10; $order++) {
            $ref = $this->submission->references->firstWhere('ref_order', $order);

            $references[$order] = [
                'link_ojs' => old("references.{$order}.link_ojs", $ref?->link_ojs ?? ''),
                'indexer_name' => old("references.{$order}.indexer_name", $ref?->indexer_name ?? ''),
                'link_index' => old("references.{$order}.link_index", $ref?->link_index ?? ''),
                'link_drive' => old("references.{$order}.link_drive", $ref?->link_drive ?? ''),
                'quote' => old("references.{$order}.quote", $ref?->quote ?? ''),
                'relevance' => old("references.{$order}.relevance", $ref?->relevance ?? ''),
            ];
        }

        return $references;
    }

    public function getNuiMaxWords(): int
    {
        return 300;
    }

    public function getNuiCharLimits(): array
    {
        return [
            'novelty' => $this->setting->max_chars_novelty,
            'urgency' => $this->setting->max_chars_urgency,
            'impact' => $this->setting->max_chars_impact,
        ];
    }

    public function getIndexerOptions(): array
    {
        return ['WoS', 'Scopus', 'Thomson', 'Elsevier', 'Springer', 'Wiley', 'Taylor&Francis', 'DOAJ', 'Sinta 2'];
    }
}
