<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use PDO;

/**
 * RevisionRepository
 * 
 * CRUD operaties voor post revisies.
 */
final class RevisionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }

    /**
     * Maak een nieuwe revisie aan
     */
    public function create(int $postId, string $title, string $content, string $status, int $revisionNumber): int
    {
        $sql = "INSERT INTO post_revisions (post_id, title, content, status, revision_number, created_at)
                VALUES (:post_id, :title, :content, :status, :revision_number, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'revision_number' => $revisionNumber,
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Haal alle revisies op voor een post
     */
    public function getByPostId(int $postId): array
    {
        $sql = "SELECT id, post_id, title, content, status, revision_number, created_at
                FROM post_revisions
                WHERE post_id = :post_id
                ORDER BY revision_number DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Haal een specifieke revisie op
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT id, post_id, title, content, status, revision_number, created_at
                FROM post_revisions
                WHERE id = :id
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row !== false ? $row : null;
    }

    /**
     * Tel het aantal revisies voor een post
     */
    public function countByPostId(int $postId): int
    {
        $sql = "SELECT COUNT(*) FROM post_revisions WHERE post_id = :post_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Haal de oudste revisie op voor een post
     */
    public function getOldest(int $postId): ?array
    {
        $sql = "SELECT id, post_id, title, content, status, revision_number, created_at
                FROM post_revisions
                WHERE post_id = :post_id
                ORDER BY revision_number ASC
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row !== false ? $row : null;
    }

    /**
     * Haal het hoogste revisienummer op
     */
    public function getMaxRevisionNumber(int $postId): int
    {
        $sql = "SELECT COALESCE(MAX(revision_number), 0) FROM post_revisions WHERE post_id = :post_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Verwijder een revisie
     */
    public function delete(int $id): void
    {
        $sql = "DELETE FROM post_revisions WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
}
