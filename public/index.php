<?php

// Запускаем сессию в самом начале
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// Автоматический вход по куки «Запомнить меня», если сессия еще пустая
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $user = \App\Models\User::findByToken($_COOKIE['remember_token']);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user['name'];
    }
}

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use App\Controllers\ErrorController;
use App\Services\Render;

$loader = new FilesystemLoader(__DIR__ . '/../views');
$twig = new Environment($loader);

// Инициализируем Twig в нашем сервисе Render
Render::setTwig($twig);

try {
    // Начало работы приложения (как в задании: try { ... } catch)
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uriParts = explode('/', trim($uri, '/'));

    $controllerName = !empty($uriParts[0]) ? ucfirst($uriParts[0]) . 'Controller' : 'UserController';
    $actionName = !empty($uriParts[1]) ? 'action' . ucfirst($uriParts[1]) : 'actionIndex';
    $fullControllerName = "App\\Controllers\\" . $controllerName;

    if (class_exists($fullControllerName)) {
        // Передаем Twig в конструктор базового контроллера
        $controller = new $fullControllerName($twig);
        if (method_exists($controller, $actionName)) {
            // Вызываем экшен без параметров, так как Twig уже доступен внутри через $this->twig
            echo $controller->$actionName();
            exit;
        }
    }
    $errorController = new ErrorController();
    echo $errorController->render404($twig, "Запрошенный адрес '{$uri}' не существует.");
} catch (Exception $e) {
    // Перехватываем исключение и рендерим через Render
    echo Render::renderExceptionPage($e);
}
