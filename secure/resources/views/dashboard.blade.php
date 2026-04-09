@extends('shared.layout')

@section('customStyles')
    {{-- Styles moved to /assets/css/core/ui.css --}}
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1">Übersicht</h1>
            <p class="text-body-secondary mb-0">Aktive und kommende Reservierungen.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reservations.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-circle me-2"></i> Neue Reservierung
            </a>

            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-printer me-2"></i> <span class="d-inline-block" style="padding-bottom: 2px;">Drucken</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard.print.next_week') }}" target="_blank">
                            Nächste Woche (Mo–So)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard.print.range', ['days' => 7]) }}" target="_blank">
                            Ab heute + 7 Tage
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="px-3 py-2" style="min-width: 320px;">
                        <div class="small fw-semibold mb-2">Eigener Zeitraum</div>
                        <form method="GET" action="{{ route('dashboard.print.range') }}" target="_blank" class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">Von</label>
                                <input type="date" class="form-control form-control-sm" name="from" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Bis</label>
                                <input type="date" class="form-control form-control-sm" name="to" required>
                            </div>
                            <div class="col-12 d-grid mt-1">
                                <button class="btn btn-primary btn-sm" type="submit">Drucken</button>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>

            @can('admin')
                <a href="{{ route('users.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-people me-2"></i> Benutzer
                </a>
                <a href="{{ route('register.form') }}" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-person-plus me-2"></i> Benutzer hinzufügen
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-0">Reservierungen</h5>
                            <div class="text-body-secondary small">Klicke auf eine Reservierung für Details, Bearbeiten und Löschen.</div>
                        </div>

                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="show_all"
                                   onchange="window.location = this.checked ? '{{ route('dashboard', ['show_all' => 1]) }}' : '{{ route('dashboard') }}'"
                                   @checked($showAll ?? false)>
                            <label class="form-check-label small" for="show_all">
                                Auch vergangene anzeigen
                            </label>

                            @if(($showAll ?? false) === true)
                                <a class="small ms-2 text-decoration-none" href="{{ route('dashboard') }}">Zurücksetzen</a>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Anhänger</th>
                                <th>Kundennr.</th>
                                <th>Kunde</th>
                                <th>Von</th>
                                <th>Bis</th>
                                <th>Reservierung</th>
                                <th>Zahlung</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($reservations as $reservation)
                                @php
                                    $start = optional($reservation->start_date);
                                    $endInclusive = optional($reservation->end_date);
                                @endphp
                                <tr class="reservation-row" onclick="window.location='{{ route('dashboard.reservation.show', $reservation->id) }}'">
                                    <td class="fw-semibold">{{ $reservation->id }}</td>
                                    <td>{{ $reservation->trailer->code ?? '-' }} - {{ $reservation->trailer->name ?? '-' }}</td>
                                    <td>{{ $reservation->customer_number ?? '-' }}</td>
                                    <td>{{ $reservation->customer_first_name }} {{ $reservation->customer_last_name }}</td>
                                    <td>{{ optional($start)->format('d-m-Y') }}</td>
                                    <td>{{ optional($endInclusive)->format('d-m-Y') }}</td>
                                    <td>
                                        @if($reservation->status === 'pending')
                                            <span class="ui-badge ui-badge--warning"><i class="bi bi-telephone"></i> Pending</span>
                                        @else
                                            <span class="ui-badge ui-badge--success"><i class="bi bi-check2-circle"></i> Bestätigt</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reservation->payment_status === 'paid')
                                            <span class="ui-badge ui-badge--success"><i class="bi bi-credit-card"></i> Bezahlt</span>
                                        @elseif($reservation->payment_status === 'partial')
                                            <span class="ui-badge ui-badge--warning"><i class="bi bi-cash-stack"></i> Teilweise bezahlt</span>
                                        @else
                                            <span class="ui-badge ui-badge--neutral"><i class="bi bi-clock"></i> Offen</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-body-secondary py-4">
                                        Keine aktiven oder kommenden Reservierungen.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
