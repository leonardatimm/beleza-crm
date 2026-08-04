<?php

namespace Tests\Unit;

use App\Support\BrazilianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BrazilianPhoneTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_validates_phone_with_ddd_and_ten_or_eleven_digits(string $phone): void
    {
        $this->assertTrue(BrazilianPhone::isValid($phone));
    }

    /** @return array<string, array{string}> */
    public static function validPhones(): array
    {
        return [
            'landline' => ['(47) 3333-4444'],
            'mobile' => ['(47) 99999-1234'],
            'digits' => ['47999991234'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_rejects_phone_without_ten_or_eleven_digits(string $phone): void
    {
        $this->assertFalse(BrazilianPhone::isValid($phone));
    }

    /** @return array<string, array{string}> */
    public static function invalidPhones(): array
    {
        return [
            'short' => ['479999123'],
            'long' => ['479999999999'],
            'empty' => [''],
        ];
    }

    public function test_formats_valid_phone_numbers(): void
    {
        $this->assertSame('(47) 3333-4444', BrazilianPhone::format('4733334444'));
        $this->assertSame('(47) 99999-1234', BrazilianPhone::format('47999991234'));
    }
}
