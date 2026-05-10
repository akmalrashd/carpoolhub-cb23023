@extends('layouts.app')

@section('content')
    <h1 style="margin:0 0 14px; font-family:Poppins, sans-serif;">Users Admin</h1>

    @if($errors->any())
        <div style="margin-bottom:12px; padding:10px 12px; border-radius:8px; border:1px solid #7f1d1d; background:rgba(239,68,68,0.12); color:#fecaca;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card-dark" style="padding:16px; margin-bottom:14px;">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display:grid; gap:10px; grid-template-columns: 1fr 180px 180px auto; align-items:end;">
            <div>
                <label for="q">Search</label>
                <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Name / Email / Phone">
            </div>
            <div>
                <label for="role">Peranan</label>
                <select id="role" name="role">
                    <option value="">All</option>
                    @foreach(['admin', 'driver', 'passenger'] as $role)
                        <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="active">Status</label>
                <select id="active" name="active">
                    <option value="">All</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" style="background:#FACC15; color:#111827; border:none; border-radius:8px; padding:10px 12px; font-weight:700;">Tapis</button>
        </form>
    </div>

    <div class="card-dark" style="padding:16px;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Users</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Email</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Phone</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Peranan</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid #374151;">Status</th>
                    <th style="text-align:right; padding:10px; border-bottom:1px solid #374151;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="padding:10px; border-bottom:1px solid #374151;">
                            <strong>{{ $user->name }}</strong><br>
                            <span style="color:#9CA3AF; font-size:12px;">#{{ $user->id }}</span>
                        </td>
                        <td style="padding:10px; border-bottom:1px solid #374151;">{{ $user->email }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151;">{{ $user->phone ?: '-' }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151;">{{ ucfirst($user->role) }}</td>
                        <td style="padding:10px; border-bottom:1px solid #374151;">
                            <span style="color:{{ $user->is_active ? '#22C55E' : '#EF4444' }};">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="padding:10px; border-bottom:1px solid #374151; text-align:right;">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display:inline-flex; gap:6px; align-items:center;">
                                @csrf
                                @method('PATCH')
                                <select name="role" style="width:120px; padding:6px 8px;">
                                    @foreach(['admin', 'driver', 'passenger'] as $role)
                                        <option value="{{ $role }}" {{ $user->role === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                                <select name="is_active" style="width:110px; padding:6px 8px;">
                                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ! $user->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit" style="background:#FACC15; color:#111827; border:none; border-radius:8px; padding:6px 10px; font-weight:700;">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:10px; color:#9CA3AF;">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $users->links() }}</div>
    </div>
@endsection
