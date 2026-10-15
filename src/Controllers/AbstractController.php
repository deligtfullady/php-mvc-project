<?php

namespace App\Controllers;

use Twig\Environment;

abstract class AbstractController
{
    protected Environment $twig;

    // Конструктор базового контроллера принимает Twig и сохраняет его в свойство
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }
}
