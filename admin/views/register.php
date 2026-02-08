<?php
declare(strict_types=1);
use Admin\Core\Csrf;
?>

<section class="p-6 flex justify-center items-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Registreren bij MiniCMS</h2>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 border border-red-200 bg-red-50 rounded text-red-700 text-sm">
                <p class="font-bold mb-2">Er ging iets mis:</p>
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars((string)$error, ENT_QUOTES); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= ADMIN_BASE_PATH ?>/register" class="space-y-4">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700" for="name">Naam</label>
                <input
                        class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-500"
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES); ?>"
                        placeholder="Je volledige naam"
                        required
                >
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700" for="email">Email</label>
                <input
                        class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-500"
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES); ?>"
                        placeholder="naam@voorbeeld.com"
                        required
                >
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700" for="password">Wachtwoord</label>
                <input
                        class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-500"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimaal 8 karakters"
                        required
                >
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700" for="password_confirm">Wachtwoord bevestigen</label>
                <input
                        class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-500"
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        placeholder="Herhaal je wachtwoord"
                        required
                >
            </div>

            <div>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-200" type="submit">
                    Account aanmaken
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Heb je al een account? <a href="<?= ADMIN_BASE_PATH ?>/login" class="text-blue-600 hover:underline">Inloggen</a></p>
        </div>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Of registreer met</span>
            </div>
        </div>

        <div>
            <!-- Google OAuth -->
            <a href="<?= ADMIN_BASE_PATH ?>/auth/google/login"
               class="w-full flex items-center justify-center gap-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded transition duration-200">

                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.26.81-.58z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Registreren met Google
            </a>
        </div>

    </div>
</section>
