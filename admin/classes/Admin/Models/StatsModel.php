<?php
declare(strict_types=1);

namespace Admin\Models;

use Admin\Core\Database;
use PDO;

class StatsModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * getStats()
     *
     * Doel:
     * Levert statistieken voor het dashboard uit de database.
     */
    public function getStats(): array
    {
        return [
            'posts' => $this->countPosts(),
            'users' => $this->count('users'),
            'media' => $this->count('media'),
        ];
    }

    private function countPosts(): int
    {
        // Tel alleen posts die NIET in de prullenbak zitten
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL");
        return (int)$stmt->fetchColumn();
    }

    private function count(string $table): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM " . $table);
        return (int)$stmt->fetchColumn();
    }
}
