<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\Csrf;
use Admin\Core\Flash;
use Admin\Core\View;
use Admin\Repositories\MediaRepository;
use Admin\Repositories\PostsRepository;
use Admin\Services\LockService;
use Admin\Services\RevisionService;

final class PostsController
{
    private PostsRepository $posts;

    public function __construct(PostsRepository $posts)
    {
        $this->posts = $posts;
    }

    public function index(): void
    {
        View::render('posts.php', [
            'title' => 'Posts',
            'posts' => $this->posts->getAll(),
        ]);
    }
// NIEUW: Toon de prullenbak pagina
    public function trash(): void
    {
        // Haal de verwijderde posts op via de repository
        $deletedPosts = $this->posts->getTrash();

        View::render('posts-trash.php', [
            'title' => 'Prullenbak',
            'posts' => $deletedPosts,
        ]);
    }

    // NIEUW: Herstel een verwijderde post
    public function restore(int $id): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        // Roep de restore functie in de repository aan
        $this->posts->restore($id);

        Flash::set('success', 'Post succesvol hersteld.');

        // Stuur terug naar de prullenbak
        header('Location: ' . ADMIN_BASE_PATH . '/posts/trash');
        exit;
    }
    public function create(): void
    {
        $old = Flash::get('old');
        if (!is_array($old)) {
            // AANGEPAST: SEO velden toegevoegd aan defaults
            $old = [
                'title' => '', 
                'content' => '', 
                'status' => 'draft', 
                'featured_media_id' => '', 
                'published_at' => '',
                'meta_title' => '',
                'meta_description' => '',
            ];
        }

        View::render('post-create.php', [
            'title' => 'Nieuwe post',
            'old' => $old,
            'media' => MediaRepository::make()->getAllImages(),
        ]);
    }

    public function store(): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $title   = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status  = (string)($_POST['status'] ?? 'draft');
        $featuredRaw = trim((string)($_POST['featured_media_id'] ?? ''));
        $publishedAtRaw = trim((string)($_POST['published_at'] ?? ''));
        $metaTitle = trim((string)($_POST['meta_title'] ?? '')) ?: null;
        $metaDescription = trim((string)($_POST['meta_description'] ?? '')) ?: null;

        $featuredId = $this->normalizeFeaturedId($featuredRaw);
        $publishedAt = $this->normalizePublishedAt($publishedAtRaw);

        $errors = $this->validate($title, $content, $status, $featuredId);

        if (!empty($errors)) {
            Flash::set('warning', $errors);
            Flash::set('old', compact('title', 'content', 'status') + [
                'featured_media_id' => $featuredRaw, 
                'published_at' => $publishedAtRaw,
                'meta_title' => $metaTitle ?? '',
                'meta_description' => $metaDescription ?? '',
            ]);
            header('Location: ' . ADMIN_BASE_PATH . '/posts/create');
            exit;
        }

        // AANGEPAST: SEO velden meegeven aan repository
        $this->posts->create($title, $content, $status, $featuredId, $publishedAt, $metaTitle, $metaDescription);

        Flash::set('success', 'Post succesvol aangemaakt.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function edit(int $id): void
    {
        $post = $this->posts->find($id);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        // EDITORIAL LOCKING: Check en plaats lock
        $lockService = new LockService();
        $currentUserId = (int)$_SESSION['user_id'];

        // Check of post gelocked is door een andere admin
        if ($lockService->isLockedByOther($id, $currentUserId)) {
            $lockedByName = $lockService->getLockedByName($id);
            Flash::set('error', 'Deze post wordt momenteel bewerkt door ' . htmlspecialchars($lockedByName ?? 'een andere admin') . '.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        // Plaats of ververs de lock voor huidige admin
        $lockService->acquireLock($id, $currentUserId);

        $old = Flash::get('old');
        if (!is_array($old)) {
            $old = [
                'title' => (string)$post['title'],
                'content' => (string)$post['content'],
                'status' => (string)$post['status'],
                'featured_media_id' => (string)($post['featured_media_id'] ?? ''),
                'published_at' => $this->formatDateTimeLocal($post['published_at'] ?? null),
                // NIEUW: SEO velden
                'meta_title' => (string)($post['meta_title'] ?? ''),
                'meta_description' => (string)($post['meta_description'] ?? ''),
            ];
        }

        // Haal lock info op voor de view
        $lockInfo = $lockService->getLockInfo($id);
        $lockRemainingMinutes = $lockInfo ? $lockService->getRemainingMinutes($lockInfo) : 0;
        $lockToken = $lockInfo['locked_at'] ?? null;

        View::render('post-edit.php', [
            'title' => 'Post bewerken',
            'postId' => $id,
            'post' => $post,
            'old' => $old,
            'media' => MediaRepository::make()->getAllImages(),
            'lockRemainingMinutes' => $lockRemainingMinutes,
            'lockToken' => $lockToken,
        ]);
    }

    public function update(int $id): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $post = $this->posts->find($id);
        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        // EDITORIAL LOCKING: Controleer of huidige admin de lock heeft
        $lockService = new LockService();
        $currentUserId = (int)$_SESSION['user_id'];

        if (!$lockService->isLockedByUser($id, $currentUserId)) {
            Flash::set('error', 'Je kunt deze post niet opslaan. De lock is verlopen of je hebt geen lock.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        // REFRESH DETECTIE: Controleer of lock token matcht (voorkomt dubbele tabs/refresh)
        $formLockToken = trim((string)($_POST['lock_token'] ?? ''));
        $lockInfo = $lockService->getLockInfo($id);
        $dbLockToken = $lockInfo['locked_at'] ?? '';

        if ($formLockToken !== $dbLockToken) {
            Flash::set('error', 'Deze bewerkingssessie is verlopen (pagina was ververst). Open de post opnieuw.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
            exit;
        }

        $title   = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status  = (string)($_POST['status'] ?? 'draft');
        $featuredRaw = trim((string)($_POST['featured_media_id'] ?? ''));
        $publishedAtRaw = trim((string)($_POST['published_at'] ?? ''));
        // NIEUW: SEO velden ophalen
        $metaTitle = trim((string)($_POST['meta_title'] ?? '')) ?: null;
        $metaDescription = trim((string)($_POST['meta_description'] ?? '')) ?: null;

        $featuredId = $this->normalizeFeaturedId($featuredRaw);
        $publishedAt = $this->normalizePublishedAt($publishedAtRaw);

        $errors = $this->validate($title, $content, $status, $featuredId);

        if (!empty($errors)) {
            Flash::set('warning', $errors);
            Flash::set('old', compact('title', 'content', 'status') + [
                'featured_media_id' => $featuredRaw, 
                'published_at' => $publishedAtRaw,
                'meta_title' => $metaTitle ?? '',
                'meta_description' => $metaDescription ?? '',
            ]);
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
            exit;
        }

        // REVISIES: Sla huidige post op als revisie voordat we updaten
        $revisionService = new RevisionService();
        $revisionService->createRevision($id, $post);

        // AANGEPAST: SEO velden meegeven aan repository
        $this->posts->update($id, $title, $content, $status, $featuredId, $publishedAt, $metaTitle, $metaDescription);

        // EDITORIAL LOCKING: Lock vrijgeven na succesvol opslaan
        $lockService->releaseLock($id);

        Flash::set('success', 'Post succesvol aangepast.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function deleteConfirm(int $id): void
    {
        $post = $this->posts->find($id);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        View::render('post-delete.php', [
            'title' => 'Post verwijderen',
            'post' => $post,
        ]);
    }

    // NIEUW: Delete actie verplaatst naar controller
    public function delete(int $id): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $this->posts->delete($id);

        Flash::set('success', 'Post verwijderd.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function show(int $id): void
    {
        $post = $this->posts->find($id);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        View::render('post-show.php', [
            'title' => 'Post bekijken',
            'post' => $post,
        ]);
    }

    // NIEUW: Toon revisies van een post
    public function revisions(int $id): void
    {
        $post = $this->posts->find($id);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $revisionService = new RevisionService();
        $revisions = $revisionService->getRevisionsForPost($id);

        View::render('post-revisions.php', [
            'title' => 'Revisies: ' . $post['title'],
            'post' => $post,
            'revisions' => $revisions,
        ]);
    }

    // NIEUW: Bekijk een specifieke revisie
    public function viewRevision(int $id, int $revisionId): void
    {
        $post = $this->posts->find($id);
        
        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $revisionService = new RevisionService();
        $revision = $revisionService->getRevision($revisionId);

        if (!$revision || (int)$revision['post_id'] !== $id) {
            Flash::set('error', 'Revisie niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/revisions');
            exit;
        }

        View::render('post-revision-view.php', [
            'title' => 'Revisie bekijken: ' . $post['title'],
            'post' => $post,
            'revision' => $revision,
        ]);
    }

    // NIEUW: Herstel een revisie (slug blijft behouden)
    public function restoreRevision(int $id, int $revisionId): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $revisionService = new RevisionService();
        
        if ($revisionService->restoreRevision($revisionId)) {
            Flash::set('success', 'Post hersteld naar revisie.');
        } else {
            Flash::set('error', 'Kon revisie niet herstellen.');
        }

        header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
        exit;
    }

    private function normalizeFeaturedId(string $raw): ?int
    {
        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }
        $id = (int)$raw;
        return $id > 0 ? $id : null;
    }

    private function validate(string $title, string $content, string $status, ?int $featuredId): array
    {
        $errors = [];

        if ($title === '') {
            $errors[] = 'Titel is verplicht.';
        } elseif (mb_strlen($title) < 3) {
            $errors[] = 'Titel moet minstens 3 tekens bevatten.';
        }

        if ($content === '') {
            $errors[] = 'Inhoud is verplicht.';
        } elseif (mb_strlen($content) < 10) {
            $errors[] = 'Inhoud moet minstens 10 tekens bevatten.';
        }

        if (!in_array($status, ['draft', 'published'], true)) {
            $errors[] = 'Status moet draft of published zijn.';
        }

        if ($featuredId !== null && MediaRepository::make()->findImageById($featuredId) === null) {
            $errors[] = 'Featured image is ongeldig.';
        }

        return $errors;
    }

    /**
     * NIEUW: Normaliseer published_at input van datetime-local naar MySQL formaat
     * Input: "2024-02-07T14:30" (van datetime-local)
     * Output: "2024-02-07 14:30:00" (voor MySQL) of NULL
     */
    private function normalizePublishedAt(string $raw): ?string
    {
        if ($raw === '') {
            return null; // Geen datum = direct publiceren
        }
        
        // datetime-local format: "2024-02-07T14:30"
        // MySQL format: "2024-02-07 14:30:00"
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i', $raw);
        if ($datetime === false) {
            return null; // Ongeldige datum
        }
        
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * NIEUW: Formatteer MySQL datetime naar datetime-local formaat
     * Input: "2024-02-07 14:30:00" (van database)
     * Output: "2024-02-07T14:30" (voor datetime-local input)
     */
    private function formatDateTimeLocal(?string $mysqlDate): string
    {
        if ($mysqlDate === null || $mysqlDate === '') {
            return '';
        }
        
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $mysqlDate);
        if ($datetime === false) {
            return '';
        }
        
        return $datetime->format('Y-m-d\TH:i');
    }
}
