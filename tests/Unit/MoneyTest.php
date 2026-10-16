<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_are_converted_and_formatted_without_floating_point_rounding(): void
    {
        $this->assertSame(100025, Money::toMinorUnits('1000.25'));
        $this->assertSame(-1, Money::toMinorUnits('-0.01'));
        $this->assertSame('1000.25', Money::format(100025));
        $this->assertSame('-0.01', Money::format(-1));
    }

    public function test_amounts_with_more_than_two_decimal_places_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toMinorUnits('1.001');
    }
}
