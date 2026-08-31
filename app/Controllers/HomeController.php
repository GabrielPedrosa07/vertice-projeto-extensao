<?php
/**
 * Controller de Páginas Públicas — Vértice
 * PHP Puro
 */

namespace App\Controllers;

use App\Models\Category;

class HomeController
{
    public function index(): void
    {
        $disciplinas = Category::all();
        require __DIR__ . '/../../views/inicio.php';
    }

    public function sobre(): void
    {
        require __DIR__ . '/../../views/sobre.php';
    }

    public function termos(): void
    {
        require __DIR__ . '/../../views/termos.php';
    }

    public function privacidade(): void
    {
        require __DIR__ . '/../../views/privacidade.php';
    }
}
