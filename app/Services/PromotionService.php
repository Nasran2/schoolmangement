<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentPromotionHistory;

class PromotionService
{
    /**
     * Promote all active students by one level if the next level exists.
     */
    public function promoteAll(?int $performedBy = null): int
    {
        $levelToRoom = ClassRoom::query()
            ->whereNotNull('level')
            ->get()
            ->keyBy('level');

        if ($levelToRoom->isEmpty()) {
            return 0;
        }

        $count = 0;
        Student::query()
            ->with('classRoom')
            ->where('active', true)
            ->chunkById(200, function ($students) use ($levelToRoom, &$count, $performedBy) {
                foreach ($students as $student) {
                    $level = $student->classRoom?->level;
                    if ($level === null) {
                        continue;
                    }
                    $target = $level + 1;
                    $room = $levelToRoom->get($target);
                    if (! $room) {
                        continue;
                    }
                    $fromId = $student->class_room_id;
                    $student->class_room_id = (int) $room->id;
                    $student->class = $room->name;
                    if ($room->monthly_fee !== null) {
                        $student->monthly_fee = $room->monthly_fee;
                        $student->due_amount = $room->monthly_fee;
                    }
                    $student->save();
                    StudentPromotionHistory::create([
                        'student_id' => $student->id,
                        'from_class_room_id' => $fromId,
                        'to_class_room_id' => (int) $room->id,
                        'action' => 'promote',
                        'academic_year' => session('academic_year') ?? $student->year,
                        'performed_by' => $performedBy,
                        'notes' => null,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Demote all active students by one level if the previous level exists.
     */
    public function demoteAll(?int $performedBy = null): int
    {
        $levelToRoom = ClassRoom::query()
            ->whereNotNull('level')
            ->get()
            ->keyBy('level');

        if ($levelToRoom->isEmpty()) {
            return 0;
        }

        $count = 0;
        Student::query()
            ->with('classRoom')
            ->where('active', true)
            ->chunkById(200, function ($students) use ($levelToRoom, &$count, $performedBy) {
                foreach ($students as $student) {
                    $level = $student->classRoom?->level;
                    if ($level === null) {
                        continue;
                    }
                    $target = $level - 1;
                    $room = $levelToRoom->get($target);
                    if (! $room) {
                        continue;
                    }
                    $fromId = $student->class_room_id;
                    $student->class_room_id = (int) $room->id;
                    $student->class = $room->name;
                    if ($room->monthly_fee !== null) {
                        $student->monthly_fee = $room->monthly_fee;
                        $student->due_amount = $room->monthly_fee;
                    }
                    $student->save();
                    StudentPromotionHistory::create([
                        'student_id' => $student->id,
                        'from_class_room_id' => $fromId,
                        'to_class_room_id' => (int) $room->id,
                        'action' => 'demote',
                        'academic_year' => session('academic_year') ?? $student->year,
                        'performed_by' => $performedBy,
                        'notes' => null,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    /** Promote a single student to next level if available. */
    public function promoteOne(Student $student, ?int $performedBy = null): bool
    {
        $level = $student->classRoom?->level;
        if ($level === null) return false;
        $target = $level + 1;
        $room = ClassRoom::query()->where('level', $target)->first();
        if (! $room) return false;
        $fromId = $student->class_room_id;
        $student->class_room_id = (int) $room->id;
        $student->class = $room->name;
        if ($room->monthly_fee !== null) {
            $student->monthly_fee = $room->monthly_fee;
            $student->due_amount = $room->monthly_fee;
        }
        $student->save();
        StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $fromId,
            'to_class_room_id' => (int) $room->id,
            'action' => 'promote',
            'academic_year' => session('academic_year') ?? $student->year,
            'performed_by' => $performedBy,
            'notes' => null,
        ]);
        return true;
    }

    /** Demote a single student to previous level if available. */
    public function demoteOne(Student $student, ?int $performedBy = null): bool
    {
        $level = $student->classRoom?->level;
        if ($level === null) return false;
        $target = $level - 1;
        $room = ClassRoom::query()->where('level', $target)->first();
        if (! $room) return false;
        $fromId = $student->class_room_id;
        $student->class_room_id = (int) $room->id;
        $student->class = $room->name;
        if ($room->monthly_fee !== null) {
            $student->monthly_fee = $room->monthly_fee;
            $student->due_amount = $room->monthly_fee;
        }
        $student->save();
        StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $fromId,
            'to_class_room_id' => (int) $room->id,
            'action' => 'demote',
            'academic_year' => session('academic_year') ?? $student->year,
            'performed_by' => $performedBy,
            'notes' => null,
        ]);
        return true;
    }
}
