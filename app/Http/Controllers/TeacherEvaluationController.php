<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Hr\DirectoryController;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Services\AuditLogger;
use App\Services\TeacherEvaluations\EvaluationExport;
use App\Services\TeacherEvaluations\EvaluationForm;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherEvaluationController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters] = $this->filtered($request);

        return view('teacher-evaluations.index', [
            'evaluations' => $query->orderByDesc('review_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'teachers' => Teacher::orderBy('name')->get(['id', 'name', 'active']),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate(['type' => ['nullable', Rule::in(['annual', 'event'])], 'teacher_id' => ['nullable', 'integer', 'exists:teachers,id']]);
        $evaluation = new TeacherEvaluation(['type' => $data['type'] ?? 'annual', 'teacher_id' => $data['teacher_id'] ?? null, 'status' => 'draft', 'appraisal_year' => now()->year, 'criteria' => EvaluationForm::criteria()]);

        if ($evaluation->teacher_id) {
            $evaluation->subject_class = Teacher::whereKey($evaluation->teacher_id)->value('assigned_classes');
        }

        return $this->form($evaluation);
    }

    public function edit(TeacherEvaluation $evaluation)
    {
        return $this->form($evaluation);
    }

    private function form(TeacherEvaluation $evaluation)
    {
        return view('teacher-evaluations.form', [
            'evaluation' => $evaluation,
            'teachers' => Teacher::orderBy('name')->get(DirectoryController::TEACHER_FIELDS),
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new TeacherEvaluation);
    }

    public function update(Request $request, TeacherEvaluation $evaluation)
    {
        return $this->save($request, $evaluation);
    }

    private function save(Request $request, TeacherEvaluation $evaluation)
    {
        $complete = $request->input('status') === 'completed';
        $type = $evaluation->exists ? $evaluation->type : $request->input('type');
        $annual = $type === 'annual';
        $rules = [
            'type' => ['required', Rule::in($evaluation->exists ? [$evaluation->type] : ['annual', 'event'])],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id', ...($evaluation->exists ? [Rule::in([$evaluation->teacher_id])] : [])],
            'status' => ['required', Rule::in(['draft', 'completed'])],
            'acknowledge_incomplete' => ['nullable', 'boolean'],
            'version' => [$evaluation->exists ? 'required' : 'nullable', 'integer', 'min:1'],
            'staff_id' => ['nullable', 'string', 'max:80'],
            'section' => ['nullable', Rule::in(EvaluationForm::SECTIONS)],
            'subject_class' => ['nullable', 'string', 'max:191'],
            'years_of_service' => ['nullable', 'string', 'max:80'],
            'evaluator' => ['nullable', 'string', 'max:191'],
            'review_date' => ['nullable', 'date_format:Y-m-d'],
            'teacher_comments' => ['nullable', 'string', 'max:10000'],
            'management_comments' => ['nullable', 'string', 'max:10000'],
            'teacher_signed_name' => ['nullable', 'string', 'max:191'],
            'teacher_signed_date' => ['nullable', 'date_format:Y-m-d'],
            'evaluator_signed_name' => ['nullable', 'string', 'max:191'],
            'evaluator_signed_date' => ['nullable', 'date_format:Y-m-d'],
            'follow_up_completed_date' => ['nullable', 'date_format:Y-m-d'],
            'follow_up_notes' => ['nullable', 'string', 'max:10000'],
            'evidence' => ['nullable', 'array', 'max:50'],
            'evidence.*' => ['array'],
            'development' => ['nullable', 'array', 'max:50'],
            'development.*' => ['array'],
        ];
        if ($annual) {
            $rules += [
                'appraisal_year' => ['required', 'integer', 'between:2000,2100', Rule::unique('teacher_evaluations')->where('teacher_id', $request->input('teacher_id'))->ignore($evaluation->id)],
                'period_start' => ['nullable', 'date_format:Y-m-d'],
                'period_end' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('period_start'), 'after_or_equal:period_start')],
                'ratings' => ['nullable', 'array'],
                'additional_ratings' => ['nullable', 'array:2,3'],
                'additional_ratings.*' => ['nullable', 'array'],
                'supervisors' => ['nullable', 'array:0,1,2', 'max:3'],
                'supervisors.*' => ['nullable', 'string', 'max:191'],
                'remarks' => ['nullable', 'array'],
            ];
            $sourceCriteria = $evaluation->exists ? $evaluation->criteria : EvaluationForm::criteria();
            foreach ($sourceCriteria as $criterion) {
                foreach ([1, 2, 3] as $supervisor) {
                    $path = $supervisor === 1 ? 'ratings' : 'additional_ratings.'.$supervisor;
                    $rules[$path.'.'.$criterion['id']] = ['nullable', 'integer', 'between:1,5'];
                }
                $rules['remarks.'.$criterion['id']] = ['nullable', 'string', 'max:5000'];
            }
        } else {
            $rules += [
                'event_name' => ['required', 'string', 'max:191'],
                'event_date' => ['nullable', 'date_format:Y-m-d'],
                'responsibility' => ['nullable', 'string', 'max:10000'],
            ];
        }
        foreach (['evidence' => EvaluationForm::EVIDENCE_FIELDS, 'development' => EvaluationForm::DEVELOPMENT_FIELDS] as $group => $fields) {
            foreach ($fields as $key => $label) {
                $rules[$group.'.*.'.$key] = str_ends_with($key, '_date') ? ['nullable', 'date_format:Y-m-d'] : ['nullable', 'string', 'max:5000'];
            }
        }
        $attributes = [];
        foreach ($annual ? $sourceCriteria : [] as $criterion) {
            foreach ([1, 2, 3] as $supervisor) {
                $path = $supervisor === 1 ? 'ratings' : 'additional_ratings.'.$supervisor;
                $attributes[$path.'.'.$criterion['id']] = 'Supervisor '.$supervisor.' rating for '.$criterion['area'];
            }
        }
        $data = $request->validate($rules, [], $attributes);
        $data['evidence'] = $this->cleanRows($data['evidence'] ?? [], EvaluationForm::EVIDENCE_FIELDS);
        $data['development'] = $this->cleanRows($data['development'] ?? [], EvaluationForm::DEVELOPMENT_FIELDS);
        $criteria = [];
        $score = null;
        if ($annual) {
            $criteria = array_map(function ($criterion) use ($data) {
                $id = $criterion['id'];
                $ratings = array_map(fn ($rating) => $rating === null ? null : (int) $rating, [
                    $data['ratings'][$id] ?? null,
                    $data['additional_ratings'][2][$id] ?? null,
                    $data['additional_ratings'][3][$id] ?? null,
                ]);

                return array_merge($criterion, ['rating' => $ratings[0], 'supervisor_ratings' => $ratings, 'remarks' => $data['remarks'][$id] ?? null]);
            }, $evaluation->exists ? $evaluation->criteria : EvaluationForm::criteria());
            $score = EvaluationForm::score($criteria);
            $data['supervisors'] = array_map(fn ($index) => $data['supervisors'][$index] ?? null, [0, 1, 2]);
        }
        $missingDetails = EvaluationForm::missingDetails(new TeacherEvaluation(array_merge($data, ['criteria' => $criteria])));
        if (count($missingDetails) && ! $request->boolean('acknowledge_incomplete')) {
            return back()->withInput($request->except('acknowledge_incomplete'))->with('evaluation_missing_details', $missingDetails)->with('evaluation_pending_status', $data['status']);
        }
        unset($data['ratings'], $data['additional_ratings'], $data['remarks'], $data['acknowledge_incomplete']);
        try {
            DB::transaction(function () use ($evaluation, $data, $criteria, $score, $request, $annual, $complete) {
                $record = $evaluation->exists ? TeacherEvaluation::lockForUpdate()->findOrFail($evaluation->id) : $evaluation;
                if ($record->exists && $record->version !== (int) $data['version']) {
                    throw ValidationException::withMessages(['version' => 'Another user updated this evaluation. Reload it before saving so their changes are preserved.']);
                }
                $before = $record->exists ? $record->getAttributes() : null;
                unset($data['version']);
                $record->fill($data);
                $record->forceFill([
                    'teacher_name' => $record->exists ? $record->getRawOriginal('teacher_name') : Teacher::findOrFail($data['teacher_id'])->name,
                    'criteria' => $annual ? $criteria : null,
                    'score' => $score,
                    'appraisal_year' => $annual ? $data['appraisal_year'] : null,
                    'created_by' => $record->exists ? $record->created_by : $request->user()->id,
                    'updated_by' => $request->user()->id,
                    'completed_at' => $complete ? ($record->completed_at ?? now()) : null,
                    'version' => $record->exists ? $record->version + 1 : 1,
                ])->save();
                app(AuditLogger::class)->log($before ? 'teacher_evaluation.updated' : 'teacher_evaluation.created', $record, $record->type_label.' saved', ['before' => $before, 'after' => $record->getAttributes()]);
                $evaluation->setRawAttributes($record->getAttributes(), true);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['appraisal_year' => 'This teacher already has an appraisal for that year. Open the existing record to update it.']);
        }

        return redirect()->route('teacher-evaluations.show', $evaluation)->with('status', 'Evaluation saved as '.$evaluation->status.'.'.(count($missingDetails) ? ' Some review details are incomplete.' : ''));
    }

    private function cleanRows(array $rows, array $fields): array
    {
        return array_values(array_filter(array_map(fn ($row) => array_intersect_key($row, $fields), $rows), fn ($row) => count(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== '')) > 0));
    }

    public function show(TeacherEvaluation $evaluation)
    {
        return view('teacher-evaluations.show', ['evaluation' => $evaluation->load(['creator:id,name', 'editor:id,name'])]);
    }

    public function destroy(TeacherEvaluation $evaluation)
    {
        DB::transaction(function () use ($evaluation) {
            app(AuditLogger::class)->log('teacher_evaluation.deleted', $evaluation, 'Teacher evaluation deleted', ['record' => $evaluation->getAttributes()]);
            $evaluation->delete();
        });

        return redirect()->route('teacher-evaluations.index')->with('status', 'Evaluation deleted.');
    }

    public function template(Request $request, EvaluationExport $export)
    {
        $data = $request->validate(['type' => ['required', Rule::in(['annual', 'event'])], 'format' => ['required', Rule::in(['pdf', 'xlsx'])], 'teacher_id' => ['nullable', 'integer', 'exists:teachers,id']]);
        $teacher = empty($data['teacher_id']) ? null : Teacher::select(DirectoryController::TEACHER_FIELDS)->findOrFail($data['teacher_id']);

        return $export->download(collect(), $data['type'], $data['format'], true, [], $teacher);
    }

    public function export(Request $request, EvaluationExport $export)
    {
        $request->validate(['format' => ['required', Rule::in(['pdf', 'xlsx'])]]);
        [$query, $filters] = $this->filtered($request);
        if ((clone $query)->count() > 100) {
            throw ValidationException::withMessages(['export' => 'Select a teacher, year or date range to export no more than 100 evaluations at a time.']);
        }

        return $export->download($query->orderBy('teacher_name')->orderBy('appraisal_year')->orderBy('event_date')->get(), null, $request->input('format'), false, $filters);
    }

    public function download(Request $request, TeacherEvaluation $evaluation, EvaluationExport $export)
    {
        $data = $request->validate(['format' => ['required', Rule::in(['pdf', 'xlsx'])]]);

        return $export->download(collect([$evaluation]), $evaluation->type, $data['format']);
    }

    private function filtered(Request $request): array
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['annual', 'event'])],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'section' => ['nullable', Rule::in(EvaluationForm::SECTIONS)],
            'status' => ['nullable', Rule::in(['draft', 'completed'])],
            'event_from' => ['nullable', 'date_format:Y-m-d'],
            'event_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:event_from'],
            'review_from' => ['nullable', 'date_format:Y-m-d'],
            'review_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:review_from'],
            'q' => ['nullable', 'string', 'max:120'],
            'follow_up' => ['nullable', Rule::in(['overdue', 'completed'])],
        ]);
        $query = TeacherEvaluation::query();
        foreach (['type', 'teacher_id', 'section', 'status'] as $key) {
            $query->when($filters[$key] ?? null, fn ($q, $value) => $q->where($key, $value));
        }
        $query->when($filters['year'] ?? null, fn ($q, $year) => $q->where('type', 'annual')->where('appraisal_year', $year));
        foreach (['event' => 'event_date', 'review' => 'review_date'] as $prefix => $column) {
            $query->when($filters[$prefix.'_from'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date));
            $query->when($filters[$prefix.'_to'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
        }
        $query->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('teacher_name', 'like', '%'.$term.'%')->orWhere('event_name', 'like', '%'.$term.'%')->orWhere('evaluator', 'like', '%'.$term.'%')->orWhere('staff_id', 'like', '%'.$term.'%')));
        if (($filters['follow_up'] ?? null) === 'completed') {
            $query->whereNotNull('follow_up_completed_date');
        } elseif (($filters['follow_up'] ?? null) === 'overdue') {
            // JSON arrays are portable across the MySQL and SQLite installations used by this app.
            $ids = (clone $query)->whereNull('follow_up_completed_date')->get()->filter(fn ($e) => $e->follow_up_due && $e->follow_up_due < today()->toDateString())->modelKeys();
            $query->whereIn('id', $ids);
        }

        return [$query, $filters];
    }
}
