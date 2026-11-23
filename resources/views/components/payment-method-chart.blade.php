@props(['summary'])
<figure class="finup-analysis-payment-chart" aria-labelledby="payment-chart-caption">
        <figcaption id="payment-chart-caption">
            <span>Do maior gasto para o menor</span>
            <span>Valor gasto e participação nas despesas</span>
        </figcaption>
        @if (count($summary['rows']) > 0)
            <ol class="finup-analysis-payment-bars" aria-label="Formas de pagamento, do maior gasto para o menor">
                @foreach ($summary['rows'] as $row)
                    <li class="finup-analysis-payment-row {{ $row['cents'] < 0 ? 'finup-analysis-payment-row--credit' : '' }}" data-payment-method="{{ $row['method'] }}">
                        <div class="finup-analysis-payment-row-heading">
                            <span class="finup-analysis-payment-label">{{ $row['label'] }}</span>
                            <span class="finup-analysis-payment-amount">R$ {{ number_format((float) $row['amount'], 2, ',', '.') }}
                                <span class="finup-analysis-payment-percentage">@if ($row['percentage'] !== null){{ number_format($row['percentage'], 1, ',', '.') }}%@else Crédito líquido @endif</span>
                            </span>
                        </div>
                        <div class="finup-analysis-payment-track" aria-hidden="true">
                            <div class="finup-analysis-payment-bar" style="width: {{ number_format($row['width'], 4, '.', '') }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="finup-analysis-payment-note">Estornos e cashback são descontados da respectiva forma de pagamento.@if ($summary['has_credits']) Os percentuais consideram apenas as formas com despesa líquida positiva.@endif</p>
        @else
            <div class="finup-analysis-payment-empty" role="status">
                <svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M9 35h30M13 29v-8m11 8V12m11 17v-5" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                <h3>Nenhuma despesa encontrada</h3>
                <p>Selecione outro período ou ajuste os filtros para visualizar seus gastos.</p>
            </div>
        @endif
    </figure>
