@php
    $start = $reservation->start_date ?? optional($reservation->starts_at)->format('Y-m-d');
    $endInclusive = $reservation->end_date
        ? optional($reservation->end_date)->format('Y-m-d')
        : optional($reservation->ends_at)->format('Y-m-d');

    $customerName = trim(($reservation->customer_first_name ?? '') . ' ' . ($reservation->customer_last_name ?? ''));

    $reservationStatusLabel = ($reservation->status ?? 'confirmed') === 'pending' ? 'Pending' : 'Bestätigt';
    $paymentStatusValue = $reservation->payment_status ?? 'unpaid';
    if ($paymentStatusValue === 'paid') {
        $paymentStatusLabel = 'Bezahlt';
    } elseif ($paymentStatusValue === 'partial') {
        $amount = number_format((float) ($reservation->partial_paid_amount ?? 0), 2, ',', '.');
        $paymentStatusLabel = 'Teilweise bezahlt (' . $amount . ' €)';
    } else {
        $paymentStatusLabel = 'Noch zu bezahlen';
    }

    $headerDate = now()->format('d.m.Y');
@endphp

@component('emails._layout', [
    'title' => 'Reservierung bestätigt',
    'preheader' => 'Deine Reservierung wurde gespeichert.',
    'brand' => 'Schramowski Getränke',
    'subtitle' => 'Reservierung',
    'footer' => '&copy; ' . now()->year . ' Schramowski Getränke',
])
    <h1 style="margin:0 0 10px 0;font-size:20px;line-height:1.25;color:#111;font-weight:800;">Reservierung bestätigt</h1>

    <p style="margin:0 0 12px 0;color:#374151;font-size:14px;line-height:1.6;">
        Hallo {{ $reservation->customer_first_name }},
    </p>

    <p style="margin:0 0 14px 0;color:#374151;font-size:14px;line-height:1.6;">
        vielen Dank. Deine Reservierung wurde gespeichert.
    </p>

    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:14px 14px;margin:14px 0;">
        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Deine Angaben</div>

        <div style="font-size:13px;color:#374151;line-height:1.55;">
            <strong>Name:</strong> {{ $customerName }}<br>
            <strong>E-Mail:</strong> <a href="mailto:{{ $reservation->customer_email }}" style="color:#2563eb;text-decoration:underline;">{{ $reservation->customer_email }}</a><br>
            <strong>Telefon:</strong> {{ $reservation->customer_phone }}
        </div>

        <div style="margin-top:12px;font-size:12px;color:#6b7280;margin-bottom:6px;">Zeitraum</div>
        <div style="font-size:13px;color:#374151;line-height:1.55;">
            <strong>Von:</strong> {{ \Carbon\Carbon::parse($start)->format('d-m-Y') }}<br>
            <strong>Bis:</strong> {{ \Carbon\Carbon::parse($endInclusive)->format('d-m-Y') }}
        </div>

        <div style="margin-top:12px;font-size:12px;color:#6b7280;margin-bottom:6px;">Status</div>
        <div style="font-size:13px;color:#374151;line-height:1.55;">
            <strong>Reservierung:</strong> {{ $reservationStatusLabel }}<br>
            <strong>Zahlung:</strong> {{ $paymentStatusLabel }}
        </div>
    </div>

    <p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">
        Bitte pr&uuml;fe die Angaben. Wenn etwas nicht stimmt, antworte einfach auf diese E-Mail.
    </p>
@endcomponent
