@extends('shared.layout')

@section('customStyles')
    {{-- Styles moved to /assets/css/core/ui.css --}}
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1">Anhänger</h1>
            <p class="text-body-secondary mb-0">
                Standard: alle Anhänger. Mit Datumsfilter: nur verfügbare oder pending Anhänger.
            </p>
        </div>

        <a href="{{ route('reservations.create') }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i> Neue Reservierung
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mt-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="card ui-card ui-card-pad mt-3">
        <form method="GET" action="{{ route('trailers.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Von</label>
                <input type="date" name="from" value="{{ $filterFrom ?? '' }}" class="form-control" />
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Bis einschließlich</label>
                <input type="date" name="to" value="{{ $filterTo ?? '' }}" class="form-control" />
            </div>
            <div class="col-12 col-md-4">
                <div class="d-flex gap-2 mt-2 justify-content-md-end">
                    <button class="btn btn-outline-primary rounded-pill px-4" type="submit">
                        <i class="bi bi-funnel me-2"></i> Filter
                    </button>
                    <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ route('trailers.index') }}">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-4 mt-1">
        @foreach ($cards as $card)
            @php
                $trailer = $card['trailer'];
                $reservation = $card['reservation'];
                $status = $card['status'];
            @endphp

            <div class="col-12 col-md-6 col-lg-4">
                <div class="card ui-card ui-card--interactive ui-trailer-card">
                    <div class="card-body">
                        <div class="ui-trailer-top">
                            <div>
                                <div class="ui-trailer-code">{{ $trailer->code }}</div>
                                <div class="ui-trailer-name">{{ $trailer->name }}</div>
                            </div>

                            @if ($status === 'PENDING')
                                <span class="ui-badge ui-badge--warning"><i class="bi bi-telephone"></i> Pending</span>
                            @elseif ($status === 'OCCUPIED')
                                <span class="ui-badge ui-badge--danger"><i class="bi bi-lock"></i> Belegt</span>
                            @else
                                <span class="ui-badge ui-badge--success"><i class="bi bi-check2-circle"></i> Verfügbar</span>
                            @endif
                        </div>

                        <hr class="ui-divider my-0" />

                        @if ($status !== 'AVAILABLE' && $reservation)
                            <div>
                                <div class="ui-trailer-meta">Periode</div>
                                <div class="fw-semibold">
                                    {{ optional($reservation->start_date)->format('d-m-Y') }} – {{ optional($reservation->end_date)->format('d-m-Y') }}
                                </div>
                            </div>

                            <div>
                                <div class="ui-trailer-meta">Kunde</div>
                                <div class="fw-semibold">{{ $reservation->customer_first_name }} {{ $reservation->customer_last_name }}</div>
                            </div>
                        @else
                            <div>
                                <div class="ui-trailer-meta">Status</div>
                                <div class="fw-semibold">Frei im ausgewählten Zeitraum</div>
                            </div>
                        @endif

                        <div class="ui-trailer-spacer"></div>

                        <a href="{{ route('reservations.create', ['trailer_id' => $trailer->id]) }}" class="btn btn-outline-primary">
                            Reservieren
                        </a>
                    </div>
                </div>
            </div>
        @endforeach

        @if(($cards ?? collect())->isEmpty())
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm rounded-4 mt-3">
                    Keine Anhänger für diesen Filter gefunden.
                </div>
            </div>
        @endif
    </div>
@endsection
