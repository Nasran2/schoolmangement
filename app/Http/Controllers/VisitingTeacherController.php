<?php

namespace App\Http\Controllers;

use App\Models\VisitingTeacher;
use Illuminate\Http\Request;

class VisitingTeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = VisitingTeacher::query()->orderBy('name');

        $term = (string) ($request->query('q') ?? $request->query('search') ?? '');
        $term = trim($term);
        if ($term !== '') {
            $like = '%' . str_replace('%', '\\%', $term) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('specialty', 'like', $like);
            });
        }

        $teachers = $query->paginate(20)->withQueryString();
        return view('visiting-teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('visiting-teachers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'specialty' => ['nullable','string','max:255'],
            'active' => ['nullable','boolean'],
        ]);
        VisitingTeacher::create($data);
        return redirect()->route('visiting-teachers.index')->with('status', 'Visiting teacher added');
    }

    public function edit(VisitingTeacher $visitingTeacher)
    {
        return view('visiting-teachers.edit', compact('visitingTeacher'));
    }

    public function update(Request $request, VisitingTeacher $visitingTeacher)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'specialty' => ['nullable','string','max:255'],
            'active' => ['nullable','boolean'],
        ]);
        $visitingTeacher->update($data);
        return redirect()->route('visiting-teachers.index')->with('status', 'Visiting teacher updated');
    }

    public function destroy(VisitingTeacher $visitingTeacher)
    {
        $visitingTeacher->delete();
        return redirect()->route('visiting-teachers.index')->with('status', 'Visiting teacher deleted');
    }
}
