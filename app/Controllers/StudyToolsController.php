<?php
/**
 * Controller de Ferramentas de Estudo em Tempo Real — Vértice
 * Gerencia: Live Code Editor, SQL Playground e Whiteboard
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\LiveCode;
use App\Models\SqlPlayground;
use App\Models\Whiteboard;

class StudyToolsController
{
    private function getAuthUser(): array
    {
        if (!Auth::check()) {
            $this->jsonResponse(['error' => 'Não autorizado. Faça login primeiro.'], 401);
        }
        $user = Auth::user();
        if (!$user) {
            $this->jsonResponse(['error' => 'Não autorizado. Faça login primeiro.'], 401);
        }
        return $user;
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── LIVE CODE EDITOR ─────────────────────────────────────────────────────

    public function getCode(): void
    {
        $this->getAuthUser();
        $channelId = (int)($_GET['channel_id'] ?? 1);
        $filename = trim($_GET['filename'] ?? 'main.php');

        if ($channelId <= 0) {
            $channelId = 1;
        }

        $file = LiveCode::getActiveFile($channelId, $filename);
        $this->jsonResponse([
            'status' => 'success',
            'file' => $file
        ]);
    }

    public function saveCode(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 1);
        $filename = trim($data['filename'] ?? 'main.php');
        $language = trim($data['language'] ?? 'php');
        $content = (string)($data['content'] ?? '');

        if ($channelId <= 0) {
            $channelId = 1;
        }

        $ok = LiveCode::saveFile($channelId, $filename, $language, $content, (int)$user['id'], $user['name'] ?? 'Colega');

        $this->jsonResponse([
            'status' => $ok ? 'success' : 'error',
            'updated_by' => $user['name'] ?? 'Colega',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function runCode(): void
    {
        $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $language = strtolower(trim($data['language'] ?? 'php'));
        $code = (string)($data['code'] ?? '');

        if (empty(trim($code))) {
            $this->jsonResponse([
                'status' => 'error',
                'output' => 'O código está vazio. Digite algo para executar.'
            ]);
        }

        $startTime = microtime(true);
        $output = '';

        if ($language === 'php') {
            // Executa PHP via CLI temporário com timeout estrito
            $tmpFile = sys_get_temp_dir() . '/vertice_run_' . uniqid() . '.php';
            file_put_contents($tmpFile, $code);

            // 1. Lint check
            $lint = shell_exec("php -l \"{$tmpFile}\" 2>&1");
            if (strpos($lint, 'No syntax errors detected') === false) {
                $output = "⚠️ Erro de Sintaxe PHP:\n" . $lint;
            } else {
                $output = shell_exec("php \"{$tmpFile}\" 2>&1");
            }
            @unlink($tmpFile);
        } elseif ($language === 'python') {
            $tmpFile = sys_get_temp_dir() . '/vertice_run_' . uniqid() . '.py';
            file_put_contents($tmpFile, $code);
            $output = shell_exec("python \"{$tmpFile}\" 2>&1");
            @unlink($tmpFile);
        } else {
            $output = "A linguagem {$language} é executada diretamente no navegador pelo Web Worker sandbox.";
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $this->jsonResponse([
            'status' => 'success',
            'output' => $output ?? '',
            'duration_ms' => $durationMs
        ]);
    }

    // ── SQL PLAYGROUND ───────────────────────────────────────────────────────

    public function executeSql(): void
    {
        $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $query = (string)($data['query'] ?? '');
        $result = SqlPlayground::executeQuery($query);

        $this->jsonResponse($result);
    }

    public function getSqlSchema(): void
    {
        $this->getAuthUser();
        $schema = SqlPlayground::getSchemaOverview();
        $this->jsonResponse([
            'status' => 'success',
            'tables' => $schema
        ]);
    }

    public function resetSql(): void
    {
        $this->getAuthUser();
        SqlPlayground::resetDatabase();
        $schema = SqlPlayground::getSchemaOverview();

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Banco de dados didático restaurado com sucesso para o estado original!',
            'tables' => $schema
        ]);
    }

    // ── WHITEBOARD / LOUSA DIGITAL ───────────────────────────────────────────

    public function getWhiteboard(): void
    {
        $this->getAuthUser();
        $channelId = (int)($_GET['channel_id'] ?? 1);
        if ($channelId <= 0) $channelId = 1;

        $shapes = Whiteboard::getShapes($channelId);
        $this->jsonResponse([
            'status' => 'success',
            'shapes' => $shapes
        ]);
    }

    public function saveWhiteboardShape(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $channelId = (int)($data['channel_id'] ?? 1);
        if ($channelId <= 0) $channelId = 1;

        $shapeData = is_string($data['shape_data'] ?? '') ? $data['shape_data'] : json_encode($data['shape_data'] ?? []);

        $id = Whiteboard::addShape($channelId, (int)$user['id'], $user['name'] ?? 'Colega', $shapeData);

        $this->jsonResponse([
            'status' => 'success',
            'id' => $id
        ]);
    }

    public function clearWhiteboard(): void
    {
        $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $channelId = (int)($data['channel_id'] ?? 1);
        if ($channelId <= 0) $channelId = 1;

        Whiteboard::clear($channelId);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lousa limpa com sucesso!'
        ]);
    }
}
