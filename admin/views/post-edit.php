<?php
declare(strict_types=1);
use Admin\Core\Csrf;
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold"><?= htmlspecialchars((string)($title ?? 'Post bewerken'), ENT_QUOTES) ?></h1>
            <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= (int)($postId ?? 0) ?>/revisions" 
               class="text-indigo-600 hover:text-indigo-900 border border-indigo-600 px-3 py-1 rounded text-sm bg-indigo-50 hover:bg-indigo-100">
                🕒 Geschiedenis
            </a>
        </div>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <?php if (isset($lockRemainingMinutes) && $lockRemainingMinutes > 0): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            🔒 Je hebt een lock op deze post. Nog <strong><?= (int)$lockRemainingMinutes ?></strong> minuten geldig.
        </div>
        <?php endif; ?>

        <form method="post" action="<?= ADMIN_BASE_PATH ?>/posts/<?= (int)($postId ?? 0) ?>/update" class="space-y-4">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
            <!-- Lock Token (voor refresh detectie) -->
            <?php if (isset($lockToken)): ?>
            <input type="hidden" name="lock_token" value="<?= htmlspecialchars((string)$lockToken, ENT_QUOTES) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-semibold mb-1">Titel</label>
                <input class="w-full border rounded px-3 py-2"
                       type="text"
                       name="title"
                       value="<?= htmlspecialchars((string)($old['title'] ?? ''), ENT_QUOTES) ?>"
                       required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Inhoud</label>
                <textarea class="w-full border rounded px-3 py-2"
                          name="content"
                          rows="10"
                          required><?= htmlspecialchars((string)($old['content'] ?? ''), ENT_QUOTES) ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <?php $status = (string)($old['status'] ?? 'draft'); ?>
                <select class="w-full border rounded px-3 py-2" name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>draft</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>published</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Featured image</label>
                <?php $featured = (string)($old['featured_media_id'] ?? ''); ?>
                <select class="w-full border rounded px-3 py-2" name="featured_media_id">
                    <option value="">Geen</option>
                    <?php foreach (($media ?? []) as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= ((string)$item['id'] === $featured) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$item['original_name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- NIEUW: Publicatiedatum veld -->
            <div>
                <label class="block text-sm font-semibold mb-1">Publicatiedatum (optioneel)</label>
                <input class="w-full border rounded px-3 py-2"
                       type="datetime-local"
                       name="published_at"
                       value="<?= htmlspecialchars((string)($old['published_at'] ?? ''), ENT_QUOTES) ?>">
                <p class="text-xs text-gray-500 mt-1">Laat leeg om direct te publiceren</p>
            </div>

            <!-- NIEUW: SEO Sectie -->
            <div class="border-t pt-4 mt-4">
                <h3 class="text-lg font-semibold mb-3">🔍 SEO Instellingen</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Meta titel (max 70 tekens)</label>
                    <input class="w-full border rounded px-3 py-2"
                           type="text"
                           name="meta_title"
                           maxlength="70"
                           placeholder="<?= htmlspecialchars((string)($old['title'] ?? ''), ENT_QUOTES) ?>"
                           value="<?= htmlspecialchars((string)($old['meta_title'] ?? ''), ENT_QUOTES) ?>">
                    <p class="text-xs text-gray-500 mt-1">Laat leeg om post titel te gebruiken</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Meta beschrijving (max 160 tekens)</label>
                    <textarea class="w-full border rounded px-3 py-2"
                              name="meta_description"
                              maxlength="160"
                              rows="2"
                              placeholder="Korte beschrijving voor zoekmachines..."><?= htmlspecialchars((string)($old['meta_description'] ?? ''), ENT_QUOTES) ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Verschijnt als snippet in Google</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" type="submit">
                    Opslaan
                </button>
                <a class="px-4 py-2 rounded border" href="<?= ADMIN_BASE_PATH ?>/posts">Annuleren</a>
            </div>
        </form>
    </div>
</section>
