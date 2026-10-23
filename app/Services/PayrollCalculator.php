<?php

namespace App\Services;

final class PayrollCalculator
{
    /** @param array{base_minor:int,allowances_minor:int,deductions_minor:int,attendance_days:int,overtime_minutes:int} $input
     * @return array{gross_minor:int,overtime_minor:int,net_minor:int}
     */
    public function calculate(array $input, int $overtimeRateMinorPerHour): array
    {
        $overtime = intdiv($overtimeRateMinorPerHour * $input['overtime_minutes'] + 30, 60);
        $gross = $input['base_minor'] + $input['allowances_minor'] + $overtime;

        return [
            'gross_minor' => $gross,
            'overtime_minor' => $overtime,
            'net_minor' => max(0, $gross - $input['deductions_minor']),
        ];
    }
}
