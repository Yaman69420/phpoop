<section class="p-6">
    <div class="bg-white p-6 rounded shadow max-w-2xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold">
                Revisie #<?= (int)$revision['revision_number'] ?>
            </h1>
            <div class="flex space-x-3">
                <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= $post['id'] ?>/revisions" 
                   class="px-4 py-2 rounded border bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm">
                    Terug
                </a>
                
                <form id="restoreForm-<?= $revision['id'] ?>" method="post" action="<?= ADMIN_BASE_PATH ?>/posts/<?= $post['id'] ?>/revisions/<?= $revision['id'] ?>/restore">
                    <input type="hidden" name="csrf_token" value="<?= \Admin\Core\Csrf::getToken() ?>">
                    <button type="button" 
                            onclick="document.getElementById('restoreModal').classList.remove('hidden')"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm font-bold">
                        Herstel versie
                    </button>
                </form>
            </div>
        </div>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <div class="space-y-6">
            <!-- Metadata -->
            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded border">
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Datum</span>
                    <span class="text-sm"><?= htmlspecialchars($revision['created_at']) ?></span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Status</span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?= $revision['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= htmlspecialchars($revision['status']) ?>
                    </span>
                </div>
            </div>

            <!-- Content Check -->
            <div>
                <label class="block text-sm font-semibold mb-1">Titel</label>
                <div class="w-full border rounded px-3 py-2 bg-gray-50">
                    <?= htmlspecialchars($revision['title']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1">Inhoud</label>
                <div class="w-full border rounded px-3 py-2 bg-gray-50 min-h-[100px] prose max-w-none">
                    <?= nl2br(htmlspecialchars($revision['content'])) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Restore Modal -->
<div id="restoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
    <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Versie Herstellen</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Weet je zeker dat je deze versie wilt herstellen? De huidige versie wordt opgeslagen als backup.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmRestoreBtn"
                        onclick="document.getElementById('restoreForm-<?= $revision['id'] ?>').submit()"
                        class="px-4 py-2 bg-yellow-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                    Ja, herstel versie
                </button>
                <button onclick="document.getElementById('restoreModal').classList.add('hidden')"
                        class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Annuleren
                </button>
            </div>
        </div>
    </div>
</div>
