<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // This single method handles both a user editing their own profile
    // and an admin editing another user's profile.
    public function edit(User $user = null)
    {
        // If no user is passed in, assume it's the logged-in user's profile
        if (is_null($user)) {
            $user = Auth::user();
        }

        // Only admins can edit other users
        if (Auth::user()->role !== 'admin' && Auth::id() !== $user->id) {
            return redirect()->route('patients.index')->with('error', 'You do not have permission to do that.');
        }

        return view('users.edit', compact('user'));
    }

    // This single method handles the update for both cases
    public function update(Request $request, User $user)
    {
        // 1. Initial Access Check (Sound)
        if (Auth::user()->role !== 'admin' && Auth::id() !== $user->id) {
            return redirect()->route('patients.index')->with('error', 'You do not have permission to update this user.');
        }

        // 2. Define Base Validation Rules (with the CRUCIAL unique rule)
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // CRITICAL: Ensure email is unique, ignoring the current user's ID
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ];

        // 3. Admin-Only Role Validation Logic
        if (Auth::user()->role === 'admin') {
            $rules['role'] = [
                'required',
                // CRITICAL: Ensure submitted role is one of your database roles
                Rule::in(['assistant', 'doctor', 'admin']), 
            ];
        }
        
        // 4. Run the Validation
        $validated = $request->validate($rules);

        // 5. Update the User Model
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->filled('password')) {
            $user->password = $validated['password'];
        }

        // 6. Admin-Only Role Update Logic
        if (Auth::user()->role === 'admin' && isset($validated['role'])) {
            $user->role = $validated['role'];
        }
        // Note: If the user is a doctor/assistant, they cannot change their own role.

        $user->save();

        return back()->with('status', 'Profile updated successfully!');
    }
    //
    public function index()
    {
        // Make sure only an admin can see this page
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('patients.index')->with('error', 'Unauthorized access.');
        }

        $users = User::all();
        return view('users.index', compact('users'));
    }
}