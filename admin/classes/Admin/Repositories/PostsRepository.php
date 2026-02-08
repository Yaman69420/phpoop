<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use Admin\Services\SlugService;
use PDO;

final class PostsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }

    // =========================================================================
    // LEES-ACTIES (Aangepast voor Soft Deletes)
    // =========================================================================

    public function getAll(): array
    {
        // AANGEPAST: published_at toegevoegd voor planningsfunctionaliteit
        $sql = "SELECT id, title, slug, content, status, featured_media_id, published_at, created_at, deleted_at
                FROM posts
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        // AANGEPAST: SEO velden toegevoegd (meta_title, meta_description)
        $sql = "SELECT id, title, slug, content, status, featured_media_id, published_at, meta_title, meta_description, created_at
                FROM posts
                WHERE id = :id AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    // NIEUW: Specifiek voor de prullenbak (alleen verwijderde items)
    public function getTrash(): array
    {
        $sql = "SELECT id, title, slug, content, status, created_at, deleted_at
                FROM posts
                WHERE deleted_at IS NOT NULL
                ORDER BY deleted_at DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // =========================================================================
    // PUBLIEKE METHODES (Voor de frontend, met slug & soft delete check)
    // =========================================================================

    public function findPublishedBySlug(string $slug): ?array
    {
        // AANGEPAST: LEFT JOIN met media voor featured images
        $sql = "SELECT p.*, m.filename, m.path, m.alt_text 
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.slug = :slug 
                AND p.status = 'published' 
                AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW())
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        return $post !== false ? $post : null;
    }

    /**
     * Haal de laatste gepubliceerde posts op (voor homepage)
     */
    public function getPublishedLatest(int $limit = 5): array
    {
        // AANGEPAST: LEFT JOIN met media voor featured images
        $sql = "SELECT p.*, m.filename, m.path, m.alt_text 
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.status = 'published' 
                AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW())
                ORDER BY p.created_at DESC 
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Haal alle gepubliceerde posts op (voor overzichtspagina)
     */
    public function getPublishedAll(): array
    {
        // AANGEPAST: LEFT JOIN met media voor featured images
        $sql = "SELECT p.*, m.filename, m.path, m.alt_text 
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.status = 'published' 
                AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW())
                ORDER BY p.created_at DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // =========================================================================
    // SCHRIJF-ACTIES
    // =========================================================================

    /**
     * AANGEPAST: SEO velden toegevoegd (meta_title, meta_description)
     */
    public function create(
        string $title, 
        string $content, 
        string $status, 
        ?int $featuredMediaId = null, 
        ?string $publishedAt = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null
    ): int {
        $slug = $this->generateUniqueSlug($title);

        $sql = "INSERT INTO posts (title, slug, content, status, featured_media_id, published_at, meta_title, meta_description, created_at)
                VALUES (:title, :slug, :content, :status, :featured_media_id, :published_at, :meta_title, :meta_description, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * AANGEPAST: SEO velden toegevoegd (meta_title, meta_description)
     */
    public function update(
        int $id, 
        string $title, 
        string $content, 
        string $status, 
        ?int $featuredMediaId = null, 
        ?string $publishedAt = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null
    ): void {
        $sql = "UPDATE posts
                SET title = :title,
                    content = :content,
                    status = :status,
                    featured_media_id = :featured_media_id,
                    published_at = :published_at,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ]);
    }

    // AANGEPAST: Soft Delete (Update i.p.v. Delete)
    public function delete(int $id): void
    {
        $sql = "UPDATE posts SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    // NIEUW: Restore (Herstellen uit prullenbak)
    public function restore(int $id): void
    {
        $sql = "UPDATE posts SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    // NIEUW: Force Delete (Echt weg, voor als je de prullenbak leegt)
    public function forceDelete(int $id): void
    {
        $sql = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    // =========================================================================
    // HULP METHODES
    // =========================================================================

    private function generateUniqueSlug(string $title): string
    {
        // Check of SlugService bestaat, anders fallback
        if (class_exists(SlugService::class)) {
            $slugService = new SlugService();
            $baseSlug = $slugService->slugify($title);
        } else {
            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }

        $slug = $baseSlug;
        $counter = 1;

        while ($this->findIdBySlug($slug) !== null) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function findIdBySlug(string $slug): ?int
    {
        $sql = "SELECT id FROM posts WHERE slug = :slug LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_COLUMN);

        return $row === false ? null : (int)$row;
    }
}