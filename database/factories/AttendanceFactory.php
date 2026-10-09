<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = now()->format('Y-m-d');

        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => $date,
            'check_in' => $date.' 08:15:00',
            'check_out' => $date.' 17:05:00',
            'latitude' => -6.2088000,
            'longitude' => 106.8456000,
            'location_status' => 'verified',
            'face_status' => 'verified',
            'wifi_verified' => true,
            'wifi_ssid' => 'Aranus_HQ_WiFi',
            'ip_address' => '192.168.1.45',
            'photo_path' => null,
            'notes' => null,
        ];
    }

    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $date = $attributes['attendance_date'] ?? now()->format('Y-m-d');

            return [
                'check_in' => $date.' 09:15:00',
                'notes' => 'Terlambat karena kendala lalu lintas',
            ];
        });
    }

    public function wfh(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_status' => 'wfh',
            'wifi_verified' => false,
            'wifi_ssid' => null,
            'notes' => 'Work From Home (WFH disetujui)',
        ]);
    }

    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_path' => 'attendances/test_photo.jpg',
        ]);
    }
}
