@extends('shared.layout')

@section('customStyles')
    <link rel="stylesheet" href="{{ url('/assets/css/dashboard-calendar.css') }}">
    <style>
        /* Sticky table header */
        .table-scroll-wrapper {
            max-height: 70vh;
            overflow-y: auto;
        }
        .table-scroll-wrapper thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #fff;
            box-shadow: inset 0 -2px 0 #E2E8F0;
        }
        /* Sortable headers */
        th[data-sort] {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        th[data-sort]:hover {
            color: #0F172A;
            background: #F8FAFC !important;
        }
        th[data-sort] .sort-icon {
            font-size: .7em;
            opacity: .4;
            margin-left: .25rem;
        }
        th[data-sort].sort-active .sort-icon {
            opacity: 1;
        }
        /* Stats bar */
        .stats-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            border: 1px solid #E2E8F0;
            background: #fff;
            color: #334155;
        }
        .stat-chip strong {
            font-weight: 700;
            color: #0F172A;
        }
        .stat-chip--warning { background: #FEF3C7; border-color: #FDE68A; color: #92400E; }
        .stat-chip--warning strong { color: #92400E; }
        .stat-chip--success { background: #DCFCE7; border-color: #BBF7D0; color: #15803D; }
        .stat-chip--success strong { color: #15803D; }
        .stat-chip--neutral { background: #F1F5F9; border-color: #E2E8F0; color: #475569; }
        .stat-chip--neutral strong { color: #475569; }
    </style>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1">Übersicht</h1>
            <p class="text-body-secondary mb-0">Aktive und kommende Reservierungen.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reservations.create') }}" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle me-2"></i> Neue Reservierung
            </a>

            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
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
                        <form method="GET" action="{{ route('dashboard.print.range') }}" target="_blank" class="row g-2" data-linked-dates>
                            <div class="col-6">
                                <label class="form-label small mb-1">Von</label>
                                <input type="date" class="form-control form-control-sm" name="from" required data-link-from>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Bis</label>
                                <input type="date" class="form-control form-control-sm" name="to" required data-link-to>
                            </div>
                            <div class="col-12 d-grid mt-1">
                                <button class="btn btn-primary btn-sm" type="submit">Drucken</button>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>

            @can('admin')
                <a href="{{ route('users.index') }}" class="btn btn-outline-primary px-4">
                    <i class="bi bi-people me-2"></i> Benutzer
                </a>
                <a href="{{ route('register.form') }}" class="btn btn-outline-primary px-4">
                    <i class="bi bi-person-plus me-2"></i> Benutzer hinzufügen
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats bar --}}
    <div class="stats-bar mb-3">
        <span class="stat-chip"><strong>{{ $stats['total'] }}</strong> Gesamt</span>
        <span class="stat-chip stat-chip--success"><i class="bi bi-check2-circle"></i> <strong>{{ $stats['confirmed'] }}</strong> Bestätigt</span>
        <span class="stat-chip stat-chip--warning"><i class="bi bi-telephone"></i> <strong>{{ $stats['pending'] }}</strong> Pending</span>
        <span class="stat-chip stat-chip--success"><i class="bi bi-credit-card"></i> <strong>{{ $stats['paid'] }}</strong> Bezahlt</span>
        @if($stats['partial'] > 0)
            <span class="stat-chip stat-chip--warning"><i class="bi bi-cash-stack"></i> <strong>{{ $stats['partial'] }}</strong> Teilweise</span>
        @endif
        <span class="stat-chip stat-chip--neutral"><i class="bi bi-clock"></i> <strong>{{ $stats['unpaid'] }}</strong> Offen</span>
    </div>

    {{-- View toggle --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="view-toggle btn-group" role="group" aria-label="Ansicht wechseln">
            <button type="button" class="btn active" id="btn-view-table" onclick="switchView('table')">
                <i class="bi bi-table me-1"></i> Tabelle
            </button>
            <button type="button" class="btn" id="btn-view-calendar" onclick="switchView('calendar')">
                <i class="bi bi-calendar3 me-1"></i> Kalender
            </button>
        </div>
    </div>

    {{-- Table view --}}
    <div id="table-view">
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
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

                        <div class="table-scroll-wrapper">
                            <table class="table table-hover align-middle mb-0" id="reservations-table">
                                <thead>
                                <tr>
                                    <th data-sort="id"># <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="trailer">Anhänger <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="customer_number">Kundennr. <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="customer">Kunde <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="from">Von <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="to">Bis <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="status">Reservierung <i class="bi bi-chevron-expand sort-icon"></i></th>
                                    <th data-sort="payment">Zahlung <i class="bi bi-chevron-expand sort-icon"></i></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($reservations as $reservation)
                                    @php
                                        $start = optional($reservation->start_date);
                                        $endInclusive = optional($reservation->end_date);
                                    @endphp
                                    <tr class="reservation-row" onclick="window.location='{{ route('dashboard.reservation.show', $reservation->id) }}'"
                                        data-id="{{ $reservation->id }}"
                                        data-trailer="{{ $reservation->trailer->name ?? '' }}"
                                        data-customer-number="{{ $reservation->customer_number ?? '' }}"
                                        data-customer="{{ $reservation->customer_first_name }} {{ $reservation->customer_last_name }}"
                                        data-from="{{ optional($start)->format('Y-m-d') }}"
                                        data-to="{{ optional($endInclusive)->format('Y-m-d') }}"
                                        data-status="{{ $reservation->status }}"
                                        data-payment="{{ $reservation->payment_status }}">
                                        <td class="fw-semibold">{{ $reservation->id }}</td>
                                        <td>{{ $reservation->trailer->name ?? '-' }}</td>
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
    </div>

    {{-- Calendar view --}}
    <div id="calendar-view-wrapper" style="display:none;">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Kalender</h5>
                        <div class="text-body-secondary small">Horizontal durch die Wochen scrollen. Neue Wochen werden automatisch geladen.</div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="calendar-legend d-none d-md-flex">
                            <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--confirmed"></span> Bestätigt</span>
                            <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--pending"></span> Pending</span>
                            <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--today"></span> Heute</span>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm px-3" id="cal-today">
                            <i class="bi bi-geo-alt me-1"></i> Heute
                        </button>
                    </div>
                </div>

                <div class="calendar-container">
                    <div id="calendar-view"
                         data-api-url="{{ route('dashboard.calendar.data') }}"
                         data-detail-url="{{ url('/dashboard/reservation/__ID__') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('customScripts')
    <script src="{{ url('/assets/js/dashboard-calendar.js') }}"></script>
    <script>
        function switchView(view) {
            var tableView = document.getElementById('table-view');
            var calendarView = document.getElementById('calendar-view-wrapper');
            var btnTable = document.getElementById('btn-view-table');
            var btnCalendar = document.getElementById('btn-view-calendar');

            if (view === 'calendar') {
                tableView.style.display = 'none';
                calendarView.style.display = 'block';
                btnTable.classList.remove('active');
                btnCalendar.classList.add('active');
                try { localStorage.setItem('dashboard_view', 'calendar'); } catch(e) {}
            } else {
                tableView.style.display = 'block';
                calendarView.style.display = 'none';
                btnTable.classList.add('active');
                btnCalendar.classList.remove('active');
                try { localStorage.setItem('dashboard_view', 'table'); } catch(e) {}
            }
        }

        // Restore last view preference
        document.addEventListener('DOMContentLoaded', function () {
            try {
                var saved = localStorage.getItem('dashboard_view');
                if (saved === 'calendar') switchView('calendar');
            } catch(e) {}
        });

        // ── Table sorting ──
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('reservations-table');
            if (!table) return;

            var headers = table.querySelectorAll('th[data-sort]');
            var currentSort = null;
            var currentDir = 'asc';

            headers.forEach(function (th) {
                th.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var key = th.dataset.sort;

                    if (currentSort === key) {
                        currentDir = currentDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort = key;
                        currentDir = 'asc';
                    }

                    // Update active header styling
                    headers.forEach(function (h) {
                        h.classList.remove('sort-active');
                        var icon = h.querySelector('.sort-icon');
                        if (icon) { icon.className = 'bi bi-chevron-expand sort-icon'; }
                    });
                    th.classList.add('sort-active');
                    var activeIcon = th.querySelector('.sort-icon');
                    if (activeIcon) {
                        activeIcon.className = 'bi sort-icon ' + (currentDir === 'asc' ? 'bi-chevron-up' : 'bi-chevron-down');
                    }

                    // Sort rows
                    var tbody = table.querySelector('tbody');
                    var rows = Array.from(tbody.querySelectorAll('tr.reservation-row'));
                    if (rows.length === 0) return;

                    rows.sort(function (a, b) {
                        var aVal = (a.dataset[key] || a.dataset[toCamelCase(key)] || '').toLowerCase();
                        var bVal = (b.dataset[key] || b.dataset[toCamelCase(key)] || '').toLowerCase();

                        // Numeric sort for id
                        if (key === 'id') {
                            aVal = parseInt(aVal, 10) || 0;
                            bVal = parseInt(bVal, 10) || 0;
                            return currentDir === 'asc' ? aVal - bVal : bVal - aVal;
                        }

                        // String sort
                        if (aVal < bVal) return currentDir === 'asc' ? -1 : 1;
                        if (aVal > bVal) return currentDir === 'asc' ? 1 : -1;
                        return 0;
                    });

                    rows.forEach(function (row) { tbody.appendChild(row); });
                });
            });

            function toCamelCase(str) {
                return str.replace(/_([a-z])/g, function (m, c) { return c.toUpperCase(); });
            }
        });
    </script>
@endsection
