<?php

namespace App\Support;

class ClassFeeRegisterFilters
{
    public static function rules(): array
    {
        return [
            'view' => ['nullable', 'in:overview,students'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'type' => ['nullable', 'in:exam,computer,facility'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'status' => ['nullable', 'in:all,paid,unpaid,partial,pending'],
            'q' => ['nullable', 'string', 'max:100'],
            'include_inactive' => ['nullable', 'boolean'], 'include_former' => ['nullable', 'boolean'],
            'include_computer' => ['nullable', 'boolean'],
            'all_years' => ['nullable', 'boolean'], 'include_facility' => ['nullable', 'boolean'],
            'exam_selection' => ['nullable', 'boolean'], 'exam_dates' => ['nullable', 'array', 'max:2000'],
            'exam_dates.*' => ['required', 'date_format:Y-m-d', 'distinct'],
            'selection_scope' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function returnRules(): array
    {
        $rules = ['return_filters' => ['nullable', 'array']];
        foreach (self::rules() as $name => $rule) {
            $rules['return_filters.'.$name] = $rule;
        }

        return $rules;
    }

    public static function query(array $filters): array
    {
        return array_filter(array_intersect_key($filters, self::rules()), fn ($value) => $value !== null);
    }
}
