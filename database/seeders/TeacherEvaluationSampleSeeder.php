<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Services\AuditLogger;
use App\Services\TeacherEvaluations\EvaluationForm;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherEvaluationSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $today = today();
            $teachers = [];
            foreach (['Primary' => 'Grade 2', 'Secondary' => 'Science / Grade 8', 'Preschool' => 'Preschool'] as $section => $subject) {
                $teachers[$section] = Teacher::firstOrCreate(
                    ['email' => 'sample.evaluation.'.strtolower($section).'@example.invalid'],
                    [
                        'name' => '[SAMPLE] '.$section.' Teacher',
                        'joining_date' => $today->copy()->subYears(3)->startOfYear(),
                        'assigned_classes' => $subject,
                        'salary_amount' => 0,
                        'epf_enabled' => false,
                        'etf_enabled' => false,
                        'active' => false,
                    ]
                );
            }

            $strong = array_fill(1, 13, 4);
            $strong[3] = 5;
            $strong[7] = 5;
            $developing = array_fill(1, 13, 3);
            foreach ([1, 2, 10] as $id) {
                $developing[$id] = 5;
            }
            $this->annual($teachers['Primary'], 'Primary', $today, $strong);
            $this->annual($teachers['Primary'], 'Primary', $today->copy()->subYear()->endOfYear(), array_fill(1, 13, 4));
            $this->annual($teachers['Secondary'], 'Secondary', $today, $developing);
            $this->annual($teachers['Preschool'], 'Preschool', $today, [1 => 4, 2 => 4, 3 => 5, 4 => 3, 5 => 4], 'draft');

            $this->event($teachers['Primary'], 'Primary', '[SAMPLE] Open Day', $today->copy()->subDays(14), 'Coordinate classroom displays and welcome visiting parents.', 'completed', $today->copy()->addDays(21));
            $this->event($teachers['Secondary'], 'Secondary', '[SAMPLE] Sports Day', $today->copy()->subDays(60), 'Prepare the equipment checklist and coordinate student teams.', 'completed', $today->copy()->subDays(7));
            $this->event($teachers['Preschool'], 'Preschool', '[SAMPLE] Science and Discovery Fair', $today->copy()->subDays(3), 'Arrange age-appropriate discovery stations and collect parent feedback.', 'draft', $today->copy()->addDays(30));
            $this->event($teachers['Primary'], 'Primary', '[SAMPLE] Reading and Community Day', $today->copy()->subDays(90), 'Organise shared reading activities and brief supporting staff.', 'completed', $today->copy()->subDays(45), true);
        });
    }

    private function annual(Teacher $teacher, string $section, Carbon $review, array $ratings, string $status = 'completed'): void
    {
        $examples = [
            'Sample attendance register shows regular arrival before the morning briefing.',
            'Sample term marks and lesson plans were submitted by the agreed dates.',
            'Sample lesson observation records clear objectives and checks for understanding.',
            'Sample observation shows clear routines and a calm classroom.',
            'Sample lesson includes practical activities and differentiated resources.',
            'Sample support log records individual help for learners who needed extra practice.',
            'Sample Open Day checklist records completed classroom displays.',
            'Sample resource folder includes a shared activity bank for the section.',
            'Sample planning notes show constructive cooperation with colleagues.',
            'Sample parent meeting notes record clear and timely follow-up.',
            'Sample duty checklist records completed assigned responsibilities.',
            'Sample training log records participation in a teaching workshop.',
            'Sample anonymised work comparison records progress against learning objectives.',
        ];
        $criteria = array_map(fn ($criterion) => array_merge($criterion, [
            'rating' => $ratings[(int) $criterion['id']] ?? null,
            'remarks' => isset($ratings[(int) $criterion['id']]) ? $examples[(int) $criterion['id'] - 1] : null,
        ]), EvaluationForm::criteria());
        $completeRatings = collect($criteria)->every(fn ($c) => $c['rating'] !== null);
        $target = $review->copy()->addDays(30);
        $this->record(['teacher_id' => $teacher->id, 'appraisal_year' => $review->year], [
            ...$this->profile($teacher, $section, $review, $status),
            'type' => 'annual',
            'period_start' => $review->copy()->startOfYear(),
            'period_end' => $review,
            'years_of_service' => '3 years (sample)',
            'criteria' => $criteria,
            'score' => $completeRatings ? round(collect($criteria)->sum(fn ($c) => $c['weight'] * $c['rating'] / 5), 2) : null,
            'evidence' => [[
                'area' => 'Teaching quality and academic delivery',
                'examples' => 'Sample observation: the teacher used clear learning objectives, practical tasks and a short review activity.',
                'strengths' => 'Clear explanations and well-prepared resources.',
                'improvement' => 'Allow more time for individual feedback.',
                'action' => 'Trial a short feedback checklist in two lessons.',
                'review_date' => $target->toDateString(),
                'reviewer' => '[SAMPLE] Head of '.$section,
            ]],
            'development' => [[
                'goal' => 'Provide more consistent individual feedback.',
                'training' => 'Attend a coaching session and trial the feedback checklist.',
                'support' => 'Section head provides a model checklist and one observation.',
                'measure' => 'Two lesson observations include feedback to each learner.',
                'target_date' => $target->toDateString(),
                'notes' => $status === 'draft' ? 'Sample draft: remaining ratings have not been entered.' : 'Sample action agreed during the review discussion.',
            ]],
            'follow_up_completed_date' => $review->year < today()->year ? $target : null,
            'follow_up_notes' => $review->year < today()->year ? 'SAMPLE DATA: follow-up completed and the feedback checklist reviewed.' : null,
        ]);
    }

    private function event(Teacher $teacher, string $section, string $name, Carbon $date, string $duty, string $status, Carbon $target, bool $followedUp = false): void
    {
        $this->record(['teacher_id' => $teacher->id, 'type' => 'event', 'event_name' => $name], [
            ...$this->profile($teacher, $section, $date->copy()->addDay(), $status),
            'appraisal_year' => null,
            'event_date' => $date,
            'responsibility' => 'SAMPLE DATA: '.$duty,
            'evidence' => [
                [
                    'area' => 'School events and extracurricular activities',
                    'examples' => 'Sample event checklist shows that the assigned activity was prepared before visitors arrived.',
                    'strengths' => 'Preparation, organisation and clear communication.',
                    'improvement' => 'Delegate the final equipment check earlier.',
                    'action' => 'Assign an equipment lead and use a shared checklist for the next event.',
                    'review_date' => $target->toDateString(),
                    'reviewer' => '[SAMPLE] Event Coordinator',
                ],
                [
                    'area' => 'Teamwork and professional conduct',
                    'examples' => 'Sample briefing notes show that colleagues understood the activity schedule and their duties.',
                    'strengths' => 'Helpful support for colleagues.',
                    'improvement' => $status === 'draft' ? null : 'Share the final timetable before the briefing.',
                    'action' => $status === 'draft' ? null : 'Circulate a one-page timetable before the next function.',
                    'review_date' => $target->toDateString(),
                    'reviewer' => $status === 'draft' ? null : '[SAMPLE] Section Head',
                ],
            ],
            'development' => [[
                'goal' => 'Improve delegation and preparation for school functions.',
                'training' => 'Plan the next event with a mentor using a responsibility checklist.',
                'support' => 'Event coordinator provides a planning example and review meeting.',
                'measure' => 'Each activity has a named lead and a completed preparation check.',
                'target_date' => $target->toDateString(),
                'notes' => $followedUp ? 'Sample review confirms that a named activity lead and checklist were used.' : 'Sample development plan awaiting its follow-up review.',
            ]],
            'follow_up_completed_date' => $followedUp ? $target : null,
            'follow_up_notes' => $followedUp ? 'SAMPLE DATA: the mentor reviewed the checklist and closed the agreed actions.' : null,
        ]);
    }

    private function profile(Teacher $teacher, string $section, Carbon $review, string $status): array
    {
        return [
            'teacher_name' => $teacher->name,
            'staff_id' => 'SAMPLE-'.strtoupper($section),
            'section' => $section,
            'subject_class' => $teacher->assigned_classes,
            'evaluator' => '[SAMPLE] Head of '.$section,
            'review_date' => $review,
            'status' => $status,
            'completed_at' => $status === 'completed' ? $review->copy()->startOfDay() : null,
            'teacher_comments' => 'SAMPLE DATA ONLY: this fictional example demonstrates a review discussion and agreed development goals.',
            'management_comments' => 'SAMPLE DATA ONLY: use this record to try the evaluation screens, filters and exports. No real teacher assessment or employment decision is recorded.',
            'version' => 1,
        ];
    }

    private function record(array $identity, array $data): void
    {
        $evaluation = TeacherEvaluation::firstOrCreate($identity, $data);
        if ($evaluation->wasRecentlyCreated) {
            app(AuditLogger::class)->log('teacher_evaluation.sample_seeded', $evaluation, 'Clearly labelled sample evaluation added', ['sample' => true, 'type' => $evaluation->type]);
        }
    }
}
