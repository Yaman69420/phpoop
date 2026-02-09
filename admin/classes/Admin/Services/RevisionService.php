<?php
declare(strict_types=1);

namespace Admin\Services;

use Admin\Repositories\RevisionRepository;
use Admin\Repositories\PostsRepository;

/**
 * RevisionService
 * 
 * Businesslogica voor revisiebeheer.
 * Handelt max revisies af en herstel functionaliteit.
 */
final class RevisionService
{
    private const MAX_REVISIONS = 3;
    
    private RevisionRepository $revisions;
    private PostsRepository $posts;

    public function __construct(?RevisionRepository $revisions = null, ?PostsRepository $posts = null)
    {
        $this->revisions = $revisions ?? RevisionRepository::make();
        $this->posts = $posts ?? PostsRepository::make();
    }

    /**
     * Maak een revisie van de huidige post staat VOORDAT deze wordt geupdate
     */
    public function createRevision(int $postId, array $currentPostData): void
    {
        // Haal het volgende revisienummer op
        $nextNumber = $this->revisions->getMaxRevisionNumber($postId) + 1;
        
        // Sla huidige staat op als revisie
        $this->revisions->create(
            $postId,
            (string)($currentPostData['title'] ?? ''),
            (string)($currentPostData['content'] ?? ''),
            (string)($currentPostData['status'] ?? 'draft'),
            $nextNumber
        );
        
        // Verwijder oudste als we over het maximum gaan
        $this->enforceMaxRevisions($postId);
    }

    /**
     * Zorg dat er maximaal MAX_REVISIONS revisies zijn
     */
    private function enforceMaxRevisions(int $postId): void
    {
        $count = $this->revisions->countByPostId($postId);
        
        while ($count > self::MAX_REVISIONS) {
            $oldest = $this->revisions->getOldest($postId);
            if ($oldest) {
                $this->revisions->delete((int)$oldest['id']);
            }
            $count--;
        }
    }

    /**
     * Herstel een post naar een eerdere revisie
     * De slug blijft ongewijzigd!
     */
    public function restoreRevision(int $revisionId): bool
    {
        $revision = $this->revisions->find($revisionId);
        
        if ($revision === null) {
            return false;
        }
        
        $postId = (int)$revision['post_id'];
        $post = $this->posts->find($postId);
        
        if ($post === null) {
            return false;
        }
        
        // Sla huidige staat eerst op als revisie voordat we herstellen
        $this->createRevision($postId, $post);
        
        // Herstel de post met revisie data (slug blijft ongewijzigd!)
        $this->posts->update(
            $postId,
            (string)$revision['title'],
            (string)$revision['content'],
            (string)$revision['status'],
            $post['featured_media_id'] ? (int)$post['featured_media_id'] : null,
            $post['published_at'] ?? null,
            $post['meta_title'] ?? null,
            $post['meta_description'] ?? null
        );
        
        return true;
    }

    /**
     * Haal alle revisies op voor een post
     */
    public function getRevisionsForPost(int $postId): array
    {
        return $this->revisions->getByPostId($postId);
    }

    /**
     * Haal een specifieke revisie op
     */
    public function getRevision(int $revisionId): ?array
    {
        return $this->revisions->find($revisionId);
    }
}
