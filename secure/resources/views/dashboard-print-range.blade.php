@extends('shared.layout')

@section('customStyles')
    <style>
        @media print {
            body { background: #fff !important; color: #111 !important; }
            .no-print { display: none !important; }
            main.container { margin-top: 0 !important; max-width: none; }
        }

        /* Screen defaults (light) */
        body { background: #fff; }
        .print-page { font-size: 13.5px; line-height: 1.35; color: #111; }
        .print-header { border-bottom: 1px solid rgba(0,0,0,.12); padding-bottom: 10px; margin-bottom: 14px; }
        .booking { border: 1px solid rgba(0,0,0,.12); border-radius: 12px; padding: 14px; margin-bottom: 12px; break-inside: avoid; page-break-inside: avoid; background: #fff; }
        .booking h3 { font-size: 15px; margin: 0 0 10px 0; display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
        .booking h3 .muted { font-size: 12px; font-weight: 500; color: rgba(0,0,0,.6); }
        .meta { display: grid; grid-template-columns: 140px 1fr; gap: 6px 12px; }
        .label { color: rgba(0,0,0,.55); font-weight: 600; }
        .value { color: rgba(0,0,0,.92); }

        /* Dark theme only when UI theme is dark */
        [data-bs-theme="dark"] body { background: #0f0f10; }
        [data-bs-theme="dark"] .print-page { color: #fff; }
        [data-bs-theme="dark"] .print-header { border-bottom-color: rgba(255,255,255,.16); }
        [data-bs-theme="dark"] .booking { border-color: rgba(255,255,255,.16); background: rgba(255,255,255,.04); }
        [data-bs-theme="dark"] .label { color: rgba(255,255,255,.7); }
        [data-bs-theme="dark"] .value { color: rgba(255,255,255,.92); }
        [data-bs-theme="dark"] .booking h3 .muted { color: rgba(255,255,255,.65); }
    </style>
@endsection

@section('content')
    <div class="print-page">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 no-print print-header">
            <div>
                <h1 class="fw-bold mb-1">Druckübersicht</h1>
                <div class="text-body-secondary">
                    {{ $from->format('d-m-Y') }} – {{ $toInclusive->format('d-m-Y') }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary rounded-pill px-4" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Drucken
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">Zurück</a>
            </div>
        </div>

        @php $i = 1; @endphp

        @forelse($reservations as $reservation)
            @php
                $endInclusive = optional($reservation->end_date);
                $services = [];
                if ($reservation->service_selber_beladen) $services[] = 'Selber beladen (+0€)';
                if ($reservation->service_lehr) $services[] = 'Lehr (+25€)';
                if ($reservation->service_paket) $services[] = 'Servicepaket (+35€)';
            @endphp

            <section class="booking">
                <h3>
                    <span>Buchung {{ $i++ }}</span>
                    <span class="muted">{{ optional($reservation->start_date)->format('d-m-Y') }} – {{ optional($endInclusive)->format('d-m-Y') }}</span>
                </h3>
                <div class="meta">
                    <div class="label">Anhänger</div>
                    <div class="value">{{ $reservation->trailer->code ?? '-' }} - {{ $reservation->trailer->name ?? '-' }}</div>

                    <div class="label">Status</div>
                    <div class="value">
                        {{ $reservation->status === 'pending' ? 'Pending' : 'Bestätigt' }}
                        /
                        @if($reservation->payment_status === 'paid')
                            Bezahlt
                        @elseif($reservation->payment_status === 'partial')
                            Teilweise bezahlt ({{ number_format((float) ($reservation->partial_paid_amount ?? 0), 2, ',', '.') }} €)
                        @else
                            Offen
                        @endif
                    </div>

                    <div class="label">Kundennr.</div>
                    <div class="value">{{ $reservation->customer_number ?? '-' }}</div>

                    <div class="label">Firma</div>
                    <div class="value">{{ $reservation->company_name ?: '-' }}</div>

                    <div class="label">Kunde</div>
                    <div class="value">{{ $reservation->customer_first_name }} {{ $reservation->customer_last_name }}</div>

                    <div class="label">Telefon</div>
                    <div class="value">{{ $reservation->customer_phone }}</div>

                    <div class="label">E-Mail</div>
                    <div class="value">{{ $reservation->customer_email }}</div>

                    <div class="label">Zusatzleistungen</div>
                    <div class="value">{{ $services ? implode(' · ', $services) : '-' }}</div>

                    @if($reservation->notes)
                        <div class="label">Notizen</div>
                        <div class="value">{{ $reservation->notes }}</div>
                    @endif
                </div>
            </section>
        @empty
            <div class="alert alert-info border-0 shadow-sm rounded-4">
                Keine Reservierungen im ausgewählten Zeitraum.
            </div>
        @endforelse
    </div>
@endsection
