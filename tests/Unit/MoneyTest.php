<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_keeps_exact_cents_and_column_boundaries(): void
    {
        $this->assertSame(10, Money::cents('0.10'));
        $this->assertSame(9999999999, Money::cents('99999999.99'));
        $this->assertSame('100.05', Money::decimal(10005));
    }

    public static function invalidAmounts(): array
    {
        return array_map(fn ($value) => [$value], ['-1', '1e3', '1.001', '100000000.00', 'NaN', '']);
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amounts_are_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::cents($value);
    }
}
