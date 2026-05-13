@props(['cents' => 0])

<span {{ $attributes }}>
    RM {{ number_format($cents / 100, 2) }}
</span>
