{{-- Add / edit form for one survey question. Expects $action and an optional $question. --}}
@php
    $isEdit = isset($question);
    $useOld = !$isEdit && old('_form') === 'add';
    $type = $isEdit ? $question->type : ($useOld ? old('type') : \App\Models\SurveyQuestion::TYPE_CHOICE);
    $options = $isEdit ? $question->optionList() : ($useOld ? array_values(array_filter((array) old('options', []))) : []);
    $options = array_pad($options, max(3, count($options)), '');
    $required = $isEdit ? $question->is_required : (!$useOld || (bool) old('is_required'));
    $maxOptions = \App\Models\SurveyQuestion::MAX_OPTIONS;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @else
        <input type="hidden" name="_form" value="add">
    @endif

    <div class="form-group">
        <label class="form-label">Question *</label>
        <input type="text" name="question" class="form-control" maxlength="255" required
            value="{{ $isEdit ? $question->question : ($useOld ? old('question') : '') }}"
            placeholder="e.g. How did you like today's event?">
    </div>

    <div class="form-group">
        <label class="form-label">Answer type</label>
        <select name="type" class="form-control" onchange="svTypeChanged(this)">
            <option value="choice" @selected($type === 'choice')>Multiple choice — fans pick one option</option>
            <option value="text" @selected($type === 'text')>Free text — fans type their own answer</option>
        </select>
    </div>

    <div class="form-group sv-options" style="{{ $type === 'choice' ? '' : 'display:none' }}">
        <label class="form-label">Answer options</label>
        <div class="sv-option-list" style="display:flex;flex-direction:column;gap:8px">
            @foreach ($options as $option)
                <input type="text" name="options[]" class="form-control" maxlength="120" value="{{ $option }}"
                    placeholder="Option {{ $loop->iteration }}">
            @endforeach
        </div>
        <button type="button" class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="svAddOption(this)"
            {{ count($options) >= $maxOptions ? 'disabled' : '' }}>
            <i data-lucide="plus" class="lucide-icon"></i> Add option
        </button>
        <div class="form-hint">Between 2 and {{ $maxOptions }} options. Empty boxes are ignored.</div>
    </div>

    <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
            <input type="hidden" name="is_required" value="0">
            <input type="checkbox" name="is_required" value="1" @checked($required)> Fans must answer this question
        </label>
    </div>

    <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary {{ $isEdit ? 'btn-sm' : '' }}">{{ $isEdit ? 'Save' : 'Add Question' }}</button>
        @if ($isEdit)
            <button type="button" class="btn btn-ghost btn-sm" onclick="svToggleEdit({{ $question->id }})">Cancel</button>
        @endif
    </div>
</form>
