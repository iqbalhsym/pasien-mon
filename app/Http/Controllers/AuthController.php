<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

class AuthController extends Controller
{
    public function showLogin()
    {
        $captcha = $this->generateCaptchaData();
        return view('auth.login', [
            'captcha_image' => $captcha['image']
        ]);
    }

    public function refreshCaptcha()
    {
        $captcha = $this->generateCaptchaData();
        return response()->json([
            'captcha_image' => $captcha['image']
        ]);
    }

    private function generateCaptchaData()
    {
        $num1 = rand(10, 50);
        $num2 = rand(1, 10);
        $operators = ['+', '-'];
        $operator = $operators[array_rand($operators)];

        if ($operator === '-') {
            if ($num1 < $num2) {
                $temp = $num1;
                $num1 = $num2;
                $num2 = $temp;
            }
            $answer = $num1 - $num2;
        } else {
            $answer = $num1 + $num2;
        }

        session(['captcha_answer' => $answer]);
        $captcha_question = "$num1 $operator $num2 =";

        // Create Image
        $width = 160;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $bg_color = imagecolorallocate($image, 248, 249, 250); // Light gray/blue
        $text_color = imagecolorallocate($image, 31, 59, 179); // Primary blue
        $noise_color = imagecolorallocate($image, 180, 180, 180);
        $line_color = imagecolorallocate($image, 219, 58, 232); // Purple line

        imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

        // Add Noise
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
        }

        // Add Line (like in example)
        imageline($image, 10, rand(30, 45), $width - 10, rand(5, 20), $line_color);
        imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);

        // Add Text
        // Using built-in font (1-5)
        $font_size = 5;
        $x = 20;
        $y = 15;
        imagestring($image, $font_size, $x, $y, $captcha_question, $text_color);

        // Capture Image
        ob_start();
        imagepng($image);
        $image_data = ob_get_clean();
        imagedestroy($image);

        return [
            'image' => 'data:image/png;base64,' . base64_encode($image_data)
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
            'captcha' => ['required', 'numeric'],
        ]);

        // Validasi Captcha
        if ($request->captcha != session('captcha_answer')) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['captcha' => 'Jawaban captcha salah. Silakan coba lagi.']);
        }

        $username = $request->username;
        $password = $request->password;
        $remember = $request->boolean('remember');
        $ldapSuccess = false;

        // ─── 1. Coba Autentikasi LDAP ────────────────────────────────────────
        try {
            // Tentukan field LDAP berdasarkan input (email atau sAMAccountName)
            $ldapField = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'mail' : 'samaccountname';

            // Lakukan Auth::attempt menggunakan LdapRecord provider
            if (Auth::attempt([
                $ldapField => $username,
                'password' => $password
            ], $remember)) {
                $ldapSuccess = true;
            }
        } catch (\Exception $e) {
            // Log jika terjadi error koneksi LDAP, lalu abaikan agar fallback ke lokal bekerja
            \Log::warning('LDAP Authentication failed with exception: ' . $e->getMessage());
        }

        if ($ldapSuccess) {
            // Cek jika username adalah mohammad.hud, otomatis jadikan admin (case-insensitive)
            $user = Auth::user();
            if ($user && strtolower($user->username) === 'mohammad.hud' && $user->role !== 'admin') {
                $user->role = 'admin';
                $user->save();
            }

            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // ─── 2. Fallback: Cek user LOKAL ─────────────────────────────────────
        // Mencocokkan input dengan kolom username atau email di basis data lokal
        $localUser = User::where(function($query) use ($username) {
            $query->where('username', $username)
                  ->orWhere('email', $username);
        })->first();

        if ($localUser) {
            // Pastikan user memiliki password lokal (untuk mencegah login kosong/LDAP disinkronisasi lewat fallback)
            if (!empty($localUser->password) && Hash::check($password, $localUser->password)) {
                Auth::login($localUser, $remember);

                // Cek jika username adalah mohammad.hud, otomatis jadikan admin (case-insensitive)
                if (strtolower($localUser->username) === 'mohammad.hud' && $localUser->role !== 'admin') {
                    $localUser->role = 'admin';
                    $localUser->save();
                }

                $request->session()->regenerate();
                return redirect()->intended('/');
            }
        }

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Username atau Password salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}