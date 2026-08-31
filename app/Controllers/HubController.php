<?php
/**
 * Controller do Hub Principal — Vértice
 * PHP Puro
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\Category;

class HubController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $minhasComunidades = Category::getJoinedByUser($user['id']);
        $disponiveis = Category::getAvailableForUser($user['id']);

        require __DIR__ . '/../../views/hub/index.php';
    }
}
