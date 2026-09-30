<?php

namespace Tests\Unit;

use App\Support\UserPhone;
use PHPUnit\Framework\TestCase;

class UserPhoneTest extends TestCase
{
    public function test_normalize_membuang_nol_di_depan_dan_non_digit(): void
    {
        $this->assertSame('81234567890', UserPhone::normalize('0812-3456-7890'));
    }

    public function test_normalize_membuang_kode_negara_62(): void
    {
        $this->assertSame('81234567890', UserPhone::normalize('+62 812 3456 7890'));
        $this->assertSame('81234567890', UserPhone::normalize('6281234567890'));
    }

    public function test_normalize_string_kosong_tetap_kosong(): void
    {
        $this->assertSame('', UserPhone::normalize(''));
        $this->assertSame('', UserPhone::normalize(null));
    }

    public function test_is_valid_menerima_nomor_diawali_8(): void
    {
        $this->assertTrue(UserPhone::isValid('81234567890'));
        $this->assertTrue(UserPhone::isValid('85212314123'));
    }

    public function test_is_valid_menolak_awalan_0_62_atau_plus62(): void
    {
        $this->assertFalse(UserPhone::isValid('081234567890'));
        $this->assertFalse(UserPhone::isValid('6281234567890'));
        $this->assertFalse(UserPhone::isValid('+6281234567890'));
    }

    public function test_is_valid_menolak_bukan_awalan_8_atau_terlalu_pendek(): void
    {
        $this->assertFalse(UserPhone::isValid(''));
        $this->assertFalse(UserPhone::isValid('123456'));
        $this->assertFalse(UserPhone::isValid('712345678'));
        $this->assertFalse(UserPhone::isValid('81234'));
    }
}
