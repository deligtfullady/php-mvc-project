<?php

namespace App\Services;

use Twig\Environment;
use Throwable;

class Render
{
    private static ?Environment $twig = null;

    // Сохраняем объект Twig, чтобы использовать его в статичных методах
    public static function setTwig(Environment $twig): void
    {
        self::$twig = $twig;
    }

    // Метод рендеринга страницы ошибки через общий шаблон
    public static function renderExceptionPage(Throwable $e): string
    {
        // Устанавливаем HTTP-код 500 (Internal Server Error)
        header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error", true, 500);

        if (self::$twig === null) {
            return "Ошибка: Twig не инициализирован в классе Render. " . $e->getMessage();
        }

        return self::$twig->render('exception.twig', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
