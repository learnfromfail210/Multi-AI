<?php

declare(strict_types=1);

final class Conversation
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        int $userId,
        string $title = 'New Conversation'
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO conversations (user_id, title)
             VALUES (:user_id, :title)'
        );

        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function find(
        int $conversationId,
        int $userId
    ): ?array {
        $statement = $this->db->prepare(
            'SELECT id, user_id, title, created_at, updated_at
             FROM conversations
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $conversationId,
            'user_id' => $userId,
        ]);

        $conversation = $statement->fetch();

        return $conversation ?: null;
    }

    public function allForUser(
        int $userId
    ): array {
        $statement = $this->db->prepare(
            'SELECT id, title, created_at, updated_at
             FROM conversations
             WHERE user_id = :user_id
             ORDER BY updated_at DESC'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public function rename(
        int $conversationId,
        int $userId,
        string $title
    ): bool {
        $statement = $this->db->prepare(
            'UPDATE conversations
             SET title = :title
             WHERE id = :id AND user_id = :user_id'
        );

        return $statement->execute([
            'title' => $title,
            'id' => $conversationId,
            'user_id' => $userId,
        ]);
    }

    public function delete(
        int $conversationId,
        int $userId
    ): bool {
        $statement = $this->db->prepare(
            'DELETE FROM conversations
             WHERE id = :id AND user_id = :user_id'
        );

        return $statement->execute([
            'id' => $conversationId,
            'user_id' => $userId,
        ]);
    }
}
