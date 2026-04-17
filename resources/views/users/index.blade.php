<h1>User Management</h1>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->role }}</td>
            <td>
                <a href="{{ route('users.edit', ['user' => $user->id]) }}">Edit</a>
                {{-- You could add a delete button here as well --}}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
