<?php
declare(strict_types=1);

namespace Admin\Services;

use Admin\Core\Database;
use PDO;

/**
 * LockService
 * 
 * Handles editorial locking for posts.
 * Prevents multiple admins from editing the same post simultaneously.
 */
final class LockService
{
    private PDO $pdo;
    
    /** Lock timeout in minutes */
    private const LOCK_TIMEOUT_MINUTES = 15;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Acquire a lock on a post for the given user.
     * If user already has the lock, refresh the timestamp.
     */
    public function acquireLock(int $postId, int $userId): bool
    {
        $sql = "UPDATE posts 
                SET locked_by = :user_id, locked_at = NOW() 
                WHERE id = :post_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);
    }

    /**
     * Release the lock on a post.
     */
    public function releaseLock(int $postId): void
    {
        $sql = "UPDATE posts 
                SET locked_by = NULL, locked_at = NULL 
                WHERE id = :post_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
    }

    /**
     * Get lock information for a post.
     * Returns null if post is not locked.
     */
    public function getLockInfo(int $postId): ?array
    {
        // Calculate remaining minutes in SQL to avoid timezone issues
        $sql = "SELECT p.locked_by, p.locked_at, u.name as locked_by_name,
                       GREATEST(0, :timeout - TIMESTAMPDIFF(MINUTE, p.locked_at, NOW())) as remaining_minutes
                FROM posts p
                LEFT JOIN users u ON p.locked_by = u.id
                WHERE p.id = :post_id AND p.locked_by IS NOT NULL
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'timeout' => self::LOCK_TIMEOUT_MINUTES,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false ? $result : null;
    }

    /**
     * Check if post is locked by another user (not the given user).
     * Also handles expired locks by releasing them.
     */
    public function isLockedByOther(int $postId, int $userId): bool
    {
        $lock = $this->getLockInfo($postId);
        
        if ($lock === null) {
            return false; // Not locked
        }
        
        if ((int)$lock['locked_by'] === $userId) {
            return false; // Locked by same user
        }
        
        // Locked by someone else - check if expired
        if ($this->isExpired($lock)) {
            $this->releaseLock($postId);
            return false; // Was expired, now released
        }
        
        return true; // Locked by another user, not expired
    }

    /**
     * Check if the current user has the lock on this post.
     */
    public function isLockedByUser(int $postId, int $userId): bool
    {
        $lock = $this->getLockInfo($postId);
        
        if ($lock === null) {
            return false;
        }
        
        return (int)$lock['locked_by'] === $userId;
    }

    /**
     * Check if a lock has expired based on LOCK_TIMEOUT_MINUTES.
     */
    public function isExpired(array $lock): bool
    {
        if (empty($lock['locked_at'])) {
            return true;
        }
        
        $lockedAt = new \DateTime($lock['locked_at']);
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $lockedAt->getTimestamp();
        
        return $diff > (self::LOCK_TIMEOUT_MINUTES * 60);
    }

    /**
     * Get the name of the user who has the lock.
     */
    public function getLockedByName(int $postId): ?string
    {
        $lock = $this->getLockInfo($postId);
        return $lock['locked_by_name'] ?? null;
    }

    /**
     * Get remaining lock time in minutes.
     */
    public function getRemainingMinutes(array $lock): int
    {
        // Use pre-calculated value from SQL if available
        if (isset($lock['remaining_minutes'])) {
            return (int)$lock['remaining_minutes'];
        }
        
        // Fallback calculation
        if (empty($lock['locked_at'])) {
            return 0;
        }
        
        return self::LOCK_TIMEOUT_MINUTES;
    }
}
