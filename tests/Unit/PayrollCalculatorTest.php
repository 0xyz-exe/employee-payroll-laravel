<?php

namespace Tests\Unit;

use App\Services\PayrollCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PayrollCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_integer_minor_unit_payroll_with_configured_overtime(): void
    {
        $result = (new PayrollCalculator())->calculate([
            'base_minor'=>100_000,'allowances_minor'=>5_000,'deductions_minor'=>2_000,
            'attendance_days'=>20,'overtime_minutes'=>60,
        ], 100);
        self::assertSame(['gross_minor'=>105_100,'overtime_minor'=>100,'net_minor'=>103_100], $result);
    }

    #[Test]
    public function it_floors_net_pay_at_zero_and_rounds_overtime_to_nearest_minor_unit(): void
    {
        $calculator = new PayrollCalculator();
        self::assertSame(['gross_minor'=>20,'overtime_minor'=>10,'net_minor'=>0], $calculator->calculate([
            'base_minor'=>10,'allowances_minor'=>0,'deductions_minor'=>30,'attendance_days'=>1,'overtime_minutes'=>1,
        ], 600));
    }
}
