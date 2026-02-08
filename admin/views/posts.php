<?php
declare(strict_types=1);

use Admin\Core\Auth;

?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Posts overzicht</h2>

            <div class="space-x-4">
                <a class="text-gray-500 hover:text-red-600 hover:underline text-sm" href="<?= ADMIN_BASE_PATH ?>/posts/trash">
                    🗑️ Prullenbak
                </a>
                <a class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" href="<?= ADMIN_BASE_PATH ?>/posts/create">
                    + Nieuwe post
                </a>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Titel</th>
                <th>Datum</th>
                <th>Status</th>
                <th class="text-right">Acties</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($posts as $post): ?>
                <tr class="border-b">
                    <td class="py-2">
                        <a class="underline" href="/admin/posts/<?php echo (int)$post['id']; ?>">
                            <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        // NIEUW: Toon published_at als ingesteld, anders created_at
                        $publishedAt = $post['published_at'] ?? null;
                        $createdAt = (string)$post['created_at'];
                        
                        if ($publishedAt !== null) {
                            $isScheduled = strtotime($publishedAt) > time();
                            if ($isScheduled) {
                                echo '<span class="text-orange-600 font-semibold" title="Gepland">' . htmlspecialchars($publishedAt, ENT_QUOTES) . '</span>';
                            } else {
                                echo htmlspecialchars($publishedAt, ENT_QUOTES);
                            }
                        } else {
                            echo htmlspecialchars($createdAt, ENT_QUOTES);
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)$post['status'], ENT_QUOTES); ?></td>
                    <td class="text-right space-x-3">
                        <a class="underline" href="/admin/posts/<?php echo (int)$post['id']; ?>/edit">
                            Bewerken
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                            <a class="underline text-red-600" href="/admin/posts/<?php echo (int)$post['id']; ?>/delete">
                                Verwijderen
                            </a>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
