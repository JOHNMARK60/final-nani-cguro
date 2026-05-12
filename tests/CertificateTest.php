<?php

declare(strict_types=1);

use App\Models\Certificate;
use PHPUnit\Framework\TestCase;

final class CertificateTest extends TestCase
{
    public function testCertificateRequestCreation(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE certificate_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                certificate_type TEXT,
                full_name TEXT,
                birth_date TEXT,
                requester_location TEXT,
                delivery_option TEXT,
                notes TEXT,
                baptismal_file TEXT,
                id_file TEXT,
                status TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $certificates = new Certificate($pdo);
        $this->assertTrue($certificates->request([
            'user_id' => 1,
            'certificate_type' => 'Baptismal Certificate',
            'full_name' => 'Test Member',
            'birth_date' => '2000-01-01',
        ]));
        $this->assertSame(1, $certificates->countByStatus(null, 1));
    }
}
