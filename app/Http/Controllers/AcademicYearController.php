<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\Student;

class AcademicYearController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:50'],
        ]);

        $request->session()->put('academic_year', $data['academic_year']);

        // Auto-promote students only when a Super Admin (settings.manage) changes the year.
        if ($request->user()?->can('settings.manage')) {
            $targetYearStart = $this->parseAcademicYearStart($data['academic_year']);
            if ($targetYearStart !== null) {
                $this->promoteStudentsTo($targetYearStart);
            }
        }

        return back();
    }

    private function parseAcademicYearStart(string $academicYear): ?int
    {
        if (preg_match('/(\d{4})/', $academicYear, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function promoteStudentsTo(int $targetYearStart): void
    {
        $levelToRoom = ClassRoom::query()
            ->whereNotNull('level')
            ->get()
            ->keyBy('level');

        if ($levelToRoom->isEmpty()) {
            return;
        }

        Student::query()
            ->with('classRoom')
            ->where('active', true)
            ->chunkById(200, function ($students) use ($targetYearStart, $levelToRoom) {
                foreach ($students as $student) {
                    $from = $student->promoted_until_year;
                    if ($from === null) {
                        $student->promoted_until_year = $targetYearStart;
                        $student->save();
                        continue;
                    }

                    $diff = $targetYearStart - $from;
                    if ($diff <= 0) {
                        continue;
                    }

                    $currentLevel = $student->classRoom?->level;
                    if ($currentLevel !== null) {
                        $targetLevel = $currentLevel + $diff;
                        $targetRoom = $levelToRoom->get($targetLevel);
                        if ($targetRoom) {
                            $student->class_room_id = (int) $targetRoom->id;
                            $student->class = $targetRoom->name;
                            // Refresh monthly fee and due when promoted to a new class
                            if ($targetRoom->monthly_fee !== null) {
                                $student->monthly_fee = (float) $targetRoom->monthly_fee;
                                $student->due_amount = (float) $targetRoom->monthly_fee;
                            }
                        }
                    }

                    $student->promoted_until_year = $targetYearStart;
                    $student->save();
                }
            });
    }
}
