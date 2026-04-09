@extends('shared.layout')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="fw-bold mb-1">Reservierung #{{ $reservation->id }}</h2>
                        <div class="text-body-secondary">{{ $reservation->trailer->code ?? '-' }} - {{ $reservation->trailer->name ?? '-' }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('reservations.edit', $reservation->id) }}" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-pencil-square me-2"></i> Bearbeiten
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">Zurück</a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card ui-card ui-card-pad">
                    @php
                        $endInclusive = optional($reservation->end_date);
                    @endphp

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if($reservation->status === 'pending')
                            <span class="ui-badge ui-badge--warning">Pending</span>
                        @else
                            <span class="ui-badge ui-badge--success">Bestätigt</span>
                        @endif

                        @if($reservation->payment_status === 'paid')
                            <span class="ui-badge ui-badge--success">Bezahlt</span>
                        @elseif($reservation->payment_status === 'partial')
                            <span class="ui-badge ui-badge--warning">Teilweise bezahlt</span>
                        @else
                            <span class="ui-badge ui-badge--neutral">Offen</span>
                        @endif
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Kundennummer</dt>
                        <dd class="col-sm-8">{{ $reservation->customer_number ?? '-' }}</dd>

                        <dt class="col-sm-4">Firma</dt>
                        <dd class="col-sm-8">{{ $reservation->company_name ?: '-' }}</dd>

                        <dt class="col-sm-4">Kunde</dt>
                        <dd class="col-sm-8">{{ $reservation->customer_first_name }} {{ $reservation->customer_last_name }}</dd>

                        <dt class="col-sm-4">E-Mail</dt>
                        <dd class="col-sm-8">{{ $reservation->customer_email }}</dd>

                        <dt class="col-sm-4">Telefon</dt>
                        <dd class="col-sm-8">{{ $reservation->customer_phone }}</dd>

                        <dt class="col-sm-4">Von</dt>
                        <dd class="col-sm-8">{{ optional($reservation->start_date)->format('d-m-Y') }}</dd>

                        <dt class="col-sm-4">Bis</dt>
                        <dd class="col-sm-8">{{ optional($endInclusive)->format('d-m-Y') }}</dd>

                        <dt class="col-sm-4">Zusatzleistungen</dt>
                        <dd class="col-sm-8">
                            @php
                                $services = [];
                                if ($reservation->service_selber_beladen) $services[] = 'Selber beladen (+0€)';
                                if ($reservation->service_lehr) $services[] = 'Lehr (+25€)';
                                if ($reservation->service_paket) $services[] = 'Servicepaket (+35€)';
                            @endphp
                            {{ $services ? implode(' · ', $services) : '-' }}
                        </dd>

                        <dt class="col-sm-4">Erstellt von</dt>
                        <dd class="col-sm-8">{{ $reservation->user->name ?? '-' }}</dd>

                        <dt class="col-sm-4">Zahlung</dt>
                        <dd class="col-sm-8">
                            @if($reservation->payment_status === 'paid')
                                Bezahlt
                            @elseif($reservation->payment_status === 'partial')
                                Teilweise bezahlt ({{ number_format((float) ($reservation->partial_paid_amount ?? 0), 2, ',', '.') }} €)
                            @else
                                Offen
                            @endif
                        </dd>

                        @if($reservation->notes)
                            <dt class="col-sm-4">Notizen</dt>
                            <dd class="col-sm-8">{{ $reservation->notes }}</dd>
                        @endif
                    </dl>

                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('reservations.destroy', $reservation->id) }}" onsubmit="return confirm('Möchtest du diese Reservierung wirklich löschen?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger rounded-pill px-4" type="submit">
                                <i class="bi bi-trash"></i> Löschen
                            </button>
                        </form>

                        <form method="POST" action="{{ route('dashboard.reservation.destroy', $reservation->id) }}" onsubmit="return confirm('Möchtest du diese Reservierung wirklich stornieren?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger rounded-pill px-4" type="submit">
                                <i class="bi bi-x-circle"></i> Stornieren
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

