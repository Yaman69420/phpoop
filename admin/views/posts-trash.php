<?php
declare(strict_types=1);

use Admin\Core\Csrf;

?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-red-600">Prullenbak</h2>

            <a class="underline" href="<?= ADMIN_BASE_PATH ?>/posts">
                ← Terug naar posts
            </a>
        </div>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <?php if (empty($posts)): ?>
            <p class="text-gray-600">De prullenbak is leeg. Goed bezig!</p>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Titel</th>
                    <th>Verwijderd op</th>
                    <th class="text-right">Acties</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr class="border-b hover:bg-red-50">
                        <td class="py-2 font-medium">
                            <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?>
                        </td>
                        <td class="text-gray-500">
                            <?php echo htmlspecialchars((string)$post['deleted_at'], ENT_QUOTES); ?>
                        </td>
                        <td class="text-right">
                            <form method="post" action="<?= ADMIN_BASE_PATH ?>/posts/<?php echo (int)$post['id']; ?>/restore">
                                <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

                                <button type="submit" class="text-green-600 hover:underline font-bold">
                                    Herstellen ♻️
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>