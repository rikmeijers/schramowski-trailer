<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Trailer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redirect;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $showAll = request()->boolean('show_all', false);

        $reservationsQuery = Reservation::with(['trailer', 'user'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        if (!$showAll) {
            // active or upcoming (end_date is stored as exclusive)
            $reservationsQuery->whereDate('end_date', '>=', $today->toDateString());
        }

        $reservations = $reservationsQuery
            ->orderBy('start_date')
            ->get();

        // Stats for the summary bar
        $stats = [
            'total'     => $reservations->count(),
            'confirmed' => $reservations->where('status', 'confirmed')->count(),
            'pending'   => $reservations->where('status', 'pending')->count(),
            'paid'      => $reservations->where('payment_status', 'paid')->count(),
            'partial'   => $reservations->where('payment_status', 'partial')->count(),
            'unpaid'    => $reservations->where('payment_status', 'unpaid')->count(),
        ];

        return view('dashboard', [
            'title' => 'Übersicht',
            'reservations' => $reservations,
            'showAll' => $showAll,
            'stats' => $stats,
        ]);
    }

    public function showReservation($id)
    {
        $reservation = Reservation::with(['trailer', 'user'])->findOrFail($id);

        return view('dashboard-reservation', [
            'title' => 'Reservierungsdetails',
            'reservation' => $reservation,
        ]);
    }

    public function destroyReservation($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();
        return redirect()->route('dashboard')->with('success', 'Reservierung storniert.');
    }

    public function printNextWeek()
    {
        // Nächste Woche (ISO Woche Mo..So)
        $start = now()->addWeek()->startOfWeek();
        $endInclusive = $start->copy()->endOfWeek()->startOfDay();

        $reservations = Reservation::with(['trailer'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->overlappingDates($start, $endInclusive)
            ->orderBy('start_date')
            ->orderBy('trailer_id')
            ->get();

        return view('dashboard-print-next-week', [
            'title' => 'Druckübersicht nächste Woche',
            'header' => false,
            'footer' => false,
            'start' => $start,
            'endInclusive' => $endInclusive,
            'reservations' => $reservations,
        ]);
    }

    public function users()
    {
        $users = User::orderBy('name')->get();
        return view('users.index', [
            'title' => 'Benutzer',
            'users' => $users,
        ]);
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->isAdmin()) {
            return Redirect::back()->withErrors(['user' => 'Admin kann nicht gelöscht werden.']);
        }
        $user->delete();
        return Redirect::route('users.index')->with('success', 'Benutzer gelöscht.');
    }

    public function showRegisterForm()
    {
        return view('auth.register', [
            'title' => 'Benutzer hinzufügen',
            'header' => false,
            'footer' => false,
        ]);
    }

    public function calendarData(): JsonResponse
    {
        $from = request()->filled('from')
            ? Carbon::createFromFormat('Y-m-d', (string) request()->input('from'))->startOfDay()
            : now()->startOfWeek();

        $to = request()->filled('to')
            ? Carbon::createFromFormat('Y-m-d', (string) request()->input('to'))->startOfDay()
            : $from->copy()->addWeeks(2);

        $trailers = Trailer::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $reservations = Reservation::with([])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->overlappingDates($from, $to)
            ->orderBy('start_date')
            ->get([
                'id', 'trailer_id', 'start_date', 'end_date',
                'customer_first_name', 'customer_last_name',
                'status', 'payment_status',
            ]);

        return response()->json([
            'trailers' => $trailers,
            'reservations' => $reservations,
        ]);
    }

    public function printRange()
    {
        $today = now()->startOfDay();

        $days = request()->filled('days') ? (int) request()->input('days') : null;

        if ($days !== null) {
            $days = max(1, min(31, $days));
            $from = $today->copy();
            $toInclusive = $today->copy()->addDays($days);
        } else {
            $from = request()->filled('from')
                ? Carbon::createFromFormat('Y-m-d', (string) request()->input('from'))->startOfDay()
                : $today->copy();

            $toInclusive = request()->filled('to')
                ? Carbon::createFromFormat('Y-m-d', (string) request()->input('to'))->startOfDay()
                : $today->copy()->addDays(7);
        }

        if ($toInclusive->lessThan($from)) {
            $toInclusive = $from->copy();
        }

        $reservations = Reservation::with(['trailer'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->overlappingDates($from, $toInclusive)
            ->orderBy('start_date')
            ->orderBy('trailer_id')
            ->get();

        return view('dashboard-print-range', [
            'title' => 'Druckübersicht',
            'header' => false,
            'footer' => false,
            'from' => $from,
            'toInclusive' => $toInclusive,
            'reservations' => $reservations,
        ]);
    }
}
