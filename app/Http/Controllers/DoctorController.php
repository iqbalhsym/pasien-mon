<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::orderBy('name', 'asc')->get();
        return view('doctors.index', compact('doctors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:doctors,name',
            'ksm'  => 'required|string|max:255',
        ]);

        Doctor::create([
            'name' => trim($request->name),
            'ksm'  => trim($request->ksm)
        ]);

        return redirect()->back()->with('success', 'Dokter baru berhasil ditambahkan.');
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:doctors,name,' . $doctor->id,
            'ksm'  => 'required|string|max:255',
        ]);

        $doctor->update([
            'name' => trim($request->name),
            'ksm'  => trim($request->ksm)
        ]);

        return redirect()->back()->with('success', 'Data dokter berhasil diperbarui.');
    }

    public function destroy(Doctor $doctor)
    {
        try {
            $doctor->delete();
            return redirect()->back()->with('success', 'Data dokter berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data dokter. Dokter mungkin sedang digunakan.');
        }
    }
}
