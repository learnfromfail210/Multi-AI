<?php

declare(strict_types=1);

final class RateLimiter
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function check(
        int $userId,
        string $endpoint,
        int $maxRequests = 20,
        int $windowSeconds = 60
    ): bool {
        $now = new DateTimeImmutable('now');
        $windowStart = $now->modify(
            '-' . $windowSeconds . ' seconds'
        );

        $statement = $this->db->prepare(
            'SELECT id, request_count, window_started_at
             FROM api_rate_limits
             WHERE user_id = :user_id
               AND endpoint = :endpoint
             LIMIT 1'
        );

        $statement->execute([
            'user_id' => $userId,
            'endpoint' => $endpoint,
        ]);

        $record = $statement->fetch();

        if ($record === false) {
            $insert = $this->db->prepare(
                'INSERT INTO api_rate_limits
                    (user_id, endpoint, request_count, window_started_at)
                 VALUES
                    (:user_id, :endpoint, 1, :window_started_at)'
            );

            $insert->execute([
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'window_started_at' => $now->format('Y-m-d H:i:s'),
            ]);

            return true;
        }

        $recordWindow = new DateTimeImmutable(
            $record['window_started_at']
        );

        if ($recordWindow < $windowStart) {
            $update = $this->db->prepare(
                'UPDATE api_rate_limits
                 SET request_count = 1,
                     window_started_at = :window_started_at
                 WHERE id = :id'
            );

            $update->execute([
                'window_started_at' => $now->format('Y-m-d H:i:s'),
                'id' => (int) $record['id'],
            ]);

            return true;
        }

        if ((int) $record['request_count'] >= $maxRequests) {
            return false;
        }

        $update = $this->db->prepare(
            'UPDATE api_rate_limits
             SET request_count = request_count + 1
             WHERE id = :id'
        );

        $update->execute([
            'id' => (int) $record['id'],
        ]);

        return true;
    }
}