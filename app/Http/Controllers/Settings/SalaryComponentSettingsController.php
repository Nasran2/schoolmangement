<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryComponentSettingsController extends Controller
{
    private const DEFAULT_COMPONENT_TYPES = [
        'Basic Salary',
        'House Allowance',
        'Transport Allowance',
        'Medical Allowance',
        'Incentive',
        'Bonus',
        'Special Allowance',
        'Other',
    ];

    private const DEFAULT_DEDUCTION_TYPES = [
        'Leave Deduction',
        'Loan Recovery',
        'Advance Adjustment',
        'Late Arrival',
        'Penalty',
    ];

    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function edit(): View
    {
        $componentTypes = $this->loadJsonArray('salary_component_types', self::DEFAULT_COMPONENT_TYPES);
        $deductionTypes = $this->loadJsonArray('salary_deduction_types', self::DEFAULT_DEDUCTION_TYPES);
        $epfPercent = (float) ($this->settings->get('salary_epf_percent', '0') ?: 0);
        $etfPercent = (float) ($this->settings->get('salary_etf_percent', '0') ?: 0);

        return view('settings.salary-components', [
            'componentTypes' => $componentTypes,
            'deductionTypes' => $deductionTypes,
            'epfPercent' => $epfPercent,
            'etfPercent' => $etfPercent,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'component_types' => ['required', 'array', 'min:1'],
            'component_types.*' => ['required', 'string', 'max:120'],
            'deduction_types' => ['required', 'array', 'min:1'],
            'deduction_types.*' => ['required', 'string', 'max:120'],
            'epf_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'etf_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $componentTypes = $this->sanitizeList($validated['component_types']);
        $deductionTypes = $this->sanitizeList($validated['deduction_types']);

        $this->settings->set('salary_component_types', json_encode($componentTypes), 'salary');
        $this->settings->set('salary_deduction_types', json_encode($deductionTypes), 'salary');
        $this->settings->set('salary_epf_percent', (string) ($validated['epf_percent'] ?? '0'), 'salary');
        $this->settings->set('salary_etf_percent', (string) ($validated['etf_percent'] ?? '0'), 'salary');

        return back()->with('status', 'Salary settings updated.');
    }

    /**
     * @return array<int,string>
     */
    private function loadJsonArray(string $key, array $fallback): array
    {
        $raw = $this->settings->get($key, '');
        $decoded = json_decode((string) $raw, true);

        if (is_array($decoded) && count($decoded) > 0) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return $fallback;
    }

    /**
     * @param array<int,string> $items
     * @return array<int,string>
     */
    private function sanitizeList(array $items): array
    {
        $trimmed = array_map(fn ($v) => trim((string) $v), $items);
        $filtered = array_values(array_filter($trimmed, fn ($v) => $v !== ''));

        return count($filtered) > 0 ? $filtered : ['Unnamed'];
    }
}
