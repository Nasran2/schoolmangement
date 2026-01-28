<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\VisitingTeacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherLookupController extends Controller
{
    private function formatLabel(string $typeLabel, string $name, ?string $phone): string
    {
        $phone = trim((string) $phone);

        return $phone !== ''
            ? $typeLabel.' — '.$name.' ('.$phone.')'
            : $typeLabel.' — '.$name;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $id = $request->query('id');

        if ($type !== null || $id !== null) {
            if (! in_array($type, ['teacher', 'visiting'], true)) {
                return response()->json(['message' => 'Invalid type'], 422);
            }
            if (! is_numeric($id)) {
                return response()->json(['message' => 'Invalid id'], 422);
            }

            $intId = (int) $id;
            if ($type === 'teacher') {
                $t = Teacher::query()->find($intId);
                if (! $t) {
                    return response()->json(null, 404);
                }
                return response()->json([
                    'type' => 'teacher',
                    'id' => $t->id,
                    'name' => $t->name,
                    'phone' => $t->phone,
                    'label' => $this->formatLabel('Teacher', $t->name, $t->phone),
                ]);
            }

            $t = VisitingTeacher::query()->find($intId);
            if (! $t) {
                return response()->json(null, 404);
            }
            return response()->json([
                'type' => 'visiting',
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'label' => $this->formatLabel('Visiting', $t->name, $t->phone),
            ]);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $like = '%'.str_replace('%', '\\%', $q).'%';

        $qDigits = preg_replace('/\D+/', '', $q) ?? '';
        $digitLike = $qDigits !== '' ? '%'.$qDigits.'%' : null;
        $normalizedPhoneExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        $teachers = Teacher::query()
            ->where('active', true)
            ->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->when($digitLike !== null && strlen($qDigits) >= 3, function ($query) use ($normalizedPhoneExpr, $digitLike) {
                $query->orWhereRaw($normalizedPhoneExpr." like ?", [$digitLike]);
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone']);

        $visitingTeachers = VisitingTeacher::query()
            ->where('active', true)
            ->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->when($digitLike !== null && strlen($qDigits) >= 3, function ($query) use ($normalizedPhoneExpr, $digitLike) {
                $query->orWhereRaw($normalizedPhoneExpr." like ?", [$digitLike]);
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone']);

        $out = [];
        foreach ($teachers as $t) {
            $out[] = [
                'type' => 'teacher',
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'label' => $this->formatLabel('Teacher', $t->name, $t->phone),
            ];
        }
        foreach ($visitingTeachers as $t) {
            $out[] = [
                'type' => 'visiting',
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'label' => $this->formatLabel('Visiting', $t->name, $t->phone),
            ];
        }

        return response()->json($out);
    }
}
