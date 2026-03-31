@extends('layouts.admin')
@section('title', isset($user) ? 'Edit '.$user->name : 'New User')
@section('page-title', isset($user) ? 'Edit User' : 'New User')

@section('content')
<div style="max-width:500px">
<form method="POST" action="{{ isset($user) ? route('admin.users.update',$user) : route('admin.users.store') }}">
    @csrf @if(isset($user)) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><h3>User Details</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input name="name" class="form-control" value="{{ old('name',$user->name??'') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email',$user->email??'') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" class="form-control">
                    @foreach(['moderator'=>'Moderator','admin'=>'Admin','superadmin'=>'Super Admin'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('role',$user->role??'admin')===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
                <input type="password" name="password" class="form-control" {{ isset($user)?'':'required' }} minlength="8" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password">
            </div>
            @if(isset($user))
            <label class="form-check">
                <input type="checkbox" name="is_active" {{ $user->is_active?'checked':'' }}>
                Account is active
            </label>
            
            @endif
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary">{{ isset($user)?'Save Changes':'Create User' }}</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
</div>
@endsection
