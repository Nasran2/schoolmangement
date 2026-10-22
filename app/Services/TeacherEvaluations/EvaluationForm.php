<?php

namespace App\Services\TeacherEvaluations;

class EvaluationForm
{
    public const HR_PERMISSIONS = ['hr.directory.view', 'teacher_evaluations.view', 'teacher_evaluations.manage', 'teacher_evaluations.export'];

    public const SECTIONS = ['Preschool', 'Primary', 'Secondary', 'Other'];

    public const EVIDENCE_FIELDS = [
        'area' => 'Performance area', 'examples' => 'Evidence / examples',
        'strengths' => 'Strengths observed', 'improvement' => 'Area to improve',
        'action' => 'Agreed action', 'review_date' => 'Review date', 'reviewer' => 'Reviewed by',
    ];

    public const DEVELOPMENT_FIELDS = [
        'goal' => 'Development goal', 'training' => 'Action / training required',
        'support' => 'Support required', 'measure' => 'Success measure',
        'target_date' => 'Target date', 'notes' => 'Review notes',
    ];

    public static function criteria(): array
    {
        return json_decode(file_get_contents(resource_path('teacher-evaluation-criteria.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function ratings(array $criterion): array
    {
        $ratings = $criterion['supervisor_ratings'] ?? [$criterion['rating'] ?? null];

        return array_map(fn ($rating) => $rating === null || $rating === '' ? null : (int) $rating, array_slice(array_pad($ratings, 3, null), 0, 3));
    }

    public static function supervisorScores(array $criteria): array
    {
        $scores = [];
        for ($supervisor = 0; $supervisor < 3; $supervisor++) {
            $complete = count($criteria) > 0 && collect($criteria)->every(fn ($criterion) => self::ratings($criterion)[$supervisor] !== null);
            $scores[] = $complete ? round(collect($criteria)->sum(fn ($criterion) => $criterion['weight'] * self::ratings($criterion)[$supervisor] / 5), 2) : null;
        }

        return $scores;
    }

    public static function score(array $criteria): ?float
    {
        $scores = array_values(array_filter(self::supervisorScores($criteria), fn ($score) => $score !== null));

        return count($scores) ? round(array_sum($scores) / count($scores), 2) : null;
    }

    public static function scoreSummary(array $criteria): array
    {
        $supervisors = [];
        $completeScores = self::supervisorScores($criteria);
        for ($supervisor = 0; $supervisor < 3; $supervisor++) {
            $rated = collect($criteria)->filter(fn ($criterion) => self::ratings($criterion)[$supervisor] !== null);
            $supervisors[] = [
                'rated' => $rated->count(),
                'total' => count($criteria),
                'points' => $rated->isEmpty() ? null : round($rated->sum(fn ($criterion) => $criterion['weight'] * self::ratings($criterion)[$supervisor] / 5), 2),
                'complete' => $completeScores[$supervisor] !== null,
            ];
        }
        $startedPoints = array_values(array_filter(array_column($supervisors, 'points'), fn ($points) => $points !== null));
        $score = self::score($criteria);

        return [
            'supervisors' => $supervisors,
            'completed' => count(array_filter($completeScores, fn ($points) => $points !== null)),
            'score' => $score,
            'entered_score' => count($startedPoints) ? round(array_sum($startedPoints) / count($startedPoints), 2) : null,
        ];
    }

    public static function reviewChecklist(string $type, array $criteria = []): array
    {
        return [
            'annual' => $type === 'annual',
            'fields' => array_merge(['section' => 'Section', 'evaluator' => 'Evaluator / authorised reviewer', 'review_date' => 'Review date'], $type === 'annual' ? ['period_start' => 'Appraisal period from', 'period_end' => 'Appraisal period to'] : ['event_date' => 'Event date', 'responsibility' => 'Assigned responsibility / duties']),
            'criteria' => collect($criteria)->map(fn ($criterion) => ['id' => $criterion['id'], 'area' => $criterion['area']])->all(),
            'rows' => [
                'evidence' => ['label' => 'Evidence entry', 'fields' => array_intersect_key(self::EVIDENCE_FIELDS, array_flip(['area', 'examples', 'reviewer']))],
                'development' => ['label' => 'Development entry', 'fields' => array_intersect_key(self::DEVELOPMENT_FIELDS, array_flip(['goal', 'training', 'measure', 'target_date']))],
            ],
            'pairs' => [
                ['name' => 'teacher_signed_name', 'date' => 'teacher_signed_date', 'label' => 'Teacher signature'],
                ['name' => 'evaluator_signed_name', 'date' => 'evaluator_signed_date', 'label' => 'Evaluator signature'],
            ],
        ];
    }

    public static function missingDetails(\App\Models\TeacherEvaluation $evaluation): array
    {
        $criteria = $evaluation->criteria ?? [];
        $checklist = self::reviewChecklist($evaluation->type, $criteria);
        $missing = [];
        foreach ($checklist['fields'] as $field => $label) {
            if (blank($evaluation->$field)) {
                $missing[] = $label;
            }
        }
        if ($checklist['annual']) {
            $started = false;
            foreach ([0, 1, 2] as $supervisor) {
                if (! collect($criteria)->contains(fn ($criterion) => self::ratings($criterion)[$supervisor] !== null)) {
                    continue;
                }
                $started = true;
                foreach ($criteria as $criterion) {
                    if (self::ratings($criterion)[$supervisor] === null) {
                        $missing[] = 'Supervisor '.($supervisor + 1).': '.$criterion['area'].' rating';
                    }
                }
            }
            if (! $started) {
                $missing[] = 'Annual performance ratings (no ratings entered)';
            }
        }
        foreach ($checklist['rows'] as $group => $settings) {
            $entries = collect($evaluation->$group ?? [])->filter(fn ($entry) => collect($entry)->contains(fn ($value) => filled($value)))->values();
            if ($group === 'evidence' && ! $checklist['annual'] && $entries->isEmpty()) {
                $missing[] = 'Evidence and examples (no entries added)';
            }
            foreach ($entries as $index => $entry) {
                foreach ($settings['fields'] as $field => $label) {
                    if (blank($entry[$field] ?? null)) {
                        $missing[] = $settings['label'].' '.($index + 1).': '.$label;
                    }
                }
            }
        }
        foreach ($checklist['pairs'] as $pair) {
            if (filled($evaluation->{$pair['name']}) && blank($evaluation->{$pair['date']})) {
                $missing[] = $pair['label'].' date';
            } elseif (filled($evaluation->{$pair['date']}) && blank($evaluation->{$pair['name']})) {
                $missing[] = $pair['label'].' name';
            }
        }
        if ($evaluation->follow_up_completed_date && blank($evaluation->follow_up_notes)) {
            $missing[] = 'Follow-up review notes';
        }

        return $missing;
    }

    /** Split long narrative cells into printable continuation rows without dropping text. */
    public static function tableRows(array $entries, array $fields, int $limit = 300): array
    {
        $rows = [];
        foreach ($entries as $index => $entry) {
            $columns = [];
            foreach ($fields as $key => $label) {
                $text = (string) ($entry[$key] ?? '');
                $parts = [];
                while (mb_strlen($text) > $limit) {
                    $space = mb_strrpos(mb_substr($text, 0, $limit + 1), ' ');
                    $cut = $space && $space > $limit / 2 ? $space : $limit;
                    $parts[] = rtrim(mb_substr($text, 0, $cut));
                    $text = ltrim(mb_substr($text, $cut));
                }
                $parts[] = $text;
                $columns[$key] = $parts;
            }
            $count = max(array_map('count', $columns));
            for ($part = 0; $part < $count; $part++) {
                $cells = [];
                foreach ($fields as $key => $label) {
                    $cells[$key] = $columns[$key][$part] ?? '';
                }
                $rows[] = ['entry' => $index + 1, 'continued' => $part > 0, 'cells' => $cells];
            }
        }

        return $rows;
    }

    public static function band(?float $score): ?string
    {
        return match (true) {
            $score === null => null,
            $score >= 90 => 'Outstanding',
            $score >= 80 => 'Exceeds expectations',
            $score >= 70 => 'Meets expectations well',
            $score >= 60 => 'Needs improvement',
            default => 'Unsatisfactory',
        };
    }

    public static function instructions(): array
    {
        return [
            'Download a blank annual or event form as PDF or Excel. Print it and leave the response spaces for the authorised evaluator to complete by hand.',
            'For an annual appraisal, select the teacher and appraisal year. Enter the section, subject or class, appraisal period, evaluator and review date from the signed form. One annual appraisal is allowed per teacher per year.',
            'Use one, two or three supervisors. Enter each available rating as a whole number from 1 to 5. A supervisor total is calculated only when all 13 criteria are rated. Weighted score = weight x rating / 5. The overall score averages completed supervisors only. Blank columns do not count as zero. Missing ratings may be left blank. Saving shows a warning listing incomplete details; choose Save anyway to continue.',
            'Rating scale: 5 Outstanding, 4 Exceeds expectations, 3 Meets expectations, 2 Needs improvement, 1 Unsatisfactory. Support ratings with evidence and apply standards consistently across sections and assigned duties.',
            'For an event review, enter the event or function name, event date, assigned responsibility, evaluator and review date. Add as many evidence and development rows as needed. Record specific examples, strengths, improvement areas, agreed actions, support, success measures and follow-up dates.',
            'Save a draft while entering a physical form. Reports may be saved as drafts or marked completed with missing review details after acknowledging the warning. The teacher and appraisal year or event name identify the report and remain required. Record signature names and dates only when the physical form was signed. Typed names are a transcription, not a digital signature.',
            'Discuss feedback with the teacher, record their comments and management comments, and agree realistic development goals. A signature confirms the discussion, not necessarily agreement.',
            'Use the teacher, type, section, status and search filters to find records. Filter annual appraisals by appraisal year and event reviews by event date. Review-date filters apply to either type. PDF and Excel exports use the same filters.',
            'Use the follow-up filter to find development targets or agreed-action review dates that have passed. Enter review notes and a follow-up completion date when the action has been reviewed.',
            'Keep physical and exported forms confidential. HR admin can read teacher and student details and manage evaluations. Financial records, salaries, payments, other modules and user administration require separate access.',
            'Annual scores inform management review and do not automatically change salary or promotion. Decisions remain subject to evidence, school policy, management approval and budget.',
        ];
    }
}
