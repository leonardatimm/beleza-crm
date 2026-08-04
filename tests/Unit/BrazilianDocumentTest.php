<?php

namespace Tests\Unit;

use App\Support\BrazilianDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BrazilianDocumentTest extends TestCase
{
    #[DataProvider('validCpfs')]
    public function test_validates_cpf_with_or_without_mask(string $cpf): void
    {
        $this->assertTrue(BrazilianDocument::isValidCpf($cpf));
    }

    /** @return array<string, array{string}> */
    public static function validCpfs(): array
    {
        return [
            'masked' => ['529.982.247-25'],
            'digits' => ['52998224725'],
        ];
    }

    #[DataProvider('invalidCpfs')]
    public function test_rejects_invalid_cpf(string $cpf): void
    {
        $this->assertFalse(BrazilianDocument::isValidCpf($cpf));
    }

    /** @return array<string, array{string}> */
    public static function invalidCpfs(): array
    {
        return [
            'repeated' => ['111.111.111-11'],
            'wrong digit' => ['529.982.247-24'],
            'short' => ['123'],
        ];
    }

    public function test_validates_and_rejects_cnpj(): void
    {
        $this->assertTrue(BrazilianDocument::isValidCnpj('04.252.011/0001-10'));
        $this->assertTrue(BrazilianDocument::isValidCnpj('04252011000110'));
        $this->assertFalse(BrazilianDocument::isValidCnpj('11.111.111/1111-11'));
        $this->assertFalse(BrazilianDocument::isValidCnpj('04.252.011/0001-11'));
    }

    public function test_formats_cpf(): void
    {
        $this->assertSame('529.982.247-25', BrazilianDocument::formatCpf('52998224725'));
    }
}
