<?php
declare(strict_types=1);

// SECURITY: In productie errors verbergen voor bezoekers
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Log errors naar een bestand in plaats van op het scherm
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_public_php_error.log');

/**
 * SECURITY: HTTP Headers
 */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');


/*
|--------------------------------------------------------------------------
| Public Front Controller
|--------------------------------------------------------------------------
| Dankzij DocumentRoot naar /public en .htaccess komt elke publieke URL hier binnen.
| Hier bepalen we:
| - welke route is opgevraagd
| - welke data nodig is
| - welke view we tonen
*/


require_once __DIR__ . '/../admin/autoload.php';


use Admin\Core\Database;
use Admin\Repositories\PostsRepository;

// 1) Alleen het pad uit de URL halen (zonder querystring)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// 2) Trailing slash verwijderen ("/posts/" -> "/posts")
$uri = rtrim($uri, '/') ?: '/';

// 3) PDO connectie ophalen
$pdo = Database::getConnection();

// 4) Repository initialiseren
$postsRepository = new PostsRepository($pdo);

// 5) Routing
switch ($uri) {

    case '/':
        // Home: recente published posts
        $posts = $postsRepository->getPublishedLatest(5);

        require __DIR__ . '/views/posts/home.php';
        break;

    case '/posts':
        // Overzicht: alle published posts
        $posts = $postsRepository->getPublishedAll();

        require __DIR__ . '/views/posts/index.php';
        break;

    default:
        // Detail: /posts/{slug}
        // AANGEPAST: Regex matcht nu op slugs (letters, cijfers, streepjes)
        if (preg_match('#^/posts/([a-z0-9-]+)$#', $uri, $matches)) {

            // slug uit regex
            $slug = $matches[1];

            // Alleen published post ophalen via slug
            $post = $postsRepository->findPublishedBySlug($slug);

            if (!$post) {
                http_response_code(404);
                echo '404 - Post niet gevonden';
                exit;
            }

            require __DIR__ . '/views/posts/show.php';
            break;
        }

        http_response_code(404);
        echo '404 - Pagina niet gevonden';
}
