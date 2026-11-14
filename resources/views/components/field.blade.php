@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])

<label class="block">
    <span class="mb-1 block text-sm font-medium">{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-line bg-page px-3 py-3 text-base text-fg focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none']) }}
    >
    @error($name)
        <span class="mt-1 block text-sm text-danger">{{ $message }}</span>
    @enderror
</label>
