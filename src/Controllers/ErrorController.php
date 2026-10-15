<?php

namespace App\Controllers;

use Twig\Environment;

class ErrorController
{
    public function render404(Environment $twig, string $message = "Страница не найдена"): string
    {
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);

        return $twig->render('404.twig', [
            'message' => $message
        ]);
    }
}
