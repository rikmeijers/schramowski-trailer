<?php

namespace App\Services;

use Carbon\CarbonInterface;

class ReservationHours
{
    public function stepMinutes(): int
    {
        return (int) config('reservation-hours.step_minutes', 30);
    }

    /**
     * @return array{open:string,close:string}|null
     */
    public function hoursForDate(CarbonInterface $date): ?array
    {
        $day = $date->copy()->startOfDay();
        $key = $day->format('Y-m-d');

        $closures = (array) config('reservation-hours.closures', []);
        if (in_array($key, $closures, true)) {
            return null;
        }

        $special = (array) config('reservation-hours.special', []);
        if (array_key_exists($key, $special)) {
            return $special[$key] ?: null;
        }

        $weekly = (array) config('reservation-hours.weekly', []);
        $isoDow = (int) $day->isoWeekday(); // 1..7

        return $weekly[$isoDow] ?? null;
    }

    public function isClosedDate(CarbonInterface $date): bool
    {
        return $this->hoursForDate($date) === null;
    }

    public function isValidStart(CarbonInterface $startsAt): bool
    {
        $hours = $this->hoursForDate($startsAt);
        if ($hours === null) {
            return false;
        }

        $step = $this->stepMinutes();
        $minute = (int) $startsAt->minute;
        if ($minute % $step !== 0) {
            return false;
        }

        [$oh, $om] = array_map('intval', explode(':', $hours['open']));
        [$ch, $cm] = array_map('intval', explode(':', $hours['close']));

        $open = $startsAt->copy()->setTime($oh, $om, 0);
        $close = $startsAt->copy()->setTime($ch, $cm, 0);

        return $startsAt->betweenIncluded($open, $close);
    }

    public function toFrontendConfig(): array
    {
        return [
            'step_minutes' => $this->stepMinutes(),
            'weekly' => config('reservation-hours.weekly'),
            'closures' => config('reservation-hours.closures'),
            'special' => config('reservation-hours.special'),
        ];
    }
}

