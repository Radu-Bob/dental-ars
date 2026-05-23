<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;

class AuthController extends Controller
{
    protected $redirectTo = '/dashboard';
    
    public function loginForm()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        AuditLog::create([
            'user_id'         => Auth::id(),
            'user_name'       => Auth::user()->name,
            'action'          => 'login',
            'action_category' => 'auth',
            'model_type'      => 'User',
            'model_id'        => (string) Auth::id(),
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'context'         => 'Login: ' . Auth::user()->name . ' (' . Auth::user()->email . ') from ' . $request->ip(),
        ]);

        return redirect()->route('dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ]);
}

    public function logout(Request $request)
    {
        AuditLog::create([
            'user_id'         => Auth::id(),
            'user_name'       => Auth::user()?->name,
            'action'          => 'logout',
            'action_category' => 'auth',
            'model_type'      => 'User',
            'model_id'        => (string) Auth::id(),
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'context'         => 'Logout: ' . Auth::user()?->name,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Check if the current password is correct
        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match your current password.']);
        }

        // Hash and update the new password
        Auth::user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('status', 'Password changed successfully!');
    }
}