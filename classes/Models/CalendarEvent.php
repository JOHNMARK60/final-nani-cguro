<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class CalendarEvent extends BaseModel
{
    private const TYPES = ['Mass', 'Confession', 'Parish Event', 'Office Schedule', 'Other'];
    private const VISIBILITY = ['Public', 'Admin Only'];

    public function create(array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        $eventDate = trim((string) ($data['event_date'] ?? ''));

        if ($title === '' || $eventDate === '') {
            throw new InvalidArgumentException('Calendar title and date are required.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            throw new InvalidArgumentException('Use a valid calendar event date.');
        }

        $eventType = $this->allowed((string) ($data['event_type'] ?? 'Parish Event'), self::TYPES, 'Parish Event');
        $visibility = $this->allowed((string) ($data['visibility'] ?? 'Public'), self::VISIBILITY, 'Public');
        $eventTime = trim((string) ($data['event_time'] ?? '')) ?: null;

        if ($eventTime !== null && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $eventTime)) {
            throw new InvalidArgumentException('Use a valid calendar event time.');
        }

        $this->execute(
            'INSERT INTO parish_calendar_events
             (title, event_type, event_date, event_time, location, notes, visibility, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, "Scheduled", ?)',
            [
                $title,
                $eventType,
                $eventDate,
                $eventTime,
                trim((string) ($data['location'] ?? '')) ?: null,
                trim((string) ($data['notes'] ?? '')) ?: null,
                $visibility,
                $data['created_by'] ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function month(?string $month = null, bool $publicOnly = false, bool $includeCancelled = false): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $month) ? (string) $month : date('Y-m');
        $start = (new \DateTimeImmutable($month . '-01'))->format('Y-m-01');
        $end = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
        $params = [$start, $end];
        $where = 'WHERE event_date BETWEEN ? AND ?';

        if ($publicOnly) {
            $where .= ' AND visibility = "Public" AND status = "Scheduled"';
        } elseif (!$includeCancelled) {
            $where .= ' AND status = "Scheduled"';
        }

        return $this->fetchAll(
            'SELECT id, title, event_type, event_date AS appointment_date, event_time AS appointment_time,
                    location, notes, visibility, status, title AS appointment_type,
                    event_type AS member_name, "Calendar" AS source_type
             FROM parish_calendar_events
             ' . $where . '
             ORDER BY event_date ASC, event_time ASC, title ASC',
            $params
        );
    }

    public function cancel(int $id): bool
    {
        return $this->execute('UPDATE parish_calendar_events SET status = "Cancelled" WHERE id = ?', [$id]);
    }

    public function nextPublicByType(string $type): ?array
    {
        return $this->fetch(
            'SELECT id, title, event_type, event_date, event_time, location, notes
             FROM parish_calendar_events
             WHERE event_type = ?
                AND visibility = "Public"
                AND status = "Scheduled"
                AND (
                    event_date > CURDATE()
                    OR (event_date = CURDATE() AND (event_time IS NULL OR event_time >= CURTIME()))
                )
             ORDER BY event_date ASC, event_time IS NULL ASC, event_time ASC, title ASC
             LIMIT 1',
            [trim($type)]
        );
    }

    private function allowed(string $value, array $allowed, string $fallback): string
    {
        $value = trim($value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
