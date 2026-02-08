<?php
declare(strict_types=1);
use Admin\Core\Csrf;
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow max-w-xl">
        <h2 class="text-xl font-bold mb-4 text-red-600">
            <?php echo htmlspecialchars((string)$title, ENT_QUOTES); ?>
        </h2>

        <p class="mb-4">Ben je zeker dat je deze post wil verwijderen?</p>

        <p class="mb-6">
            <strong><?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?></strong>
        </p>

        <form method="post" action="<?= ADMIN_BASE_PATH ?>/posts/<?php echo (int)$post['id']; ?>/delete">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

            <div class="flex gap-4">
                <button class="border px-4 py-2 text-red-600" type="submit">Ja, verwijder</button>
                <a class="underline" href="<?= ADMIN_BASE_PATH ?>/posts">Annuleren</a>
            </div>
        </form>
    </div>
</section>