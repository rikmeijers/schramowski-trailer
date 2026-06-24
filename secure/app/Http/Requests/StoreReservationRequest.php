<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Models\Trailer;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'trailer_id' => ['required', Rule::exists(Trailer::class, 'id')],

            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],

            'status' => ['required', Rule::in([Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED])],
            'payment_status' => ['required', Rule::in([Reservation::PAYMENT_UNPAID, Reservation::PAYMENT_PAID, Reservation::PAYMENT_PARTIAL])],
            'partial_paid_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_status,' . Reservation::PAYMENT_PARTIAL],

            // Service option is required (radio). We also accept the legacy boolean fields but prefer service_option.
            'service_option' => ['required', Rule::in(['selber_beladen', 'lehr', 'paket'])],

            'service_selber_beladen' => ['sometimes', 'boolean'],
            'service_lehr' => ['sometimes', 'boolean'],
            'service_paket' => ['sometimes', 'boolean'],

            // Skip the 1-day load/unload buffer for this reservation.
            'ignore_buffer' => ['sometimes', 'boolean'],

            'customer_number' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],

            'customer_first_name' => ['required', 'string', 'max:255'],
            'customer_last_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email:rfc,dns', 'max:255'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Unchecked checkboxes are not sent by browsers; normalize to a boolean.
        $this->merge([
            'ignore_buffer' => (bool) $this->input('ignore_buffer', false),
        ]);

        // Normaliseer partial_paid_amount: '12,50' -> '12.50' (punt blijft punt)
        $rawPartial = $this->input('partial_paid_amount');
        if (is_string($rawPartial)) {
            $normalized = trim($rawPartial);
            $normalized = str_replace(' ', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            $this->merge([
                'partial_paid_amount' => $normalized === '' ? null : $normalized,
            ]);
        }

        // Maak partial_paid_amount leeg wanneer payment_status niet 'partial' is.
        $paymentStatus = $this->input('payment_status');
        if ($paymentStatus !== Reservation::PAYMENT_PARTIAL) {
            $this->merge([
                'partial_paid_amount' => null,
            ]);
        }

        // If the new radio-based `service_option` is present, derive the boolean fields from it.
        $serviceOption = $this->input('service_option');
        if ($serviceOption) {
            $this->merge([
                'service_selber_beladen' => $serviceOption === 'selber_beladen',
                'service_lehr' => $serviceOption === 'lehr',
                'service_paket' => $serviceOption === 'paket',
            ]);
            return;
        }

        // Fallback for older forms: unchecked checkboxes are not sent by browsers.
        $this->merge([
            'service_selber_beladen' => (bool) $this->input('service_selber_beladen', false),
            'service_lehr' => (bool) $this->input('service_lehr', false),
            'service_paket' => (bool) $this->input('service_paket', false),
        ]);
    }

    public function messages(): array
    {
        return [
            'trailer_id.required' => 'Bitte einen Anhänger auswählen.',
            'start_date.required' => 'Bitte ein Startdatum auswählen.',
            'start_date.date_format' => 'Startdatum hat ein ungültiges Format.',
            'start_date.after_or_equal' => 'Startdatum muss heute oder später sein.',
            'end_date.required' => 'Bitte ein Enddatum auswählen.',
            'end_date.date_format' => 'Enddatum hat ein ungültiges Format.',
            'end_date.after' => 'Enddatum muss nach dem Startdatum liegen.',
            'service_option.required' => 'Bitte eine Zusatzleistung auswählen.',
            'partial_paid_amount.required_if' => 'Bitte den bereits bezahlten Betrag angeben.',
            'partial_paid_amount.numeric' => 'Der Betrag muss eine Zahl sein (z.B. 12,50).',
            'partial_paid_amount.min' => 'Der Betrag darf nicht negativ sein.',
        ];
    }
}
