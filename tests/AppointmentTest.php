<?php

declare(strict_types=1);

use App\Models\Appointment;
use PHPUnit\Framework\TestCase;

final class AppointmentTest extends TestCase
{
    public function testAppointmentCreation(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE appointment_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                is_active INTEGER
            )'
        );
        $pdo->exec("INSERT INTO appointment_types (name, is_active) VALUES ('Mass Intentions', 1)");
        $pdo->exec(
            'CREATE TABLE appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                appointment_type_id INTEGER,
                appointment_date TEXT,
                appointment_time TEXT,
                notes TEXT,
                status TEXT
            )'
        );

        $appointments = new Appointment($pdo);
        $this->assertTrue($appointments->create(1, 'Mass Intentions', '2026-05-20', '10:00', 'Test'));
        $this->assertCount(1, $appointments->forUser(1));
    }
}
