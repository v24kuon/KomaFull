<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function inquiryTypeLabelProvider(): array
    {
        return [
            'reservation' => ['reservation', '予約・開催枠'],
            'billing' => ['billing', '決済・請求'],
            'account' => ['account', '会員アカウント'],
            'other' => ['other', 'その他'],
        ];
    }

    #[DataProvider('inquiryTypeLabelProvider')]
    public function test_label_for_returns_expected_label(string $type, string $expected): void
    {
        $this->assertSame($expected, StoreContactRequest::labelFor($type));
    }

    public function test_label_for_throws_invalid_argument_exception_for_unknown_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported inquiry_type:');

        StoreContactRequest::labelFor('not_a_valid_type');
    }
}
