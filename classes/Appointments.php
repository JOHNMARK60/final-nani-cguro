<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

class Appointments
{
    private App\Models\Appointment $appointments;

    public function __construct(PDO $pdo)
    {
        $this->appointments = new App\Models\Appointment($pdo);
    }

    public function create($userId, $type, $date, $time, $notes): bool
    {
        return $this->appointments->create((int) $userId, $type, $date, $time, $notes);
    }

    public function getAll(): array
    {
        return $this->appointments->all();
    }

    public function getByUserId($userId): array
    {
        return $this->appointments->forUser((int) $userId);
    }

    public function getById($id): ?array
    {
        return $this->appointments->find((int) $id);
    }

    public function update($id, $type, $date, $time, $notes, ?int $userId = null): bool
    {
        $userId ??= (int) ($_SESSION['user_id'] ?? 0);

        return $this->appointments->update((int) $id, $userId, $type, $date, $time, $notes);
    }

    public function updateStatus($id, $status): bool
    {
        return $this->appointments->updateStatus((int) $id, $status);
    }

    public function delete($id, ?int $userId = null): bool
    {
        $userId ??= (int) ($_SESSION['user_id'] ?? 0);

        return $this->appointments->delete((int) $id, $userId);
    }
}
