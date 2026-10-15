<?php

namespace App\Models;

use Exception;

class User
{
    private static function getStoragePath(): string
    {
        $storageDir = __DIR__ . '/../../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        return $storageDir . '/birthdays.txt';
    }

    public static function validateRequestData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if (preg_match('/<[a-z][\s\S]*>/i', $value)) {
                    throw new Exception("Поле '{$key}' содержит запрещенные HTML-теги!");
                }
            }
        }
    }

    public static function getAll(): array
    {
        $filePath = self::getStoragePath();
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $users = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $users[] = [
                    'id'       => (int)$parts[0],
                    'name'     => $parts[1],
                    'birthday' => $parts[2],
                    'token'    => $parts[3] ?? '',
                    'role'     => $parts[4] ?? 'user', // Роль: admin или user
                ];
            }
        }
        return $users;
    }

    public static function save(string $name, string $birthday): bool
    {
        $users = self::getAll();
        $newId = 1;
        if (!empty($users)) {
            $lastUser = end($users);
            $newId = $lastUser['id'] + 1;
        }

        // Первый зарегистрированный пользователь автоматически становится администратором
        $role = ($newId === 1) ? 'admin' : 'user';

        $filePath = self::getStoragePath();
        // Формат: ID|Name|Birthday|Token|Role
        $line = "{$newId}|{$name}|{$birthday}||{$role}" . PHP_EOL;

        return file_put_contents($filePath, $line, FILE_APPEND) !== false;
    }

    public static function updateName(int $id, string $newName): bool
    {
        $users = self::getAll();
        $found = false;

        foreach ($users as &$user) {
            if ($user['id'] === $id) {
                $user['name'] = $newName;
                $found = true;
                break;
            }
        }
        unset($user);

        if (!$found) {
            return false;
        }

        return self::saveAll($users);
    }

    public static function delete(int $id): bool
    {
        $users = self::getAll();
        $filtered = array_filter($users, fn($user) => $user['id'] !== $id);

        if (count($filtered) === count($users)) {
            return false;
        }

        return self::saveAll(array_values($filtered));
    }

    public static function setRememberToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $users = self::getAll();
        $found = false;

        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                $user['token'] = $token;
                $found = true;
                break;
            }
        }
        unset($user);

        if ($found) {
            self::saveAll($users);
        }

        return $token;
    }

    public static function findByToken(string $token): ?array
    {
        if (empty($token)) {
            return null;
        }
        $users = self::getAll();
        foreach ($users as $user) {
            if (!empty($user['token']) && $user['token'] === $token) {
                return $user;
            }
        }
        return null;
    }

    public static function clearToken(int $userId): void
    {
        $users = self::getAll();
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                $user['token'] = '';
                break;
            }
        }
        unset($user);
        self::saveAll($users);
    }

    private static function saveAll(array $users): bool
    {
        $filePath = self::getStoragePath();
        $content = '';
        foreach ($users as $user) {
            $token = $user['token'] ?? '';
            $role  = $user['role'] ?? 'user';
            $content .= "{$user['id']}|{$user['name']}|{$user['birthday']}|{$token}|{$role}" . PHP_EOL;
        }
        return file_put_contents($filePath, $content) !== false;
    }
}
