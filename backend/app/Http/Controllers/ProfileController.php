<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $socialAccounts = $user->socialAccounts;

        return view('profile.show', compact('user', 'socialAccounts'));
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();

        // Unique制約を回避するために、メールアドレスを書き換えてから論理削除する
        $oldEmail = $user->email;
        $user->email = 'deleted_' . now()->timestamp . '_' . $oldEmail;
        $user->save();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', '退会手続きが完了しました。ご利用ありがとうございました。');
    }
}
