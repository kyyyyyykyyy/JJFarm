<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ternak;

class TernakController extends Controller
{
    public function update(Request $request, Ternak $ternak)
    {
        $validated = $request->validate([
            'ras' => 'nullable|string',
            'kandang' => 'nullable|string',
            'program' => 'nullable|string',
            'status_kehamilan' => 'nullable|string',
        ]);

        $ternak->update($validated);
        return redirect()->back(); 
    }
}