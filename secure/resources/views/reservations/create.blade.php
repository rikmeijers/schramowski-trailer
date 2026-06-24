@extends('shared.layout')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1">Neue Reservierung</h1>
            <p class="text-body-secondary mb-0">Pflichtfelder sind mit <span class="text-danger">*</span> markiert. Optionale Angaben sind entsprechend gekennzeichnet.</p>
        </div>

        <a href="{{ route('trailers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i> Zurück
        </a>
    </div>

    <div class="card ui-card ui-card-pad mt-3">
        <form method="POST" action="{{ route('reservations.store') }}" class="row g-3"
              data-blocked-dates-url-template="{{ route('trailers.blocked_dates', ['trailerId' => '__TRAILER_ID__']) }}">
            @csrf

            <div class="col-12">
                <h5 class="fw-bold mb-0">Reservierung</h5>
                <div class="text-body-secondary">Bitte Zeitraum und Status auswählen.</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-truck me-1"></i> Anhänger <span class="text-danger">*</span></label>
                <select name="trailer_id" class="form-select @error('trailer_id') is-invalid @enderror">
                    <option value="">-- Anhänger auswählen --</option>
                    @foreach ($trailers as $trailer)
                        <option value="{{ $trailer->id }}"
                            @selected(old('trailer_id', request('trailer_id')) == $trailer->id)>
                            {{ $trailer->code }} - {{ $trailer->name }}
                        </option>
                    @endforeach
                </select>
                @error('trailer_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-calendar-event me-1"></i> Startdatum <span class="text-danger">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', request('start_date')) }}"
                       class="form-control @error('start_date') is-invalid @enderror" />
                @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-calendar-event me-1"></i> Enddatum <span class="text-danger">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date', request('end_date')) }}"
                       class="form-control @error('end_date') is-invalid @enderror" />
                @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-hourglass-split me-1"></i> Reservierungsstatus <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="confirmed" @selected(old('status', 'confirmed') === 'confirmed')>Bestätigt</option>
                    <option value="pending" @selected(old('status') === 'pending')>Pending (Telefon / noch nicht sicher)</option>
                </select>
                @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-credit-card me-1"></i> Zahlstatus <span class="text-danger">*</span></label>
                <select name="payment_status" class="form-select @error('payment_status') is-invalid @enderror" data-payment-status>
                    <option value="unpaid" @selected(old('payment_status', 'unpaid') === 'unpaid')>Noch zu bezahlen</option>
                    <option value="partial" @selected(old('payment_status') === 'partial')>Teilweise bezahlt</option>
                    <option value="paid" @selected(old('payment_status') === 'paid')>Bezahlt</option>
                </select>
                @error('payment_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6" data-partial-paid-wrapper style="display:none;">
                <label class="form-label fw-semibold"><i class="bi bi-cash-stack me-1"></i> Bereits bezahlt <span class="text-danger">*</span></label>
                <input type="tel" inputmode="decimal" pattern="[0-9]*[.,]?[0-9]{0,2}" step="0.01" min="0" name="partial_paid_amount"
                       value="{{ old('partial_paid_amount') }}"
                       class="form-control @error('partial_paid_amount') is-invalid @enderror" data-partial-paid-input />
                @error('partial_paid_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text text-body-secondary">Nur ausfüllen, wenn der Zahlstatus auf "Teilweise bezahlt" steht.</div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="ignore_buffer" value="0" />
                    <input class="form-check-input" type="checkbox" name="ignore_buffer" id="ignore_buffer" value="1"
                           @checked(old('ignore_buffer'))>
                    <label class="form-check-label fw-semibold" for="ignore_buffer">
                        <i class="bi bi-unlock me-1"></i> Pufferzeit ignorieren (Ein-/Ausladetag)
                    </label>
                    <div class="form-text text-body-secondary">
                        Normalerweise bleibt je 1 Tag vor und nach einer Reservierung frei (Ein-/Ausladen).
                        Aktivieren, um diese Reservierung direkt an eine andere Buchung anschließen zu lassen.
                    </div>
                </div>
            </div>

            <div class="col-12">
                <hr class="my-2" />
                <h5 class="fw-bold mb-0">Kundendaten</h5>
                <p class="text-body-secondary mb-0">Diese Daten werden bei der Reservierung gespeichert.</p>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-hash me-1"></i> Kundennummer <span class="text-body-secondary fw-normal">(optional)</span></label>
                <input name="customer_number" value="{{ old('customer_number') }}"
                       class="form-control @error('customer_number') is-invalid @enderror" />
                @error('customer_number')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-building me-1"></i> Firma <span class="text-body-secondary fw-normal">(optional)</span></label>
                <input name="company_name" value="{{ old('company_name') }}"
                       class="form-control @error('company_name') is-invalid @enderror" />
                @error('company_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-person me-1"></i> Vorname <span class="text-danger">*</span></label>
                <input name="customer_first_name" value="{{ old('customer_first_name') }}"
                       class="form-control @error('customer_first_name') is-invalid @enderror" />
                @error('customer_first_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-person me-1"></i> Nachname <span class="text-danger">*</span></label>
                <input name="customer_last_name" value="{{ old('customer_last_name') }}"
                       class="form-control @error('customer_last_name') is-invalid @enderror" />
                @error('customer_last_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-telephone me-1"></i> Telefonnummer <span class="text-danger">*</span></label>
                <input name="customer_phone" value="{{ old('customer_phone') }}"
                       class="form-control @error('customer_phone') is-invalid @enderror" />
                @error('customer_phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-envelope me-1"></i> E-Mail <span class="text-danger">*</span></label>
                <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                       class="form-control @error('customer_email') is-invalid @enderror" />
                @error('customer_email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <hr class="my-2" />
                <h5 class="fw-bold mb-0">Zusatzleistungen</h5>
                <p class="text-body-secondary mb-0"><span class="text-danger">*</span> Bitte wählen Sie eine Zusatzleistung (verpflichtend).</p>
            </div>

            <div class="col-12">
                <div class="d-flex flex-column gap-2">
                    @php
                        // Determine selected service option. We keep the existing boolean field names
                        // on the backend, so we output three hidden boolean inputs (default 0)
                        // and a radio group (required) that the JS will sync into those booleans before submit.
                        $selectedService = old('service_option') ?? (
                            old('service_selber_beladen') ? 'selber_beladen' : (
                            old('service_lehr') ? 'lehr' : (
                            old('service_paket') ? 'paket' : 'selber_beladen')));
                    @endphp

                    {{-- Hidden boolean fields (Laravel expects these names) --}}
                    <input type="hidden" name="service_selber_beladen" id="hidden_service_selber_beladen" value="0" />
                    <input type="hidden" name="service_lehr" id="hidden_service_lehr" value="0" />
                    <input type="hidden" name="service_paket" id="hidden_service_paket" value="0" />

                    {{-- Radio group (required): one must be selected --}}
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="service_option" id="service_option_selber" value="selber_beladen" @checked($selectedService === 'selber_beladen') required>
                        <label class="form-check-label" for="service_option_selber">
                            <span class="fw-semibold">Option 1:</span> Selber beladen <span class="text-body-secondary">(+ 0 €)</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="service_option" id="service_option_lehr" value="lehr" @checked($selectedService === 'lehr')>
                        <label class="form-check-label" for="service_option_lehr">
                            <span class="fw-semibold">Option 2:</span> Lehr <span class="text-body-secondary">(+ 25 €)</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="service_option" id="service_option_paket" value="paket" @checked($selectedService === 'paket')>
                        <label class="form-check-label" for="service_option_paket">
                            <span class="fw-semibold">Option 3:</span> Servicepaket <span class="text-body-secondary">(+ 35 €)</span>
                        </label>
                    </div>
                 </div>
             </div>

            <div class="col-12">
                <label class="form-label fw-semibold"><i class="bi bi-journal-text me-1"></i> Notizen <span class="text-body-secondary fw-normal">(optional)</span></label>
                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 d-grid d-md-flex justify-content-md-end gap-2 mt-4">
                <button class="btn btn-primary rounded-pill px-4" type="submit">
                    <i class="bi bi-check2-circle me-2"></i> Reservierung speichern
                </button>
            </div>
        </form>
    </div>
@endsection

@section('customScripts')
    <script src="{{ url('/assets/js/reservations-form.js') }}"></script>
@endsection
