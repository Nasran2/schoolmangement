<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeachersBulkUploadController extends Controller
{
    public function create(): View
    {
        return view('teachers.bulk_upload');
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

            $teacher = Teacher::query()->updateOrCreate(
                ['phone' => $data['phone'] ?? null],
                [
                    'name' => $data['name'] ?? '',
                    'address' => $data['address'] ?? null,
                    'joining_date' => $data['joining_date'] ?? null,
                    'assigned_classes' => $data['assigned_classes'] ?? null,
                    'salary_amount' => isset($data['salary_amount']) ? (float) $data['salary_amount'] : 0,
                    'active' => (($data['active'] ?? '1') === '1'),
                ]
            );
            if ($teacher) $created++;
        }
        fclose($handle);
        Storage::delete($path);

        return redirect()->route('teachers.index')->with('status', "Imported {$created} teachers.");
    }
}
