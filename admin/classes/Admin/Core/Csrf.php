<?php
declare(strict_types=1);

namespace Admin\Core;

class Csrf
{
    /**
     * getToken()
     *
     * Doel:
     * Genereert een token als die er nog niet is, en geeft deze terug.
     * Dit token moet in een hidden input veld in elk formulier.
     */
    public static function getToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * check()
     *
     * Doel:
     * Controleert of het token uit het formulier ($_POST) overeenkomt met de sessie.
     * Gooit een Exception of stopt het script als het niet klopt.
     */
    public static function check(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(419); // Page Expired
            die('<h1>419 - Pagina verlopen (CSRF Mismatch)</h1><p>Probeer het formulier opnieuw te verzenden.</p>');
        }
    }
}