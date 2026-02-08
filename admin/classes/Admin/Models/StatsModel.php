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
            'posts' => $this->count('posts'),
            'users' => $this->count('users'),
            'media' => $this->count('media'),
        ];
    }

    private function count(string $table): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM " . $table);
        return (int)$stmt->fetchColumn();
    }
}
