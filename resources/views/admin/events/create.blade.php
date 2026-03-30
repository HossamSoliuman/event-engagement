@extends('layouts.admin')
@section('page-title', isset($event) ? 'Edit Event' : 'New Event')

@section('content')
<div style="max-width:700px;">
<form method="POST"
      action="{{ isset($event) ? route('admin.events.update', $event) : route('admin.events.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($event)) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><h3 style="font-size:15px;">Event Details</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Event Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $event->name ?? '') }}" required placeholder="Championship Night 2025">
                @error('name') <div style="color:#f87171; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $event->subtitle ?? '') }}" placeholder="The Ultimate Fan Experience">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Tell guests what this event is about...">{{ old('description', $event->description ?? '') }}</textarea>
            </div>

            @if(isset($event))
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" {{ $event->is_active ? 'checked' : '' }}>
                    Event is Active (guests can access it)
                </label>
            </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 style="font-size:15px;">Branding</h3></div>
        <div class="card-body">
            <div class="grid-3" style="margin-bottom:18px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Primary Colour</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="color" name="primary_color" value="{{ old('primary_color', $event->primary_color ?? '#FF3D00') }}" style="width:40px; height:36px; padding:2px; border-radius:6px; border:1px solid var(--eb-border); background:var(--eb-dark); cursor:pointer;">
                        <input type="text" name="primary_color_text" class="form-control" value="{{ old('primary_color', $event->primary_color ?? '#FF3D00') }}" style="font-family:monospace;" maxlength="7">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Background Colour</label>
                    <input type="color" name="secondary_color" value="{{ old('secondary_color', $event->secondary_color ?? '#0D0D1A') }}" style="width:40px; height:36px; padding:2px; border-radius:6px; border:1px solid var(--eb-border); background:var(--eb-dark); cursor:pointer;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Accent Colour</label>
                    <input type="color" name="accent_color" value="{{ old('accent_color', $event->accent_color ?? '#FFD700') }}" style="width:40px; height:36px; padding:2px; border-radius:6px; border:1px solid var(--eb-border); background:var(--eb-dark); cursor:pointer;">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Event Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if(isset($event) && $event->logo_path)
                        <img src="{{ Storage::disk('public')->url($event->logo_path) }}" style="height:40px; margin-top:8px; border-radius:4px;">
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">Sponsor Logo</label>
                    <input type="file" name="sponsor_logo" class="form-control" accept="image/*">
                    @if(isset($event) && $event->sponsor_logo_path)
                        <img src="{{ Storage::disk('public')->url($event->sponsor_logo_path) }}" style="height:40px; margin-top:8px; border-radius:4px;">
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($event))
    <div class="card mb-3">
        <div class="card-header"><h3 style="font-size:15px;">Module Labels</h3></div>
        <div class="card-body">
            <div class="grid-2">
                @foreach(['fotobomb' => 'Foto Bomb', 'lottery' => 'Lottery', 'voting' => 'Athlete Vote', 'membership' => 'Membership'] as $key => $default)
                <div class="form-group">
                    <label class="form-label">{{ ucfirst($key) }} Title</label>
                    <input type="text" name="{{ $key }}_title" class="form-control"
                           value="{{ old($key.'_title', $event->{$key.'_title'} ?? $default) }}">
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div style="display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary">
            {{ isset($event) ? '✓ Save Changes' : '+ Create Event' }}
        </button>
        <a href="{{ isset($event) ? route('admin.events.show', $event) : route('admin.events.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
</div>
@endsection