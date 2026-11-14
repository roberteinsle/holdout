<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-lg bg-amber-500 px-4 py-3 text-base font-semibold text-stone-950 hover:bg-amber-400 focus:ring-2 focus:ring-amber-500/50 focus:outline-none disabled:opacity-50']) }}>
    {{ $slot }}
</button>
