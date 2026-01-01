<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassRoom;
use App\Services\SMS\SmsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function sendStudent(Request $request, Student $student, SmsService $sms): RedirectResponse
    {

        if (! $sms->isConfigured()) {
            return back()->withErrors(['sms' => 'SMS gateway not configured.']);
        }

        $template = (string) app('settings')->get('sms.template.due', 'Dear {name}, your due is {amount}.');
        $message = $this->interpolate($template, [
            'name' => $student->name,
            'amount' => number_format((float) ($student->computed_due_amount ?? $student->due_amount), 2),
            'date' => Carbon::now()->addDays(7)->toDateString(),
        ]);

        $ok = $sms->send([$student->phone, $student->guardian_phone], $message);

        return back()->with('status', $ok ? 'SMS sent.' : 'Failed to send SMS.');
    }

    public function sendDueStudents(Request $request, SmsService $sms): RedirectResponse
    {

        if (! $sms->isConfigured()) {
            return back()->withErrors(['sms' => 'SMS gateway not configured.']);
        }

        $students = Student::query()
            ->where('active', true)
            ->limit(500)
            ->get();

        $template = (string) app('settings')->get('sms.template.due', 'Dear {name}, your due is {amount}.');
        $phones = [];
        foreach ($students as $s) {
            $msg = $this->interpolate($template, [
                'name' => $s->name,
                'amount' => number_format((float) ($s->computed_due_amount ?? $s->due_amount), 2),
                'date' => Carbon::now()->addDays(7)->toDateString(),
            ]);
            $targets = array_filter([$s->phone, $s->guardian_phone]);
            if (!empty($targets)) {
                // Send individually to preserve personalisation; could batch if gateway supports templates.
                $sms->send($targets, $msg);
            }
        }

        return back()->with('status', 'Bulk SMS queued for due students.');
    }

    public function sendSelectedClasses(Request $request, SmsService $sms): RedirectResponse
    {

        $validated = $request->validate([
            'class_room_ids' => ['required', 'array'],
            'class_room_ids.*' => ['integer', 'exists:class_rooms,id'],
        ]);

        if (! $sms->isConfigured()) {
            return back()->withErrors(['sms' => 'SMS gateway not configured.']);
        }

        $rooms = ClassRoom::query()->whereIn('id', $validated['class_room_ids'])->get(['id']);
        $students = Student::query()
            ->where('active', true)
            ->whereIn('class_room_id', $rooms->pluck('id'))
            ->get();

        $template = (string) app('settings')->get('sms.template.due', 'Dear {name}, your due is {amount}.');
        foreach ($students as $s) {
            $msg = $this->interpolate($template, [
                'name' => $s->name,
                'amount' => number_format((float) ($s->computed_due_amount ?? $s->due_amount), 2),
                'date' => Carbon::now()->addDays(7)->toDateString(),
            ]);
            $targets = array_filter([$s->phone, $s->guardian_phone]);
            if (!empty($targets)) {
                $sms->send($targets, $msg);
            }
        }

        return back()->with('status', 'SMS sent to selected classes.');
    }

    private function interpolate(string $template, array $vars): string
    {
        $out = $template;
        foreach ($vars as $k => $v) {
            $out = str_replace('{'.$k.'}', (string) $v, $out);
        }
        return $out;
    }
}
