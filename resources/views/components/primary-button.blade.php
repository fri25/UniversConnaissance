<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-solid']) }}>
    {{ $slot }}
</button>
