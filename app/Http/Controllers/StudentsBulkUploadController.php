<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentsBulkUploadController extends Controller
{
    public function create(): View
    {
        return view('students.bulk_upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $validated['csv']->store('tmp');
        $full = Storage::path($path);

        $handle = fopen($full, 'r');
        $header = null;
        $created = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }
            $data = [];
            foreach ($header as $idx => $key) {
                $data[$key] = $row[$idx] ?? null;
            }

            $classRoomId = null;
            if (!empty($data['class_room_level'])) {
                $cr = ClassRoom::query()->where('level', (int) $data['class_room_level'])->first();
                $classRoomId = $cr?->id;
            } elseif (!empty($data['class_room_name'])) {
                $cr = ClassRoom::query()->where('name', $data['class_room_name'])->first();
                $classRoomId = $cr?->id;
            }

            $student = Student::query()->updateOrCreate(
                ['admission_number' => $data['admission_number'] ?? null],
                [
                    'name' => $data['name'] ?? '',
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'guardian_name' => $data['guardian_name'] ?? null,
                    'guardian_phone' => $data['guardian_phone'] ?? null,
                    'joining_date' => $data['joining_date'] ?? null,
                    'year' => $data['year'] ?? null,
                    'class_room_id' => $classRoomId,
                    'class' => $classRoomId ? (ClassRoom::find($classRoomId)?->name) : ($data['class'] ?? null),
                    'monthly_fee' => isset($data['monthly_fee']) ? (float) $data['monthly_fee'] : 0,
                    'due_amount' => isset($data['due_amount']) ? (float) $data['due_amount'] : 0,
                    'active' => (($data['active'] ?? '1') === '1'),
                ]
            );
            if ($student) $created++;
        }
        fclose($handle);
        Storage::delete($path);

        return redirect()->route('students.index')->with('status', "Imported {$created} students.");
    }
}
