<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class CalculateTimeEntryDurationService
{
    public function calculate(CarbonInterface $start, CarbonInterface $end): int
    {
        $seconds = $end->getTimestamp() - $start->getTimestamp();
        if ($seconds <= 0) {
            throw ValidationException::withMessages(['ended_at' => 'O horário final deve ser posterior ao horário inicial.']);
        }

        return $seconds;
    }
}
