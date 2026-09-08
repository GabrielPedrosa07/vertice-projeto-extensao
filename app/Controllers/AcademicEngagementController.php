<?php
/**
 * Controller de Gamificação, Fórum Q&A e Quizzes — Vértice
 */

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\Forum;
use App\Models\Gamification;
use App\Models\Quiz;

class AcademicEngagementController
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

    // ── FÓRUM DE DÚVIDAS RESOLVIDAS ──────────────────────────────────────────

    public function listQuestions(): void
    {
        $this->getAuthUser();
        $categoryId = (int)($_GET['category_id'] ?? 1);
        $filter = trim($_GET['filter'] ?? 'all');

        $questions = Forum::getQuestions($categoryId, $filter);
        $this->jsonResponse([
            'status' => 'success',
            'questions' => $questions
        ]);
    }

    public function getQuestion(): void
    {
        $this->getAuthUser();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'Pergunta não informada.'], 400);
        }

        Forum::incrementViews($id);
        $question = Forum::getQuestion($id);
        if (!$question) {
            $this->jsonResponse(['error' => 'Pergunta não encontrada.'], 404);
        }

        $answers = Forum::getAnswers($id);
        $this->jsonResponse([
            'status' => 'success',
            'question' => $question,
            'answers' => $answers
        ]);
    }

    public function createQuestion(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $categoryId = (int)($data['category_id'] ?? 1);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $tags = trim($data['tags'] ?? '');

        if (empty($title) || empty($content)) {
            $this->jsonResponse(['error' => 'Preencha o título e a descrição da sua dúvida.'], 400);
        }

        $id = Forum::createQuestion($categoryId, (int)$user['id'], $user['name'] ?? 'Aluno', $title, $content, $tags);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Dúvida publicada com sucesso! (+5 pontos de Karma)',
            'question_id' => $id
        ]);
    }

    public function createAnswer(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $questionId = (int)($data['question_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($questionId <= 0 || empty($content)) {
            $this->jsonResponse(['error' => 'Escreva o conteúdo da sua resposta.'], 400);
        }

        $ansId = Forum::createAnswer($questionId, (int)$user['id'], $user['name'] ?? 'Colega', $user['role'] ?? 'student', $content);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Resposta publicada! Obrigado por ajudar seus colegas (+10 pontos de Karma)',
            'answer_id' => $ansId
        ]);
    }

    public function markSolution(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $questionId = (int)($data['question_id'] ?? 0);
        $answerId = (int)($data['answer_id'] ?? 0);

        if ($questionId <= 0 || $answerId <= 0) {
            $this->jsonResponse(['error' => 'Dados inválidos para marcar solução.'], 400);
        }

        $ok = Forum::markSolution($questionId, $answerId, (int)$user['id']);

        if ($ok) {
            $this->jsonResponse([
                'status' => 'success',
                'message' => '✅ Resposta marcada como solução oficial! (+30 pontos de Karma atribuídos)'
            ]);
        } else {
            $this->jsonResponse(['error' => 'Apenas o autor da pergunta ou monitores podem marcar a solução.'], 403);
        }
    }

    public function voteAnswer(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $answerId = (int)($data['answer_id'] ?? 0);
        if ($answerId <= 0) {
            $this->jsonResponse(['error' => 'Resposta não informada.'], 400);
        }

        $res = Forum::voteAnswer($answerId, (int)$user['id']);
        $this->jsonResponse($res);
    }

    // ── GAMIFICAÇÃO & LEADERBOARD ────────────────────────────────────────────

    public function getLeaderboard(): void
    {
        $this->getAuthUser();
        $limit = (int)($_GET['limit'] ?? 10);
        $leaderboard = Gamification::getLeaderboard($limit);

        $this->jsonResponse([
            'status' => 'success',
            'leaderboard' => $leaderboard
        ]);
    }

    public function getMyStats(): void
    {
        $user = $this->getAuthUser();
        $karma = Gamification::getUserKarma((int)$user['id']);

        $this->jsonResponse([
            'status' => 'success',
            'stats' => $karma
        ]);
    }

    // ── QUIZZES RÁPIDOS PRÉ-PROVA ────────────────────────────────────────────

    public function listQuizzes(): void
    {
        $this->getAuthUser();
        $categoryId = (int)($_GET['category_id'] ?? 1);
        $quizzes = Quiz::getQuizzesByCategory($categoryId);

        $this->jsonResponse([
            'status' => 'success',
            'quizzes' => $quizzes
        ]);
    }

    public function getQuiz(): void
    {
        $this->getAuthUser();
        $quizId = (int)($_GET['id'] ?? 0);
        if ($quizId <= 0) {
            $this->jsonResponse(['error' => 'Quiz não informado.'], 400);
        }

        $quiz = Quiz::getQuizWithQuestions($quizId);
        if (!$quiz) {
            $this->jsonResponse(['error' => 'Quiz não encontrado.'], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'quiz' => $quiz
        ]);
    }

    public function submitQuiz(): void
    {
        $user = $this->getAuthUser();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $quizId = (int)($data['quiz_id'] ?? 0);
        $answers = (array)($data['answers'] ?? []);
        $timeTaken = max(1, (int)($data['time_taken'] ?? 0));

        if ($quizId <= 0) {
            $this->jsonResponse(['error' => 'Quiz inválido.'], 400);
        }

        $result = Quiz::submitAttempt($quizId, (int)$user['id'], $user['name'] ?? 'Aluno', $answers, $timeTaken);
        $this->jsonResponse($result);
    }

    public function getQuizLeaderboard(): void
    {
        $this->getAuthUser();
        $quizId = (int)($_GET['quiz_id'] ?? 0);
        $leaderboard = Quiz::getQuizLeaderboard($quizId);

        $this->jsonResponse([
            'status' => 'success',
            'leaderboard' => $leaderboard
        ]);
    }
}
