<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Mail\ReservationConfirmed;
use App\Models\Reservation;
use App\Models\Trailer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TrailerReservationController extends Controller
{
    public function blockedDates(Request $request, int $trailerId): JsonResponse
    {
        $excludeReservationId = $request->filled('exclude') ? (int) $request->query('exclude') : null;

        $blocked = Reservation::query()
            ->where('trailer_id', $trailerId)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->orderBy('start_date')
            ->get(['start_date', 'end_date'])
            ->map(function (Reservation $r) {
                $startBuffered = optional($r->start_date)?->copy()?->subDay();
                $endBuffered = optional($r->end_date)?->copy()?->addDay();

                return [
                    'start' => optional($startBuffered)->format('Y-m-d'),
                    'end' => optional($endBuffered)->format('Y-m-d'),
                ];
            })
            ->values();

        return response()->json([
            'blocked' => $blocked,
        ]);
    }

    public function index(Request $request): View
    {
        $hasFilter = $request->filled('from') || $request->filled('to');

        // Buffer in dagen
        $bufferDays = 1;

        // Filterperiode (incl. buffer)
        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->input('from'))
                ->startOfDay()
                ->subDays($bufferDays)
            : now()->startOfDay()->subDays($bufferDays);

        $toInclusive = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->input('to'))
                ->startOfDay()
                ->addDays($bufferDays)
            : now()->addDays(7)->startOfDay()->addDays($bufferDays);

        $trailers = Trailer::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $reservationsByTrailer = Reservation::query()
            ->with(['trailer'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $toInclusive)
            ->where('end_date', '>=', $from)
            ->orderBy('start_date')
            ->get()
            ->groupBy('trailer_id');

        $cards = $trailers->map(function (Trailer $trailer) use ($reservationsByTrailer) {
            /** @var \Illuminate\Support\Collection<int, Reservation> $list */
            $list = $reservationsByTrailer[$trailer->id] ?? collect();

            if ($list->isEmpty()) {
                return [
                    'trailer' => $trailer,
                    'status' => 'AVAILABLE',
                    'reservation' => null,
                ];
            }

            $pending = $list->first(
                fn (Reservation $r) => $r->status === Reservation::STATUS_PENDING
            );

            if ($pending) {
                return [
                    'trailer' => $trailer,
                    'status' => 'PENDING',
                    'reservation' => $pending,
                ];
            }

            return [
                'trailer' => $trailer,
                'status' => 'OCCUPIED',
                'reservation' => $list->first(),
            ];
        });

        // Alleen bezette trailers verbergen bij actief filter
        if ($hasFilter) {
            $cards = $cards
                ->filter(fn (array $card) =>
                in_array($card['status'], ['AVAILABLE', 'PENDING'], true)
                )
                ->values();
        } else {
            $cards = $cards->values();
        }

        return view('trailers.index', [
            'title' => 'Anhänger',
            'cards' => $cards,
            'filterFrom' => $request->input('from'),
            'filterTo' => $request->input('to'),
            'hasFilter' => $hasFilter,
        ]);
    }

    public function create(Request $request): View
    {
        $trailers = Trailer::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('reservations.create', [
            'title' => 'Reservieren',
            'trailers' => $trailers,
        ]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $start = Carbon::createFromFormat('Y-m-d', $data['start_date'])->startOfDay();
        $endInclusive = Carbon::createFromFormat('Y-m-d', $data['end_date'])->startOfDay();

        if ($start->lt(now()->startOfDay())) {
            return back()->withErrors(['start_date' => 'Startdatum muss heute oder später sein.'])->withInput();
        }

        // Fast pre-check (helps return the correct error even if transaction fails for other reasons)
        $trailerId = (int) $data['trailer_id'];
        // We enforce a 1-day buffer on BOTH SIDES of each reservation.
        // Existing reservation [S..E] blocks [S-1 .. E+1].
        // Overlap check between new [start..end] and existing buffered window:
        // existing.start_date <= (new_end + 1) AND existing.end_date >= (new_start - 1)
        $startMinusOne = (clone $start)->subDay();
        $endPlusOne = (clone $endInclusive)->addDay();
        $overlapExistsPre = Reservation::query()
            ->where('trailer_id', $trailerId)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $endPlusOne->toDateString())
            ->whereDate('end_date', '>=', $startMinusOne->toDateString())
            ->exists();

        if ($overlapExistsPre) {
            return back()->withErrors([
                'trailer_id' => 'Dieser Anhänger ist in diesem Zeitraum nicht verfügbar. Bitte wähle andere Daten oder einen anderen Anhänger.',
            ])->withInput();
        }

        try {
            $reservation = DB::transaction(function () use ($data, $start, $endInclusive, $request, $trailerId) {
                $startMinusOneT = (clone $start)->subDay();
                $endPlusOneT = (clone $endInclusive)->addDay();

                $overlapExists = Reservation::query()
                    ->where('trailer_id', $trailerId)
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->whereDate('start_date', '<=', $endPlusOneT->toDateString())
                    ->whereDate('end_date', '>=', $startMinusOneT->toDateString())
                    ->lockForUpdate()
                    ->exists();

                if ($overlapExists) {
                    return null;
                }

                return Reservation::create([
                    'trailer_id' => $trailerId,
                    'user_id' => $request->user()->id,

                    'customer_number' => $data['customer_number'] ?? null,
                    'company_name' => $data['company_name'] ?? null,

                    'customer_first_name' => $data['customer_first_name'],
                    'customer_last_name' => $data['customer_last_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_email' => $data['customer_email'],

                    'start_date' => $start->toDateString(),
                    'end_date' => $endInclusive->toDateString(),

                    'status' => $data['status'],
                    'payment_status' => $data['payment_status'],
                    'partial_paid_amount' => $data['partial_paid_amount'] ?? null,

                    'service_selber_beladen' => (bool) ($data['service_selber_beladen'] ?? false),
                    'service_lehr' => (bool) ($data['service_lehr'] ?? false),
                    'service_paket' => (bool) ($data['service_paket'] ?? false),

                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            // Don't mask other errors as “not available”
            report($e);
            return back()->withErrors([
                'trailer_id' => 'Speichern fehlgeschlagen. Bitte erneut versuchen oder Administrator kontaktieren.',
            ])->withInput();
        }

        if (!$reservation) {
            return back()->withErrors([
                'trailer_id' => 'Dieser Anhänger ist in diesem Zeitraum nicht verfügbar. Bitte wähle andere Daten oder einen anderen Anhänger.',
            ])->withInput();
        }

        // Send confirmation email (non-blocking)
        if (config('app.send_reservation_confirmation_email', true)) {
            try {
                Mail::to($reservation->customer_email)->send(new ReservationConfirmed($reservation));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('dashboard')
            ->with('success', 'Reservierung gespeichert.');
    }

    public function edit(int $id): View
    {
        $reservation = Reservation::with(['trailer'])->findOrFail($id);

        $trailers = Trailer::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('reservations.edit', [
            'title' => 'Reservierung bearbeiten',
            'reservation' => $reservation,
            'trailers' => $trailers,
        ]);
    }

    public function update(StoreReservationRequest $request, int $id): RedirectResponse
    {
        $reservation = Reservation::findOrFail($id);
        $data = $request->validated();

        $start = Carbon::createFromFormat('Y-m-d', $data['start_date'])->startOfDay();
        $endInclusive = Carbon::createFromFormat('Y-m-d', $data['end_date'])->startOfDay();

        try {
            $updated = DB::transaction(function () use ($reservation, $data, $start, $endInclusive, $request) {
                $trailerId = (int) $data['trailer_id'];

                $startMinusOneU = (clone $start)->subDay();
                $endPlusOneU = (clone $endInclusive)->addDay();

                $overlapExists = Reservation::query()
                    ->where('id', '!=', $reservation->id)
                    ->where('trailer_id', $trailerId)
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->whereDate('start_date', '<=', $endPlusOneU->toDateString())
                    ->whereDate('end_date', '>=', $startMinusOneU->toDateString())
                    ->lockForUpdate()
                    ->exists();

                if ($overlapExists) {
                    return false;
                }

                $reservation->fill([
                    'trailer_id' => $trailerId,
                    'user_id' => $request->user()->id,

                    'customer_number' => $data['customer_number'] ?? null,
                    'company_name' => $data['company_name'] ?? null,

                    'customer_first_name' => $data['customer_first_name'],
                    'customer_last_name' => $data['customer_last_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_email' => $data['customer_email'],

                    'start_date' => $start->toDateString(),
                    'end_date' => $endInclusive->toDateString(),

                    'status' => $data['status'],
                    'payment_status' => $data['payment_status'],
                    'partial_paid_amount' => $data['partial_paid_amount'] ?? null,

                    'service_selber_beladen' => (bool) ($data['service_selber_beladen'] ?? false),
                    'service_lehr' => (bool) ($data['service_lehr'] ?? false),
                    'service_paket' => (bool) ($data['service_paket'] ?? false),

                    'notes' => $data['notes'] ?? null,
                ]);

                $reservation->save();
                return true;
            });
        } catch (\Throwable $e) {
            $updated = false;
        }

        if (!$updated) {
            return back()->withErrors([
                'trailer_id' => 'Dieser Anhänger ist in diesem Zeitraum nicht verfügbar. Bitte wähle andere Daten oder einen anderen Anhänger.',
            ])->withInput();
        }

        return redirect()->route('dashboard.reservation.show', $reservation->id)
            ->with('success', 'Reservierung aktualisiert.');
    }

    public function destroy($id): RedirectResponse
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();
        return redirect()->route('dashboard')->with('success', 'Reservierung gelöscht.');
    }
}
