@props(['label' => null, 'name' => '', 'type' => 'text', 'value' => null])
<div class="space-y-1">
    @if($label)<label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>@endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => 'input w-full']) }}>
    @error($name)<p class="text-xs text-rose-600" role="alert">{{ $message }}</p>@enderror
</div>
