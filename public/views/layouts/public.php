<?php
declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Layout: public
|--------------------------------------------------------------------------
| Verwacht van elke view:
| - $title   (string)
| - $content (string) -> HTML via output buffering
| - $metaTitle (string, optioneel) -> SEO title
| - $metaDescription (string, optioneel) -> SEO description
*/

// NIEUW: SEO fallback logica
$seoTitle = !empty($metaTitle) ? $metaTitle : ($title ?? 'MiniCMS');
$seoDescription = $metaDescription ?? '';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO Title -->
    <title><?= htmlspecialchars($seoTitle) ?></title>

    <!-- SEO Meta Description (alleen als ingevuld) -->
    <?php if (!empty($seoDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES) ?>">
    <?php endif; ?>

    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">

<?php require __DIR__ . '/../partials/nav.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-10">
    <?= $content ?>
</main>

<?php  require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>

