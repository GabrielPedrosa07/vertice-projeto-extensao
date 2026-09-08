<?php
/**
 * Model de Quizzes Rápidos de Revisão Pré-Prova — Vértice
 * Estilo Kahoot universitário com pontuação, cronômetro e ranking da turma
 */

namespace App\Models;

use PDO;

class Quiz
{
    public static function getQuizzesByCategory(int $categoryId): array
    {
        self::ensureSeedQuizzes($categoryId);

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT q.*, 
                   (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS total_questions,
                   (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) AS total_attempts
            FROM quizzes q
            WHERE q.category_id = :cat
            ORDER BY q.id ASC
        ");
        $stmt->execute(['cat' => $categoryId]);
        return $stmt->fetchAll();
    }

    public static function getQuizWithQuestions(int $quizId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $quizId]);
        $quiz = $stmt->fetch();
        if (!$quiz) return null;

        $stmtQ = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = :qid ORDER BY id ASC");
        $stmtQ->execute(['qid' => $quizId]);
        $questions = $stmtQ->fetchAll();

        foreach ($questions as &$q) {
            $q['options'] = is_string($q['options_json']) ? json_decode($q['options_json'], true) : $q['options_json'];
            // Oculta resposta correta do payload inicial do jogo para evitar trapaça no client-side
            unset($q['options_json']);
        }

        $quiz['questions'] = $questions;
        return $quiz;
    }

    public static function submitAttempt(int $quizId, int $userId, string $userName, array $answers, int $timeTaken): array
    {
        $db = Database::getConnection();

        // Carrega as perguntas com o gabarito oficial
        $stmt = $db->prepare("SELECT id, correct_index, explanation FROM quiz_questions WHERE quiz_id = :qid ORDER BY id ASC");
        $stmt->execute(['qid' => $quizId]);
        $questions = $stmt->fetchAll();

        $score = 0;
        $total = count($questions);
        $review = [];

        foreach ($questions as $q) {
            $userAns = isset($answers[$q['id']]) ? (int)$answers[$q['id']] : -1;
            $isCorrect = ($userAns === (int)$q['correct_index']);
            if ($isCorrect) {
                $score++;
            }

            $review[] = [
                'question_id' => $q['id'],
                'user_answer' => $userAns,
                'correct_index' => (int)$q['correct_index'],
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'] ?? ''
            ];
        }

        // Salva a tentativa
        $stmtSave = $db->prepare("
            INSERT INTO quiz_attempts (quiz_id, user_id, user_name, score, total_questions, time_taken_seconds, created_at)
            VALUES (:qid, :uid, :uname, :score, :total, :time, CURRENT_TIMESTAMP)
        ");
        $stmtSave->execute([
            'qid' => $quizId,
            'uid' => $userId,
            'uname' => $userName,
            'score' => $score,
            'total' => $total,
            'time' => $timeTaken
        ]);

        // Recompensa acadêmica: +10 pontos de Karma por acerto
        $karmaEarned = $score * 10;
        if ($karmaEarned > 0) {
            Gamification::addPoints($userId, $karmaEarned);
        }

        return [
            'status' => 'success',
            'score' => $score,
            'total' => $total,
            'percentage' => round(($score / max(1, $total)) * 100, 1),
            'time_taken' => $timeTaken,
            'karma_earned' => $karmaEarned,
            'review' => $review
        ];
    }

    public static function getQuizLeaderboard(int $quizId, int $limit = 8): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT qa.*, k.badge_title
            FROM quiz_attempts qa
            LEFT JOIN user_karma k ON qa.user_id = k.user_id
            WHERE qa.quiz_id = :qid
            ORDER BY qa.score DESC, qa.time_taken_seconds ASC, qa.created_at ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':qid', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function ensureSeedQuizzes(int $categoryId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM quizzes WHERE category_id = :cat");
        $stmt->execute(['cat' => $categoryId]);
        if ((int)$stmt->fetchColumn() === 0) {
            $stmtQ = $db->prepare("
                INSERT INTO quizzes (category_id, title, description, created_at)
                VALUES (:cat, :title, :desc, CURRENT_TIMESTAMP)
            ");
            $stmtQ->execute([
                'cat' => $categoryId,
                'title' => 'Simulado Rápido de Banco de Dados — P1',
                'desc' => 'Revise os conceitos essenciais de SQL, chaves primárias, normalização e tipos de JOIN antes da prova!'
            ]);
            $quizId = (int)$db->lastInsertId();

            $insertQuest = $db->prepare("
                INSERT INTO quiz_questions (quiz_id, question_text, options_json, correct_index, explanation)
                VALUES (:qid, :text, :opts, :corr, :exp)
            ");

            // Questão 1
            $insertQuest->execute([
                'qid' => $quizId,
                'text' => 'Qual cláusula do SQL é utilizada para filtrar registros APÓS um agrupamento com GROUP BY?',
                'opts' => json_encode(['WHERE', 'HAVING', 'ORDER BY', 'LIMIT']),
                'corr' => 1,
                'exp' => 'A cláusula WHERE filtra registros antes do agrupamento; para filtrar grupos ou funções agregadas (como SUM, AVG, COUNT), utiliza-se HAVING.'
            ]);

            // Questão 2
            $insertQuest->execute([
                'qid' => $quizId,
                'text' => 'Para garantir que uma tabela esteja na 1ª Forma Normal (1FN), o que é estritamente obrigatório?',
                'opts' => json_encode([
                    'Todas as chaves estrangeiras devem ser indexadas.',
                    'Os atributos devem conter apenas valores atômicos (indivisíveis) e sem grupos repetitivos.',
                    'Não podem existir dependências transitivas entre colunas não-chave.',
                    'Deve existir uma tabela intermediária para cada relacionamento 1:N.'
                ]),
                'corr' => 1,
                'exp' => 'A 1FN exige que cada coluna contenha apenas valores atômicos e que não haja repetição de listas ou arrays dentro de um mesmo campo.'
            ]);

            // Questão 3
            $insertQuest->execute([
                'qid' => $quizId,
                'text' => 'Qual o comando SQL correto para remover todos os dados de uma tabela de forma rápida e resetar os auto-incrementos?',
                'opts' => json_encode(['DELETE ALL FROM tabela;', 'DROP TABLE tabela;', 'TRUNCATE TABLE tabela;', 'CLEAR TABLE tabela;']),
                'corr' => 2,
                'exp' => 'O comando TRUNCATE TABLE remove todas as linhas rapidamente através de desacolocação de páginas e reseta o contador de chave primária AUTO_INCREMENT.'
            ]);

            // Questão 4
            $insertQuest->execute([
                'qid' => $quizId,
                'text' => 'Em um relacionamento N:M (Muitos para Muitos) entre Alunos e Disciplinas, como ele é fisicamente implementado em um SGBD relacional?',
                'opts' => json_encode([
                    'Adicionando o ID da disciplina como array dentro da tabela de Alunos.',
                    'Criando uma tabela associativa intermediária contendo as chaves estrangeiras de ambas.',
                    'Usando uma chave primária composta diretamente na tabela Disciplinas.',
                    'SGBDs relacionais não suportam relacionamentos N:M.'
                ]),
                'corr' => 1,
                'exp' => 'Relacionamentos N:M são resolvidos decompondo-se em dois relacionamentos 1:N através de uma tabela associativa (tabela de junção, ex: `matriculas`).'
            ]);
        }
    }
}
