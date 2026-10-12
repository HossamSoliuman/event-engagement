{{-- Add / edit form for one survey question. Expects $action and $question (null when adding). --}}
@php
    $isEdit = $question !== null;
    $formKey = $isEdit ? 'edit-' . $question->id : 'add';
    $useOld = old('_form') === $formKey;
    $type = $useOld ? old('type') : ($isEdit ? $question->answerType() : \App\Models\SurveyQuestion::TYPE_CHOICE);
    $type = array_key_exists((string) $type, \App\Models\SurveyQuestion::ANSWER_TYPES) ? $type : \App\Models\SurveyQuestion::TYPE_CHOICE;
    $options = $useOld
        ? array_values(array_filter((array) old('options', []), fn ($option) => trim((string) $option) !== ''))
        : ($isEdit ? $question->optionList() : []);
    $options = array_pad($options, max(\App\Models\SurveyQuestion::MIN_OPTIONS, count($options)), '');
    $required = $useOld ? (bool) old('is_required') : ($isEdit ? $question->is_required : true);
    $maxOptions = \App\Models\SurveyQuestion::MAX_OPTIONS;
@endphp

<form method="POST" action="{{ $action }}" class="sv-form" data-type="{{ $type }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">

    @if ($useOld && $errors->any())
        <div class="alert alert-error">
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="sv-question-{{ $formKey }}">Question</label>
        <input type="text" id="sv-question-{{ $formKey }}" name="question" class="form-control sv-question-input"
            maxlength="255" required value="{{ $useOld ? old('question') : ($isEdit ? $question->question : '') }}"
            placeholder="e.g. How did you like today's event?">
    </div>

    <div class="form-group">
        <div class="form-label">Answer type</div>
        <div class="sv-types" role="radiogroup" aria-label="Answer type">
            @foreach (\App\Models\SurveyQuestion::ANSWER_TYPES as $value => $label)
                <label class="sv-type">
                    <input type="radio" name="type" value="{{ $value }}" @checked($type === $value) onchange="svTypeChanged(this)">
                    <span><i data-lucide="{{ $svTypeIcons[$value] }}" class="lucide-icon"></i> {{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="form-group sv-options">
        <div class="form-label">Answer options</div>
        <div class="sv-option-list">
            @foreach ($options as $option)
                @include('admin.survey._option-row', ['value' => $option, 'number' => $loop->iteration])
            @endforeach
        </div>
        <button type="button" class="btn btn-ghost btn-sm sv-add-option" onclick="svAddOption(this.form)?.focus()"
            @disabled(count($options) >= $maxOptions)>
            <i data-lucide="plus" class="lucide-icon"></i> Add option
        </button>
        <div class="form-hint">Press Enter for the next option, or paste a list with one option per line. Up to {{ $maxOptions }} options.</div>
    </div>

    <label class="sv-check">
        <input type="hidden" name="is_required" value="0">
        <input type="checkbox" name="is_required" value="1" @checked($required)> Fans must answer this question
    </label>

    <div class="sv-form-actions">
        <button type="submit" class="btn btn-primary {{ $isEdit ? 'btn-sm' : '' }}">
            @if ($isEdit)
                Save
            @else
                <i data-lucide="plus" class="lucide-icon"></i> Add Question
            @endif
        </button>
        @if ($isEdit)
            <button type="button" class="btn btn-ghost btn-sm" onclick="svToggleEdit({{ $question->id }})">Cancel</button>
        @endif
    </div>
</form>
