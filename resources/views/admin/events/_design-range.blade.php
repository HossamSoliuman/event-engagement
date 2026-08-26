{{-- A single labelled slider that drives one CSS variable in the live phone preview. --}}
@php
    $rSuffix = $suffix ?? '';
    $rValue = old($name, $value);
@endphp
<div class="ld-ctl">
    <label class="ld-ctl-label" for="{{ $name }}">
        <span>{{ $label }}</span>
        <output id="out_{{ $name }}">{{ 0 + $rValue }}{{ $rSuffix }}</output>
    </label>
    <input type="range" class="ld-range" id="{{ $name }}" name="{{ $name }}" value="{{ $rValue }}"
        min="{{ $min }}" max="{{ $max }}" step="{{ $step ?? 1 }}" data-dvar="{{ $var ?? '' }}"
        data-dunit="{{ $unit ?? '' }}" data-dscale="{{ $scale ?? 1 }}" data-dscope="{{ $scope ?? 'root' }}"
        data-dsuffix="{{ $rSuffix }}">
    @isset($hint)
        <div class="ld-hint">{{ $hint }}</div>
    @endisset
</div>
