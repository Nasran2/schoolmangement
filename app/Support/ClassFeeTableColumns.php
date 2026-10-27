<?php

namespace App\Support;

use Illuminate\Support\Collection;

/** Presentation groups only; each fee keeps its own balance and payment actions. */
class ClassFeeTableColumns
{
    public static function forCycles(Collection $cycles): Collection
    {
        $columns = $cycles->reject(fn ($cycle) => $cycle['schedule']->fee_type === 'facility')
            ->sortBy(fn ($cycle) => [$cycle['due']->timestamp, $cycle['schedule']->fee_type === 'exam' ? 0 : 1])
            ->groupBy(fn ($cycle) => implode(':', [$cycle['due']->toDateString(), ($cycle['end'] ?? null)?->toDateString() ?? '', $cycle['term'] ?? '']), true)
            ->map(fn ($group) => [
                'kind' => 'term', 'label' => isset($group->first()['term']) ? 'Term '.$group->first()['term'] : 'Term fee',
                'due' => $group->first()['due'], 'end' => $group->first()['end'] ?? null, 'cycles' => $group,
            ])->values();

        $facility = $cycles->filter(fn ($cycle) => $cycle['schedule']->fee_type === 'facility')->sortBy('due');
        if ($facility->isNotEmpty()) {
            $columns->push(['kind' => 'facility', 'label' => 'Facility Fee', 'due' => null, 'end' => null, 'cycles' => $facility]);
        }

        return $columns;
    }
}
