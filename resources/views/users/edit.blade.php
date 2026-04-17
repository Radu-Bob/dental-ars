<form action="{{ route('users.update', $user) }}" method="POST">
    @csrf

    @if(Auth::user()->role === 'admin' && Auth::id() !== $user->id)
        <h3>Editing User: {{ $user->name }}</h3>
    @else
        <h3>My Profile</h3>
    @endif

    <div>
        <label for="name">Name:</label>
        <input type="text" name="name" value="{{ $user->name }}">
        @error('name')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" value="{{ $user->email }}">
        @error('email')
            <span class="text-danger">{{ $message }}</span> 
        @enderror
    </div>

    <div>
        <label for="current_role">Status:</label>
        <input type="text" id="current_role" maxlength="10" value="{{ ucfirst($user->role) }}" disabled>
    </div>
    {{-- This section is only visible if the logged-in user is an admin --}}
    @if(Auth::user()->role === 'admin')
    <div>
        <label for="role">Change User Role:</label>
        <select name="role">
            <option value="assistant" {{ $user->role === 'assistant' ? 'selected' : '' }}>Assistant</option>
            <option value="doctor" {{ $user->role === 'doctor' ? 'selected' : '' }}>Doctor</option>
            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrator</option>
        </select>
        @error('role')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    @endif

    <h3>Change Password</h3>
    <div>
        <label for="password">New Password:</label>
        <input type="password" name="password">
        @error('password')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label for="password_confirmation">Confirm Password:</label>
        <input type="password" name="password_confirmation">
    </div>
    <button type="submit">Update Details</button>
</form>