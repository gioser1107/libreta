<p class="lb-note">
    @if($preview)
        Equivale a {{ \App\Support\Money::format($preview['usd'], 'USD') }}
        y {{ \App\Support\Money::format($preview['ves'], 'VES') }}.
        Tasa BCV de esa fecha: {{ $preview['rate'] }} Bs{{ $currency === 'EUR' ? ' por euro' : ' por dólar' }}.
    @else
        El equivalente se calcula con la tasa BCV del día del movimiento.
    @endif
</p>
