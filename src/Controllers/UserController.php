<?php

namespace App\Controllers;

use App\Models\User;
use Exception;

class UserController extends AbstractController
{
    public function actionIndex(): string
    {
        $users = User::getAll();

        // Проверяем, является ли текущий залогиненный пользователь администратором
        $isAdmin = false;
        if (!empty($_SESSION['user_id'])) {
            foreach ($users as $user) {
                if ($user['id'] === $_SESSION['user_id'] && ($user['role'] ?? '') === 'admin') {
                    $isAdmin = true;
                    break;
                }
            }
        }

        return $this->twig->render('users.twig', [
            'users'   => $users,
            'is_admin' => $isAdmin,
        ]);
    }

    public function actionSave(): string
    {
        User::validateRequestData($_POST);

        $name = trim($_POST['name'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');

        if (empty($name) || empty($birthday)) {
            throw new Exception("Параметры name и birthday обязательны для сохранения!");
        }

        User::save($name, $birthday);

        header('Location: /user/index');
        exit;
    }

    public function actionEdit(): string
    {
        User::validateRequestData($_GET);

        $id = (int)($_GET['id'] ?? 0);
        $users = User::getAll();

        $currentUser = null;
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                $currentUser = $user;
                break;
            }
        }

        if (!$currentUser) {
            throw new Exception("Пользователь с ID {$id} не найден.");
        }

        return $this->twig->render('user-edit.twig', [
            'user' => $currentUser
        ]);
    }

    public function actionUpdate(): string
    {
        User::validateRequestData($_POST);

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || empty($name)) {
            throw new Exception("Некорректный ID или пустое имя.");
        }

        $success = User::updateName($id, $name);

        if (!$success) {
            throw new Exception("Пользователь с ID {$id} не найден.");
        }

        header('Location: /user/index');
        exit;
    }

    // Удаление пользователя (поддерживает AJAX через fetch и обычный запрос)
    public function actionDelete(): void
    {
        User::validateRequestData($_GET);

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->sendJsonResponse(false, 'Некорректный ID.');
        }

        $success = User::delete($id);

        if (!$success) {
            $this->sendJsonResponse(false, 'Пользователь не найден.');
        }

        // Если это AJAX-запрос, отдаем JSON без редиректа
        if ($this->isAjax()) {
            $this->sendJsonResponse(true);
        }

        header('Location: /user/index');
        exit;
    }

    public function actionLogout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            User::clearToken($_SESSION['user_id']);
        }

        setcookie('remember_token', '', time() - 3600, '/');

        $_SESSION = [];
        session_destroy();

        header('Location: /user/index');
        exit;
    }

    // Вспомогательные методы для работы с AJAX
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function sendJsonResponse(bool $success, string $message = ''): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
        }

        if (!$success) {
            throw new Exception($message);
        }

        header('Location: /user/index');
        exit;
    }
}
