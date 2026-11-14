@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])

<label class="block">
    <span class="mb-1 block text-sm font-medium">{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-stone-300 px-3 py-3 text-base focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30 focus:outline-none']) }}
    >
    @error($name)
        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
    @enderror
</label>
