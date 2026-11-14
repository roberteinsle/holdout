<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-lg bg-primary px-4 py-3 text-base font-semibold text-on-primary hover:bg-primary-hover focus:ring-2 focus:ring-primary/50 focus:outline-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
