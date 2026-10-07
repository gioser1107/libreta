<button {{ $attributes->merge(['type' => 'submit', 'class' => 'lb-btn lb-btn-danger-solid']) }}>
    {{ $slot }}
</button>
