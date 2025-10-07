<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();


        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $user->photo = $user->photo ? asset('storage/' . $user->photo) : null;
        return response()->json([
            'user'  => $user,
            'token' => $token
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:mahasiswa,dosen',
            'nim_nidn' => 'required|string|unique:users,nim_nidn',
            'jurusan' => 'nullable|string',
            'prodi' => 'nullable|string',
            'photo' => 'nullable|string' // jika kamu ingin langsung kirim URL atau path
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'nim_nidn' => $request->nim_nidn,
            'jurusan' => $request->jurusan,
            'prodi' => $request->prodi,
            'photo' => $request->photo ?? null,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return ResponseFormatter::success([
            'user' => $user,
            'token' => $token
        ], 'Registrasi berhasil');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }
    public function me(Request $request)
    {
        $user = User::find($request->user()->id);

        $user->photo = $user->photo
            ? url('storage/' . $user->photo)
            : null;

        return ResponseFormatter::success($user, 'Detail profil user');
    }
}
