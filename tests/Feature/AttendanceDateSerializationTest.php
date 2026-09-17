<?php

namespace Tests\Feature;

use App\Models\Attendance;
use Tests\TestCase;

class AttendanceDateSerializationTest extends TestCase
{
    public function test_attendance_date_serializes_as_plain_date_string(): void
    {
        $attendance = new Attendance([
            'user_id' => 1,
            'date' => '2026-09-03',
            'check_in_time' => '08:00:00',
            'check_out_time' => '16:00:00',
            'status' => 'hadir',
        ]);

        $this->assertSame('2026-09-03', $attendance->toArray()['date']);
    }
}
