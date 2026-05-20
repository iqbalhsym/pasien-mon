<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Menampilkan daftar user
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('users.index', compact('users'));
    }

    // Update role user (oleh Admin) — akses penuh
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,user,viewer',
        ]);

        $user = User::findOrFail($id);

        // Mencegah admin mengubah role dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Role {$user->name} berhasil diubah menjadi {$user->role}.");
    }

    // Promote viewer → editor (oleh Editor) — akses terbatas
    public function editorPromote(Request $request, $id)
    {
        // Pastikan yang menjalankan adalah editor
        if (Auth::user()->role !== 'user') {
            abort(403);
        }

        $user = User::findOrFail($id);

        // Tidak boleh mengubah diri sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        // Tidak boleh mengubah akun admin atau sesama editor
        if ($user->role !== 'viewer') {
            return back()->with('error', 'Anda hanya dapat mengubah akun View Only menjadi Editor.');
        }

        $user->role = 'user';
        $user->save();

        return back()->with('success', "{$user->name} berhasil dijadikan Editor.");
    }

    // Menghapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}