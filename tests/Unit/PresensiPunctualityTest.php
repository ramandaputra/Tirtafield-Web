<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Presensi;

class PresensiPunctualityTest extends TestCase
{
    /**
     * Test Senin masuk: jadwal 07:45 + toleransi 20 menit = batas 08:05 WIB
     */
    public function test_monday_punctuality_criteria(): void
    {
        // 2026-11-09 is a Monday (ISO day 1)
        // 08:00 WIB is within 08:05 WIB limit -> Tepat Waktu
        $onTime = Presensi::evaluatePunctuality('2026-11-09 08:00:00', 'masuk');
        $this->assertFalse($onTime['is_late']);
        $this->assertEquals('Tepat Waktu', $onTime['label']);

        // 08:05 WIB is exact limit -> Tepat Waktu
        $exactLimit = Presensi::evaluatePunctuality('2026-11-09 08:05:00', 'masuk');
        $this->assertFalse($exactLimit['is_late']);
        $this->assertEquals('Tepat Waktu', $exactLimit['label']);

        // 08:06 WIB exceeds 08:05 WIB -> Terlambat 1 Menit
        $late = Presensi::evaluatePunctuality('2026-11-09 08:06:00', 'masuk');
        $this->assertTrue($late['is_late']);
        $this->assertEquals('Terlambat (1 Menit)', $late['label']);
        $this->assertEquals(1, $late['late_minutes']);
    }

    /**
     * Test Selasa-Jumat masuk: jadwal 08:00 + toleransi 15 menit = batas 08:15 WIB
     */
    public function test_regular_day_punctuality_criteria(): void
    {
        // 2026-11-10 is a Tuesday (ISO day 2)
        // 08:14 WIB is within 08:15 WIB limit -> Tepat Waktu
        $onTime = Presensi::evaluatePunctuality('2026-11-10 08:14:00', 'masuk');
        $this->assertFalse($onTime['is_late']);
        $this->assertEquals('Tepat Waktu', $onTime['label']);

        // 08:25 WIB exceeds 08:15 WIB -> Terlambat 10 Menit
        $late = Presensi::evaluatePunctuality('2026-11-10 08:25:00', 'masuk');
        $this->assertTrue($late['is_late']);
        $this->assertEquals('Terlambat (10 Menit)', $late['label']);
        $this->assertEquals(10, $late['late_minutes']);
    }

    /**
     * Test Istirahat Masuk: jadwal 13:00 + toleransi 15 menit = batas 13:15 WIB
     */
    public function test_break_checkin_punctuality_criteria(): void
    {
        // 13:10 WIB -> Tepat Waktu
        $onTime = Presensi::evaluatePunctuality('2026-11-10 13:10:00', 'istirahatMasuk');
        $this->assertFalse($onTime['is_late']);
        $this->assertEquals('Tepat Waktu', $onTime['label']);

        // 13:20 WIB exceeds 13:15 WIB -> Terlambat 5 Menit
        $late = Presensi::evaluatePunctuality('2026-11-10 13:20:00', 'istirahatMasuk');
        $this->assertTrue($late['is_late']);
        $this->assertEquals('Terlambat (5 Menit)', $late['label']);
        $this->assertEquals(5, $late['late_minutes']);
    }

    /**
     * Test catatan terstruktur [TERLAMBAT (XX Menit): Alasan]
     */
    public function test_structured_notes_punctuality(): void
    {
        $res = Presensi::evaluatePunctuality(
            '2026-11-10 08:10:00',
            'masuk',
            '[TERLAMBAT (25 Menit): Kendala lalu lintas dan hujan deras]'
        );
        $this->assertTrue($res['is_late']);
        $this->assertEquals('Terlambat (25 Menit)', $res['label']);
        $this->assertEquals(25, $res['late_minutes']);
    }
}

