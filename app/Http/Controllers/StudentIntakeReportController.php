<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StudentIntakeReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $classes = ClassRoom::orderBy('level')->orderBy('name')->get(['id', 'name']);
        $request->mergeIfMissing(['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]);
        $filters = array_replace(['class_room_id' => null, 'status' => 'all', 'q' => '', 'sort' => 'date_desc', 'per_page' => 25], array_filter($request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'class_room_id' => ['nullable', Rule::in(array_merge(['unassigned'], $classes->modelKeys()))],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive', 'alumni'])],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['date_desc', 'date_asc', 'name_asc'])],
            'per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1', 'max:200000'],
            'download' => ['nullable', Rule::in(['pdf', 'csv'])],
        ]), fn ($value) => $value !== null));
        if (! empty($filters['download'])) {
            abort_unless($request->user()->can('reports.download'), 403);
        }
        // Exclusive next-day boundary includes the entire last date for DATE or datetime-backed records.
        $query = Student::query()->with('classRoom')->where('joining_date', '>=', $filters['from'])
            ->where('joining_date', '<', \Carbon\Carbon::parse($filters['to'])->addDay()->toDateString());
        if ($filters['class_room_id'] === 'unassigned') {
            $query->whereNull('class_room_id');
        } elseif ($filters['class_room_id']) {
            $query->where('class_room_id', $filters['class_room_id']);
        }
        match ($filters['status']) {
            'active' => $query->where('active', true)->where('alumni', false),
            'inactive' => $query->where('active', false)->where('alumni', false),
            'alumni' => $query->where('alumni', true),
            default => null,
        };
        $needle = trim($filters['q'] ?? '');
        if ($needle !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $needle).'%';
            $query->where(fn ($q) => $q->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("admission_number LIKE ? ESCAPE '!'", [$like]));
        }
        $total = (clone $query)->count();
        $intakeDays = (clone $query)->distinct()->count('joining_date');
        $classCounts = (clone $query)->toBase()->select('class_room_id')->selectRaw('COUNT(*) as total')
            ->groupBy('class_room_id')->orderByDesc('total')->orderBy('class_room_id')->get()
            ->map(fn ($group) => ['id' => $group->class_room_id ?? 'unassigned',
                'name' => $classes->firstWhere('id', $group->class_room_id)?->name ?? 'Not assigned', 'total' => (int) $group->total]);
        if ($filters['sort'] === 'name_asc') {
            $query->orderBy('name')->orderBy('joining_date');
        } else {
            $query->orderBy('joining_date', $filters['sort'] === 'date_asc' ? 'asc' : 'desc')->orderBy('name');
        }
        $query->orderBy('id');
        $filterQuery = Arr::except($filters, ['download', 'page']);
        $classLabel = $filters['class_room_id'] === 'unassigned' ? 'Not assigned'
            : ($classes->firstWhere('id', $filters['class_room_id'])?->name ?? 'All classes');
        $statusLabel = ['all' => 'All students', 'active' => 'Active', 'inactive' => 'Inactive', 'alumni' => 'Alumni'][$filters['status']];
        $period = \Carbon\Carbon::parse($filters['from'])->format('d M Y').' – '.\Carbon\Carbon::parse($filters['to'])->format('d M Y');
        $data = compact('classes', 'filters', 'filterQuery', 'total', 'intakeDays', 'classCounts', 'classLabel', 'statusLabel', 'period');
        if (($filters['download'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Registration number', 'Student name', 'Joining date', 'Current class', 'Status'], ',', '"', '');
                foreach ($query->lazy(500) as $student) {
                    $values = [$student->admission_number ?? '', $student->name, $student->joining_date->toDateString(),
                        $student->classRoom?->name ?? 'Not assigned', $student->alumni ? 'Alumni' : ($student->active ? 'Active' : 'Inactive')];
                    // Keep spreadsheet software from executing user-entered names/numbers as formulas.
                    fputcsv($stream, array_map(fn ($value) => preg_match('/^[\s\x00-\x1f]*[=+@-]/u', (string) $value) ? "'".$value : $value, $values), ',', '"', '');
                }
                fclose($stream);
            }, 'student-intake-'.$filters['from'].'-to-'.$filters['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        if (($filters['download'] ?? null) === 'pdf') {
            return Pdf::loadView('reports.student-intake-pdf', $data + [
                'items' => $query->get(), 'schoolName' => app('settings')->get('school.name', config('app.name')),
            ])->setPaper('a4')->download('student-intake-'.$filters['from'].'-to-'.$filters['to'].'.pdf');
        }

        return view('reports.student-intake', $data + ['items' => $query->paginate((int) $filters['per_page'])->appends($filterQuery)]);
    }
}
