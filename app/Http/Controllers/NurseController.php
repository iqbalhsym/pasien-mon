<?php

namespace App\Http\Controllers;

use App\Models\Nurse;
use Illuminate\Http\Request;

class NurseController extends Controller
{
    public function index()
    {
        $nurses = Nurse::orderBy('name', 'asc')->get();
        return view('nurses.index', compact('nurses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:nurses,name',
        ]);

        Nurse::create([
            'name' => trim($request->name),
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Ners baru berhasil ditambahkan.');
    }

    public function update(Request $request, Nurse $nurse)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:nurses,name,' . $nurse->id,
            'is_active' => 'required|boolean'
        ]);

        $nurse->update([
            'name' => trim($request->name),
            'is_active' => (bool)$request->is_active
        ]);

        return redirect()->back()->with('success', 'Data ners berhasil diperbarui.');
    }

    public function destroy(Nurse $nurse)
    {
        try {
            $nurse->delete();
            return redirect()->back()->with('success', 'Data ners berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data ners. Ners mungkin sedang digunakan.');
        }
    }
}
