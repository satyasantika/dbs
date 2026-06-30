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

    /** @return array<int, bool|null> */
    public function getRefStatusesForForm(): array
    {
        $statuses = [];

        for ($order = 1; $order <= 10; $order++) {
            $ref = $this->submission->references->firstWhere('ref_order', $order);
            $statuses[$order] = $ref?->ref_approved;
        }

        return $statuses;
    }

    /** @return array<int, string|null> */
    public function getRefNotesForForm(): array
    {
        $notes = [];

        for ($order = 1; $order <= 10; $order++) {
            $ref = $this->submission->references->firstWhere('ref_order', $order);
            $notes[$order] = $ref?->ref_note;
        }

        return $notes;
    }

    public function getMinReferences(): int
    {
        return $this->setting->min_references_approved ?? 10;
    }

    public function getMaxReferences(): int
    {
        return $this->setting->max_references ?? 10;
    }

    public function getNuiMaxWords(): int
    {
        return $this->setting->max_words_novelty
            ?? $this->setting->max_words_urgency
            ?? $this->setting->max_words_impact
            ?? 300;
    }

    /** @return array<string, array{min: int|null, max: int|null}> */
    public function getNuiWordLimits(): array
    {
        return [
            'novelty' => [
                'min' => $this->setting->min_words_novelty,
                'max' => $this->setting->max_words_novelty,
            ],
            'urgency' => [
                'min' => $this->setting->min_words_urgency,
                'max' => $this->setting->max_words_urgency,
            ],
            'impact' => [
                'min' => $this->setting->min_words_impact,
                'max' => $this->setting->max_words_impact,
            ],
        ];
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
