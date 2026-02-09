<?php
declare(strict_types=1);

/**
 * SECURITY: Veilige sessie instellingen
 * Moet gebeuren VOORDAT session_start() wordt aangeroepen.
 */
session_set_cookie_params([
    'lifetime' => 0,            // Sessie stopt als browser sluit
    'path' => '/',              // Geldig voor hele domein
    'domain' => '',             // Huidig domein
    'secure' => false,          // Zet op TRUE als je HTTPS gebruikt (in productie verplicht!)
    'httponly' => true,         // JavaScript kan niet bij de cookie (tegen XSS)
    'samesite' => 'Strict',     // Cookie wordt niet meegestuurd bij cross-site requests (tegen CSRF)
]);

session_start();

/**
 * SECURITY: HTTP Headers
 */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');

require __DIR__ . '/config/app.php';
require __DIR__ . '/autoload.php';

use Admin\Controllers\AuthController;
use Admin\Controllers\DashboardController;
use Admin\Controllers\ErrorController;
use Admin\Controllers\MediaController;
use Admin\Controllers\PostsController;
use Admin\Controllers\UsersController;
use Admin\Core\Auth;
use Admin\Core\Router;
use Admin\Models\StatsModel;
use Admin\Repositories\MediaRepository;
use Admin\Repositories\PostsRepository;
use Admin\Repositories\RolesRepository;
use Admin\Repositories\UsersRepository;

// Path uit de URL halen (zonder querystring)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// ADMIN_BASE_PATH (/admin) verwijderen uit de URL
if (str_starts_with($uri, ADMIN_BASE_PATH)) {
    $uri = substr($uri, strlen(ADMIN_BASE_PATH));
}

// Trailing slash verwijderen en lege string corrigeren
$uri = rtrim($uri, '/') ?: '/';

/**
 * Beveilig admin-routes:
 * als je niet ingelogd bent, ga naar /login
 */
// AANGEPAST: Google + Register routes toegevoegd aan publicRoutes
$publicRoutes = ['/login', '/register', '/auth/google/login', '/auth/google/callback'];

if (!Auth::check() && !in_array($uri, $publicRoutes, true)) {
    header('Location: ' . ADMIN_BASE_PATH . '/login');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$router = new Router();

/**
 * setNotFoundHandler()
 *
 * Doel:
 * - Zorgt dat elke onbekende URL een nette 404 pagina krijgt via ErrorController.
 */
$errorController = new ErrorController();

$router->setNotFoundHandler(function (string $requestedUri) use ($errorController): void {
    $errorController->notFound($requestedUri);
});

/**
 * Admin-only guard helper
 *
 * Doel:
 * - herbruikbaar in routes
 * - toont 403 pagina via ErrorController
 */
$requireAdmin = function () use ($errorController): void {
    if (!Auth::isAdmin()) {
        $errorController->forbidden('Admin rechten vereist.');
        exit;
    }
};

/**
 * Dashboard
 */
$router->get('/', function (): void {
    (new DashboardController(new StatsModel()))->index();
});

/**
 * Auth
 */
$router->get('/login', function (): void {
    (new AuthController(UsersRepository::make()))->showLogin();
});

$router->post('/login', function (): void {
    (new AuthController(UsersRepository::make()))->login();
});

$router->post('/logout', function (): void {
    (new AuthController(UsersRepository::make()))->logout();
});

// NIEUW: Registratie Routes
$router->get('/register', function (): void {
    (new AuthController(UsersRepository::make()))->showRegister();
});

$router->post('/register', function (): void {
    (new AuthController(UsersRepository::make()))->register();
});

// NIEUW: Google Auth Routes
$router->get('/auth/google/login', function (): void {
    (new AuthController(UsersRepository::make()))->loginGoogle();
});

$router->get('/auth/google/callback', function (): void {
    (new AuthController(UsersRepository::make()))->loginGoogleCallback();
});

/**
 * Soft Deletes Routes (Prullenbak)
 */

// 1. Toon de prullenbak
$router->get('/posts/trash', function () use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make()))->trash();
});

// 2. Herstel een post (Restore)
$router->post('/posts/{id}/restore', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make()))->restore($id);
});

/**
 * Users (admin-only)
 */
$router->get('/users', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->index();
});

$router->get('/users/create', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->create();
});

$router->post('/users/store', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->store();
});

$router->get('/users/{id}/edit', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->edit($id);
});

$router->post('/users/{id}/update', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->update($id);
});

$router->post('/users/{id}/reset-password', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->resetPassword($id);
});

$router->post('/users/{id}/disable', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->disable($id);
});

$router->post('/users/{id}/enable', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->enable($id);
});

/**
 * Posts
 */
$router->get('/posts', function (): void {
    (new PostsController(PostsRepository::make()))->index();
});

$router->get('/posts/create', function (): void {
    (new PostsController(PostsRepository::make()))->create();
});

$router->post('/posts/store', function (): void {
    (new PostsController(PostsRepository::make()))->store();
});

$router->get('/posts/{id}/edit', function (int $id): void {
    (new PostsController(PostsRepository::make()))->edit($id);
});

$router->post('/posts/{id}/update', function (int $id): void {
    (new PostsController(PostsRepository::make()))->update($id);
});

/**
 * Delete routes (admin-only)
 * Let op: jij had hier al Auth::isAdmin checks.
 * We vervangen dat door $requireAdmin() voor consistent gedrag (403 pagina).
 */
$router->get('/posts/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make()))->deleteConfirm($id);
});

$router->post('/posts/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    // AANGEPAST: Nu via controller met CSRF check
    (new PostsController(PostsRepository::make()))->delete($id);
});

/**
 * Show
 * Deze moet onder edit/update staan.
 */
$router->get('/posts/{id}', function (int $id): void {
    (new PostsController(PostsRepository::make()))->show($id);
});

/**
 * Media (admin-only)
 */
$router->get('/media', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->index();
});

$router->get('/media/upload', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->uploadForm();
});

$router->post('/media/store', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->store();
});

$router->post('/media/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->delete($id);
});

$router->dispatch($uri, $method);