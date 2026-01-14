<?php

namespace App\Http\Controllers;

use App\Services\PromotionService;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function promote(Request $request, PromotionService $service): RedirectResponse
    {
        $count = $service->promoteAll($request->user()?->id);
        return back()->with('status', "Promoted {$count} students.");
    }

    public function demote(Request $request, PromotionService $service): RedirectResponse
    {
        $count = $service->demoteAll($request->user()?->id);
        return back()->with('status', "Demoted {$count} students.");
    }

    public function promoteStudent(Request $request, Student $student, PromotionService $service): RedirectResponse
    {
        $ok = $service->promoteOne($student, $request->user()?->id);
        $student->refresh();
        if ($ok && $student->alumni) {
            return back()->with('status', 'Student graduated to Alumni.');
        }
        return back()->with('status', $ok ? 'Student promoted.' : 'No next class available.');
    }

    public function demoteStudent(Request $request, Student $student, PromotionService $service): RedirectResponse
    {
        $ok = $service->demoteOne($student, $request->user()?->id);
        return back()->with('status', $ok ? 'Student demoted.' : 'No previous class available.');
    }
}
