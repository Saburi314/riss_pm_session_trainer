<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            \Log::info("Social User info from {$provider}:", [
                'id' => $socialUser->getId(),
                'email' => $socialUser->getEmail(),
                'name' => $socialUser->getName(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Social Auth Failed for {$provider}: " . get_class($e) . " - " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->route('login')->withErrors(['email' => '認証に失敗しました。詳細：' . get_class($e) . ': ' . $e->getMessage()]);
        }

        $account = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($account) {
            $user = $account->user;
            \Log::info("Existing social account found for user ID {$user->id}");
        } else {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                \Log::info("Linking existing user by email to {$provider}: ID {$user->id}");
            } else {
                \Log::info("Creating new user for {$provider}");
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'External User',
                    'email' => $socialUser->getEmail(),
                    'password' => null,
                    'email_verified_at' => now(),
                    'role' => 'user',
                ]);
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $user->update(['last_login_at' => now()]);

        Auth::login($user, true);

        return redirect()->route('exercise.index');
    }
}
