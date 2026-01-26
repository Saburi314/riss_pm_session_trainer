<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'メールアドレスが認証されていません。送信されたメールを確認するか、下記から再送してください。',
                ])->with('show_resend', true)->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended('exercise');
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'お名前を入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワードが一致しません。',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // デフォルトは一般ユーザー
        ]);

        $user->sendEmailVerificationNotification();

        $user->update(['last_login_at' => now()]);

        Auth::login($user);

        return redirect()->route('exercise.index');
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', '既に認証済みです。ログインしてください。');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return redirect()->route('login')->with('status', 'メールアドレスが認証されました！学習を開始しましょう。');
    }

    public function showVerifyNotice()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('exercise.index');
        }

        return view('auth.verify-email');
    }

    public function showResendForm()
    {
        return view('auth.verify-resend');
    }

    public function resendVerificationEmail(Request $request)
    {
        $email = $request->input('email');

        if (!$email && Auth::check()) {
            $email = Auth::user()->email;
        }

        if (!$email) {
            $request->validate(['email' => 'required|email']);
        }

        $user = User::where('email', $email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // ユーザーが存在するかどうかに関わらず、同じメッセージを出す（セキュリティのため）
        return back()->with('status', '入力されたメールアドレスが未認証の場合、認証メールを再送しました。');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
