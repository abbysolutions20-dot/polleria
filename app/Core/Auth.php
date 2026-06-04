<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private Database $db;
    private array $config;
    private ?array $user;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->user = $_SESSION[$this->sessionKey()] ?? null;
    }

    public function attempt(string $login, string $password): bool
    {
        $user = $this->db->fetch(
            'SELECT u.*, r.nombre AS rol_nombre, s.nombre AS sucursal_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.id_rol
             LEFT JOIN sucursales s ON s.id = u.id_sucursal
             WHERE u.activo = 1 AND (u.usuario = :login_user OR u.correo = :login_email)
             LIMIT 1',
            [
                'login_user' => trim($login),
                'login_email' => trim($login),
            ]
        );

        if (!$user) {
            return false;
        }

        $isValid = password_verify($password, (string) $user['password_hash']);

        if (!$isValid && $this->isLegacySeedHash((string) $user['password_hash'])) {
            $defaultPassword = (string) ($this->config['legacy_default_password'] ?? '123456');

            if (hash_equals($defaultPassword, $password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $this->db->execute(
                    'UPDATE usuarios SET password_hash = :hash WHERE id = :id',
                    ['hash' => $hash, 'id' => $user['id']]
                );
                $user['password_hash'] = $hash;
                $isValid = true;
            }
        }

        if (!$isValid) {
            return false;
        }

        $this->db->execute(
            'UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id',
            ['id' => $user['id']]
        );

        $sessionUser = [
            'id' => (int) $user['id'],
            'id_rol' => (int) $user['id_rol'],
            'id_sucursal' => $user['id_sucursal'] !== null ? (int) $user['id_sucursal'] : null,
            'nombres' => $user['nombres'],
            'apellidos' => $user['apellidos'],
            'usuario' => $user['usuario'],
            'correo' => $user['correo'],
            'rol_nombre' => $user['rol_nombre'],
            'sucursal_nombre' => $user['sucursal_nombre'],
            'permissions' => $this->permissionsForRole((int) $user['id_rol']),
        ];

        $_SESSION[$this->sessionKey()] = $sessionUser;
        $this->user = $sessionUser;

        session_regenerate_id(true);

        return true;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }

    public function can(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }

        return in_array($permission, $this->user['permissions'] ?? [], true);
    }

    public function roleName(): ?string
    {
        return $this->user['rol_nombre'] ?? null;
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array((string) $this->roleName(), $roles, true);
    }

    public function homePage(): string
    {
        return match ((string) $this->roleName()) {
            'CAJERO' => 'cash',
            'COCINA' => 'kitchen',
            default => 'dashboard',
        };
    }

    public function isOwner(): bool
    {
        return $this->hasRole('PROPIETARIO');
    }

    public function logout(): void
    {
        unset($_SESSION[$this->sessionKey()]);
        $this->user = null;
        session_regenerate_id(true);
    }

    private function permissionsForRole(int $roleId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.codigo
             FROM permisos p
             INNER JOIN rol_permisos rp ON rp.id_permiso = p.id
             WHERE rp.id_rol = :role
             ORDER BY p.codigo',
            ['role' => $roleId]
        );

        return array_map(static fn (array $row): string => $row['codigo'], $rows);
    }

    private function sessionKey(): string
    {
        return (string) ($this->config['session_key'] ?? 'polleria_pos_user');
    }

    private function isLegacySeedHash(string $hash): bool
    {
        return $hash === '$2y$10$abcdefghijklmnopqrstuv'
            || $hash === '$2y$10$abcdefghijklmnopqrstuv.'
            || str_contains($hash, 'abcdefghijklmnopqrstuv');
    }
}
