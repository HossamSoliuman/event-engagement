@extends('layouts.admin')
@section('page-title', 'Edit Event')

@section('content')
    <div style="max-width:700px;">
        <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @if ($errors->any())
                <div class="card mb-3">
                    <div class="card-body" style="background:#2a1212;border:1px solid #7f1d1d;border-radius:6px;">
                        <ul style="margin:0;padding-left:18px;color:#fca5a5;font-size:13px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            <div class="card mb-3">
                <div class="card-header">
                    <h3 style="font-size:15px;">Event Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Event Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $event->name) }}"
                            required>
                        @error('name')
                            <div style="color:#f87171; font-size:12px; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control"
                            value="{{ old('subtitle', $event->subtitle) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $event->description) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old('is_active', $event->is_active) ? 'checked' : '' }}>
                            Event is Active (guests can access it)
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 style="font-size:15px;">Branding</h3>
                </div>
                <div class="card-body">
                    <div class="grid-3" style="margin-bottom:18px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Primary Colour</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="color" name="primary_color"
                                    value="{{ old('primary_color', $event->primary_color) }}"
                                    style="width:40px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--eb-border);background:var(--eb-dark);cursor:pointer;">
                                <input type="text" name="primary_color_text" class="form-control"
                                    value="{{ old('primary_color', $event->primary_color) }}" style="font-family:monospace;"
                                    maxlength="7">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Background Colour</label>
                            <input type="color" name="secondary_color"
                                value="{{ old('secondary_color', $event->secondary_color) }}"
                                style="width:40px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--eb-border);background:var(--eb-dark);cursor:pointer;">
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Accent Colour</label>
                            <input type="color" name="accent_color"
                                value="{{ old('accent_color', $event->accent_color) }}"
                                style="width:40px;height:36px;padding:2px;border-radius:6px;border:1px solid var(--eb-border);background:var(--eb-dark);cursor:pointer;">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Event Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            @if ($event->logo_path)
                                <img src="{{ Storage::disk('public')->url($event->logo_path) }}"
                                    style="height:40px;margin-top:8px;border-radius:4px;">
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sponsor Logo</label>
                            <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                            @if ($event->sponsor_logo_path)
                                <img src="{{ Storage::disk('public')->url($event->sponsor_logo_path) }}"
                                    style="height:40px;margin-top:8px;border-radius:4px;">
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 style="font-size:15px;">Module Labels</h3>
                </div>
                <div class="card-body">
                    <div class="grid-2">
                        @foreach (['fotobomb' => 'Foto Bomb', 'lottery' => 'Lottery', 'voting' => 'Athlete Vote', 'membership' => 'Membership'] as $key => $default)
                            <div class="form-group">
                                <label class="form-label">{{ ucfirst($key) }} Title</label>
                                <input type="text" name="{{ $key }}_title" class="form-control"
                                    value="{{ old($key . '_title', $event->{$key . '_title'} ?? $default) }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                <a href="{{ route('admin.events.show', $event) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
