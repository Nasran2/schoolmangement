<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function create()
    {
        $balances = \App\Models\OpeningBalance::orderByDesc('date')->get();
        return view('opening-balance.create', compact('balances'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric',
        ]);

        \App\Models\OpeningBalance::updateOrCreate(
            ['date' => $request->date],
            ['amount' => $request->amount]
        );

        return redirect()->back()->with('success', 'Opening balance saved successfully.');
    }

    public function destroy(\App\Models\OpeningBalance $openingBalance)
    {
        $openingBalance->delete();
        return redirect()->back()->with('success', 'Opening balance deleted successfully.');
    }
}
