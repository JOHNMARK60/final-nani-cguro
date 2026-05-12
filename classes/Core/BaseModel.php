<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class BaseModel
{
    public function __construct(protected PDO $db)
    {
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }
}
