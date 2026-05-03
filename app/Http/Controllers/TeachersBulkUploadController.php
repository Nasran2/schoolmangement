<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $handle = null;

        try {
            $handle = fopen($full, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to read uploaded CSV file.');
            }

            $header = null;
            $created = 0;
            $skipped = 0;
            $errors = [];
            while (($row = fgetcsv($handle)) !== false) {
                if ($header === null) {
                    $header = array_map('trim', $row);
                    continue;
                }
                $data = [];
                foreach ($header as $idx => $key) {
                    $data[$key] = $row[$idx] ?? null;
                }

                $name = trim((string) ($data['name'] ?? ''));
                $phone = trim((string) ($data['phone'] ?? ''));
                $email = trim((string) ($data['email'] ?? ''));

                if ($name === '') {
                    $skipped++;
                    $errors[] = 'Skipped row with missing teacher name.';
                    continue;
                }

                if ($phone === '' && $email === '') {
                    $skipped++;
                    $errors[] = 'Skipped '.$name.' because both phone and email are empty.';
                    continue;
                }

                $identity = $phone !== ''
                    ? ['phone' => $phone]
                    : ['email' => $email];

                $teacher = Teacher::query()->updateOrCreate(
                    $identity,
                    [
                        'name' => $name,
                        'email' => $email !== '' ? $email : null,
                        'phone' => $phone !== '' ? $phone : null,
                        'address' => $data['address'] ?? null,
                        'joining_date' => $data['joining_date'] ?? null,
                        'assigned_classes' => $data['assigned_classes'] ?? null,
                        'salary_amount' => isset($data['salary_amount']) ? (float) $data['salary_amount'] : 0,
                        'active' => (($data['active'] ?? '1') === '1'),
                    ]
                );
                if ($teacher) {
                    $created++;
                }
            }

            $message = "Imported {$created} teachers.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} rows.";
            }
            if (! empty($errors)) {
                $message .= ' First errors: '.implode(' | ', array_slice($errors, 0, 3));
            }

            return redirect()->route('teachers.index')->with('status', $message);
        } catch (\Throwable $e) {
            Log::error('Teacher bulk upload failed.', [
                'user_id' => $request->user()?->id,
                'file' => $validated['csv']->getClientOriginalName(),
                'route' => $request->route()?->getName(),
                'action' => optional($request->route())->getActionName(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'csv' => 'Import failed. Please validate the CSV format and try again.',
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            Storage::delete($path);
        }
    }
}
