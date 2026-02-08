<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use PDO;

class UsersRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }

    /**
     * getAll()
     * Doel: admin-overzicht van alle users + rolnaam.
     */
    public function getAll(): array
    {
        $sql = "SELECT u.id, u.email, u.name, u.is_active, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                ORDER BY u.id ASC";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * findByEmail()
     * Doel: login alleen voor actieve users.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.id, u.email, u.password_hash, u.name, u.is_active, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.email = :email
                AND u.is_active = 1
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * emailExists()
     * Doel: Check of een email al in gebruik is (voor registratie)
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() !== false;
    }

    /**
     * create()
     * Doel: nieuwe user aanmaken via admin panel.
     */
    public function create(string $email, string $name, string $plainPassword, int $roleId): void
    {
        $sql = "INSERT INTO users (email, name, password_hash, role_id, is_active)
                VALUES (:email, :name, :hash, :role_id, 1)";

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'name' => $name,
            'hash' => $hash,
            'role_id' => $roleId,
        ]);
    }

    /**
     * findById()
     */
    public function findById(int $id): ?array
    {
        // Let op: we halen hier ook auth info op mocht dat later nodig zijn
        $sql = "SELECT u.id, u.email, u.name, u.role_id, u.is_active, u.auth_provider, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * update()
     */
    public function update(int $id, string $name, int $roleId): void
    {
        $sql = "UPDATE users
                SET name = :name,
                    role_id = :role_id
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'role_id' => $roleId,
        ]);
    }

    /**
     * updatePassword()
     */
    public function updatePassword(int $id, string $plainPassword): void
    {
        $sql = "UPDATE users
                SET password_hash = :hash
                WHERE id = :id
                LIMIT 1";

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'hash' => $hash,
        ]);
    }

    /**
     * disable()
     */
    public function disable(int $id): void
    {
        $sql = "UPDATE users SET is_active = 0 WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    /**
     * enable()
     */
    public function enable(int $id): void
    {
        $sql = "UPDATE users SET is_active = 1 WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    // ==========================================
    // NIEUW: Voor Google Login
    // ==========================================
    public function findOrCreateGoogleUser(array $googleData): array
    {
        $googleId = $googleData['id'];
        $email = $googleData['email'];
        $name = $googleData['name'];

        // 1. Zoek op Google ID (Bestaat deze koppeling al?)
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON r.id = u.role_id
                WHERE auth_provider = 'google' AND auth_id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user; // Gevonden! Klaar.
        }

        // 2. Zoek op Email (Bestaat de user al als 'gewone' user?)
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // Update de bestaande user met Google gegevens
            $updateSql = "UPDATE users SET auth_provider = 'google', auth_id = :aid WHERE id = :id";
            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute(['aid' => $googleId, 'id' => $existingUser['id']]);

            // Haal hem opnieuw op
            return $this->findById((int)$existingUser['id']);
        }

        // 3. User bestaat helemaal niet -> Aanmaken
        // We moeten een fake wachtwoord verzinnen omdat 'password_hash' niet leeg mag zijn
        $dummyPassword = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
        $defaultRoleId = 2; // Editor

        $insertSql = "INSERT INTO users (email, password_hash, name, role_id, auth_provider, auth_id, is_active, created_at) 
                      VALUES (:email, :pass, :name, :role, 'google', :aid, 1, NOW())";

        $stmt = $this->pdo->prepare($insertSql);
        $stmt->execute([
            'email' => $email,
            'pass' => $dummyPassword,
            'name' => $name,
            'role' => $defaultRoleId,
            'aid' => $googleId
        ]);

        $newId = (int)$this->pdo->lastInsertId();
        return $this->findById($newId);
    }
}