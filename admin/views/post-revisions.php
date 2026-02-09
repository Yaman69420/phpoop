<?php
declare(strict_types=1);

/**
 * View: Overzicht van revisies voor een post
 */
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Revisies: <?= htmlspecialchars($post['title']) ?></h2>
            <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= $post['id'] ?>/edit" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                Terug naar bewerken
            </a>
        </div>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Revisie #</th>
                    <th>Datum</th>
                    <th>Status</th>
                    <th class="text-right">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revisions)): ?>
                    <tr>
                        <td colspan="4" class="py-4 text-center text-gray-500">
                            Geen revisies gevonden.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($revisions as $rev): ?>
                        <tr class="border-b">
                            <td class="py-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Versie <?= (int)$rev['revision_number'] ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($rev['created_at']) ?>
                            </td>
                            <td>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $rev['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= htmlspecialchars($rev['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= $post['id'] ?>/revisions/<?= $rev['id'] ?>" 
                                   class="underline text-blue-600 hover:text-blue-800">
                                    Bekijken
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
