<?php

declare(strict_types=1);

final class Message
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        int $conversationId,
        string $role,
        string $content,
        ?string $model = null
    ): int {
        $allowedRoles = [
            'system',
            'user',
            'assistant',
        ];

        if (!in_array($role, $allowedRoles, true)) {
            throw new InvalidArgumentException(
                'Invalid message role.'
            );
        }

        $statement = $this->db->prepare(
            'INSERT INTO messages
                (conversation_id, role, content, model)
             VALUES
                (:conversation_id, :role, :content, :model)'
        );

        $statement->execute([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'model' => $model,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function allForConversation(
        int $conversationId
    ): array {
        $statement = $this->db->prepare(
            'SELECT id, role, content, model, created_at
             FROM messages
             WHERE conversation_id = :conversation_id
             ORDER BY created_at ASC, id ASC'
        );

        $statement->execute([
            'conversation_id' => $conversationId,
        ]);

        return $statement->fetchAll();
    }
}
