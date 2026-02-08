<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\View;
use Admin\Core\Auth;
use Admin\Core\Csrf;
use Admin\Repositories\UsersRepository;

class AuthController
{
    private UsersRepository $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    /**
     * showLogin()
     */
    public function showLogin(): void
    {
        View::render('login.php', [
            'title' => 'Login',
            'errors' => [],
            'old' => [
                'email' => '',
            ],
        ]);
    }

    /**
     * showRegister() - Toon registratie formulier
     */
    public function showRegister(): void
    {
        View::render('register.php', [
            'title' => 'Registreren',
            'errors' => [],
            'old' => [
                'name' => '',
                'email' => '',
            ],
        ]);
    }

    /**
     * register() - Verwerk registratie
     */
    public function register(): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        $errors = [];

        // Validatie: Naam
        if ($name === '') {
            $errors[] = 'Naam is verplicht.';
        } elseif (mb_strlen($name) < 2) {
            $errors[] = 'Naam moet minstens 2 karakters zijn.';
        }

        // Validatie: Email
        if ($email === '') {
            $errors[] = 'Email is verplicht.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ongeldig email formaat.';
        } elseif ($this->usersRepository->emailExists($email)) {
            $errors[] = 'Dit email adres is al in gebruik.';
        }

        // Validatie: Wachtwoord
        if ($password === '') {
            $errors[] = 'Wachtwoord is verplicht.';
        } elseif (mb_strlen($password) < 8) {
            $errors[] = 'Wachtwoord moet minstens 8 karakters zijn.';
        }

        // Validatie: Wachtwoord bevestiging
        if ($password !== $passwordConfirm) {
            $errors[] = 'Wachtwoorden komen niet overeen.';
        }

        if (!empty($errors)) {
            View::render('register.php', [
                'title' => 'Registreren',
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ]);
            return;
        }

        // Aanmaken met standaard rol: Editor (role_id = 2)
        $this->usersRepository->create($email, $name, $password, 2);

        // SECURITY: Sessie ID vernieuwen
        session_regenerate_id(true);

        // Direct inloggen na registratie
        $user = $this->usersRepository->findByEmail($email);
        if ($user) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_role'] = (string)$user['role_name'];
        }

        header('Location: /admin');
        exit;
    }

    /**
     * login() - Normale login
     */
    public function login(): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email is verplicht.';
        }
        if ($password === '') {
            $errors[] = 'Wachtwoord is verplicht.';
        }

        if (!empty($errors)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => $errors,
                'old' => ['email' => $email],
            ]);
            return;
        }

        $user = $this->usersRepository->findByEmail($email);

        if ($user === null) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        $hash = (string)$user['password_hash'];

        if (!password_verify($password, $hash)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        // SECURITY: Sessie ID vernieuwen om session fixation te voorkomen
        session_regenerate_id(true);

        // Sessie vullen
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)$user['role_name'];

        header('Location: /admin');
        exit;
    }

    /**
     * loginGoogle() - Stap 1: Stuur gebruiker naar Google
     */
    public function loginGoogle(): void
    {
        // Lees de return URL uit de query parameter (betrouwbaarder dan HTTP_REFERER bij cross-domain)
        $returnUrl = $_GET['return_url'] ?? '';
        if (!empty($returnUrl) && filter_var($returnUrl, FILTER_VALIDATE_URL)) {
            // Valideer dat het een veilige URL is (alleen http/https, geen javascript: etc.)
            $parsed = parse_url($returnUrl);
            if (isset($parsed['scheme']) && in_array($parsed['scheme'], ['http', 'https'])) {
                $_SESSION['google_return_url'] = $returnUrl;
            }
        }

        // 1. Roep de service erbij
        $googleService = new \Admin\Services\GoogleLoginService();

        // 2. Vraag de URL aan de service
        $loginUrl = $googleService->getLoginUrl();

        // 3. Stuur de bezoeker naar Google
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * loginGoogleCallback() - Stap 2: Gebruiker komt terug van Google
     */
    public function loginGoogleCallback(): void
    {
        // Checken of we een code hebben
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            header('Location: /admin/login');
            exit;
        }

        try {
            // 1. Wissel code in voor data via de service
            $googleService = new \Admin\Services\GoogleLoginService();
            $googleUser = $googleService->getGoogleUser($code);

            // 2. Zoek of maak de user via repository
            $user = $this->usersRepository->findOrCreateGoogleUser($googleUser);

            // SECURITY: Sessie ID vernieuwen om session fixation te voorkomen
            session_regenerate_id(true);

            // 3. Log de gebruiker in (Sessie zetten)
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_role'] = (string)($user['role_name'] ?? 'editor');

            // 4. Redirect naar Dashboard
            header('Location: /admin');
            exit;

        } catch (\Exception $e) {
            // Fout tonen als er iets misgaat
            echo "<h1>Er ging iets mis met inloggen</h1>";
            echo "<p>Foutmelding: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Controleer je config/google.php instellingen.</p>";
            exit;
        }
    }

    /**
     * logout()
     */
    public function logout(): void
    {
        // SECURITY: CSRF Check
        Csrf::check();

        Auth::logout();
        header('Location: /admin/login');
        exit;
    }
}