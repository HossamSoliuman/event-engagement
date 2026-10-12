{{-- One answer option input in the question form. Expects $value and $number. --}}
<div class="sv-option">
    <span class="sv-mark" aria-hidden="true"></span>
    <input type="text" name="options[]" class="form-control" maxlength="120" value="{{ $value }}"
        placeholder="Option {{ $number }}" aria-label="Option {{ $number }}">
    <button type="button" class="sv-option-remove" title="Remove option" aria-label="Remove option" tabindex="-1"
        onclick="svRemoveOption(this)">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
    </button>
</div>
