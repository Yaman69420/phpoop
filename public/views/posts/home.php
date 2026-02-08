<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

ob_start();

?>

    <header class="mb-8">
        <h1 class="text-3xl font-semibold">Laatste posts</h1>
        <p class="text-slate-300 mt-2">Recente gepubliceerde posts.</p>
    </header>

<?php
if (empty($posts)): ?>
    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-slate-300">
        Nog geen gepubliceerde posts.
    </div>
<?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php
        foreach ($posts as $post): ?>
            <article class="rounded-xl border border-white/10 bg-white/5 overflow-hidden flex flex-col">

                <?php if (!empty($post['filename'])): ?>
                    <a href="/posts/<?= htmlspecialchars($post['slug']) ?>" class="block w-full h-48 bg-slate-800">
                        <img
                                src="/<?= htmlspecialchars($post['path'] . '/' . $post['filename']) ?>"
                                alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>"
                                class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                        >
                    </a>
                <?php endif; ?>

                <div class="p-6 flex flex-col flex-1">
                    <h2 class="text-xl font-semibold text-white">
                        <a href="/posts/<?= htmlspecialchars($post['slug']) ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h2>

                    <p class="text-sm text-slate-400 mt-2">
                        <?= htmlspecialchars($post['created_at'] ?? '') ?>
                    </p>

                    <p class="text-slate-300 mt-4 flex-1">
                        <?php
                        $cleanText = strip_tags((string)$post['content']);
                        echo htmlspecialchars(mb_strimwidth($cleanText, 0, 140, '...'));
                        ?>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean(); // buffer -> string
$title = 'Home';
require __DIR__ . '/../layouts/public.php';
