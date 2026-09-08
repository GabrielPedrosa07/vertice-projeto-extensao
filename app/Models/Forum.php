<?php
/**
 * Model do Fórum de Dúvidas Resolvidas — Vértice
 * Estilo Stack Overflow com perguntas, respostas e solução verificada
 */

namespace App\Models;

use PDO;

class Forum
{
    public static function getQuestions(int $categoryId, string $filter = 'all'): array
    {
        self::ensureSeedData($categoryId);

        $db = Database::getConnection();
        $sql = "
            SELECT q.*, 
                   (SELECT COUNT(*) FROM forum_answers a WHERE a.question_id = q.id) AS answers_count,
                   k.badge_title AS user_badge
            FROM forum_questions q
            LEFT JOIN user_karma k ON q.user_id = k.user_id
            WHERE q.category_id = :cat
        ";

        if ($filter === 'resolved') {
            $sql .= " AND q.is_resolved = 1";
        } elseif ($filter === 'unresolved') {
            $sql .= " AND q.is_resolved = 0";
        }

        $sql .= " ORDER BY q.is_resolved DESC, q.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['cat' => $categoryId]);
        return $stmt->fetchAll();
    }

    public static function getQuestion(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT q.*, k.badge_title AS user_badge
            FROM forum_questions q
            LEFT JOIN user_karma k ON q.user_id = k.user_id
            WHERE q.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createQuestion(int $categoryId, int $userId, string $userName, string $title, string $content, string $tags): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO forum_questions (category_id, user_id, user_name, title, content, tags, created_at)
            VALUES (:cat, :uid, :uname, :title, :content, :tags, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'cat' => $categoryId,
            'uid' => $userId,
            'uname' => $userName,
            'title' => trim($title),
            'content' => trim($content),
            'tags' => trim($tags)
        ]);

        $qId = (int)$db->lastInsertId();

        // Recompensa acadêmica: +5 pontos por publicar dúvida
        Gamification::addPoints($userId, 5);

        return $qId;
    }

    public static function getAnswers(int $questionId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, k.badge_title AS user_badge
            FROM forum_answers a
            LEFT JOIN user_karma k ON a.user_id = k.user_id
            WHERE a.question_id = :qid
            ORDER BY a.is_solution DESC, a.upvotes DESC, a.created_at ASC
        ");
        $stmt->execute(['qid' => $questionId]);
        return $stmt->fetchAll();
    }

    public static function createAnswer(int $questionId, int $userId, string $userName, string $userRole, string $content): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO forum_answers (question_id, user_id, user_name, user_role, content, created_at)
            VALUES (:qid, :uid, :uname, :role, :content, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'qid' => $questionId,
            'uid' => $userId,
            'uname' => $userName,
            'role' => $userRole,
            'content' => trim($content)
        ]);

        $aId = (int)$db->lastInsertId();

        // Recompensa acadêmica: +10 pontos por responder colega
        Gamification::addPoints($userId, 10);

        return $aId;
    }

    public static function markSolution(int $questionId, int $answerId, int $currentUserId): bool
    {
        $db = Database::getConnection();

        // Verifica se a pergunta pertence ao usuário ou se ele é admin
        $q = self::getQuestion($questionId);
        if (!$q) return false;

        $user = User::findById($currentUserId);
        $isAdmin = ($user && in_array($user['role'] ?? '', ['admin', 'moderator']));
        if ($q['user_id'] != $currentUserId && !$isAdmin) {
            return false;
        }

        // Localiza a resposta para identificar o autor que receberá o mérito
        $stmtAns = $db->prepare("SELECT * FROM forum_answers WHERE id = :aid AND question_id = :qid LIMIT 1");
        $stmtAns->execute(['aid' => $answerId, 'qid' => $questionId]);
        $answer = $stmtAns->fetch();
        if (!$answer) return false;

        // Reseta outras respostas da pergunta
        $db->prepare("UPDATE forum_answers SET is_solution = 0 WHERE question_id = :qid")->execute(['qid' => $questionId]);

        // Marca a resposta escolhida como solução
        $db->prepare("UPDATE forum_answers SET is_solution = 1 WHERE id = :aid")->execute(['aid' => $answerId]);

        // Atualiza a pergunta como resolvida
        $db->prepare("UPDATE forum_questions SET is_resolved = 1, solved_answer_id = :aid WHERE id = :qid")->execute([
            'aid' => $answerId,
            'qid' => $questionId
        ]);

        // Recompensa máxima: +30 pontos de Karma e +1 para o contador de soluções do monitor/colega!
        Gamification::addPoints((int)$answer['user_id'], 30, true);

        return true;
    }

    public static function voteAnswer(int $answerId, int $userId): array
    {
        $db = Database::getConnection();

        // Verifica se já votou
        $checkStmt = $db->prepare("SELECT id FROM forum_votes WHERE answer_id = :aid AND user_id = :uid LIMIT 1");
        $checkStmt->execute(['aid' => $answerId, 'uid' => $userId]);
        if ($checkStmt->fetch()) {
            return ['status' => 'already_voted', 'message' => 'Você já avaliou esta resposta como útil.'];
        }

        // Registra voto
        $db->prepare("INSERT INTO forum_votes (answer_id, user_id, created_at) VALUES (:aid, :uid, CURRENT_TIMESTAMP)")
           ->execute(['aid' => $answerId, 'uid' => $userId]);

        // Incrementa contagem de upvotes
        $db->prepare("UPDATE forum_answers SET upvotes = upvotes + 1 WHERE id = :aid")->execute(['aid' => $answerId]);

        // Obtém contagem atualizada e autor para creditar karma
        $stmt = $db->prepare("SELECT user_id, upvotes FROM forum_answers WHERE id = :aid LIMIT 1");
        $stmt->execute(['aid' => $answerId]);
        $ans = $stmt->fetch();

        if ($ans) {
            Gamification::addPoints((int)$ans['user_id'], 5);
            return ['status' => 'success', 'upvotes' => (int)$ans['upvotes']];
        }

        return ['status' => 'success', 'upvotes' => 1];
    }

    public static function incrementViews(int $questionId): void
    {
        $db = Database::getConnection();
        $db->prepare("UPDATE forum_questions SET views = views + 1 WHERE id = :qid")->execute(['qid' => $questionId]);
    }

    private static function ensureSeedData(int $categoryId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM forum_questions WHERE category_id = :cat");
        $stmt->execute(['cat' => $categoryId]);
        if ((int)$stmt->fetchColumn() === 0) {
            // Seed inicial didático
            $stmtInsert = $db->prepare("
                INSERT INTO forum_questions (category_id, user_id, user_name, title, content, tags, is_resolved, created_at)
                VALUES (:cat, 1, 'Gabriel Pedrosa', :title, :content, :tags, :res, CURRENT_TIMESTAMP)
            ");

            $stmtInsert->execute([
                'cat' => $categoryId,
                'title' => 'Qual a diferença prática entre INNER JOIN e LEFT JOIN no MySQL?',
                'content' => "Estou com dúvida sobre quando usar INNER JOIN e quando devo usar LEFT JOIN ao relacionar a tabela de alunos com as notas das disciplinas. Se um aluno ainda não tiver nota lançada, qual join devo usar para ele não sumir do relatório?",
                'tags' => 'sql, join, mysql, modelagem',
                'res' => 1
            ]);
            $q1Id = (int)$db->lastInsertId();

            // Adiciona resposta solucionada
            $stmtAns = $db->prepare("
                INSERT INTO forum_answers (question_id, user_id, user_name, user_role, content, is_solution, upvotes, created_at)
                VALUES (:qid, 2, 'Aluno FASM', 'student', :content, 1, 4, CURRENT_TIMESTAMP)
            ");
            $stmtAns->execute([
                'qid' => $q1Id,
                'content' => "Excelente pergunta, Gabriel!\n\n- **INNER JOIN**: Retorna apenas os registros que existem em AMBAS as tabelas. Ou seja, se o aluno ainda não tiver nota, ele NÃO aparecerá no resultado.\n- **LEFT JOIN**: Retorna TODOS os registros da tabela da esquerda (`alunos`), mesmo que não haja correspondência na tabela da direita (`matriculas`). Nesse caso, os campos da nota virão preenchidos como `NULL`.\n\nPara o seu caso (não deixar o aluno sumir do relatório), você deve usar **LEFT JOIN**! 🚀"
            ]);
            $ansId = (int)$db->lastInsertId();

            $db->prepare("UPDATE forum_questions SET solved_answer_id = :aid WHERE id = :qid")->execute([
                'aid' => $ansId,
                'qid' => $q1Id
            ]);

            // Questão 2 (Aberta / Em andamento)
            $stmtInsert->execute([
                'cat' => $categoryId,
                'title' => 'Quando aplicar a 3ª Forma Normal (3FN) em tabelas com endereço?',
                'content' => "Estou modelando o banco para o trabalho da faculdade e fiquei em dúvida: colocar CEP, Cidade e Estado na mesma tabela de Alunos fere a Terceira Forma Normal por dependência transitiva? Qual a melhor prática recomendada pelo professor?",
                'tags' => 'normalizacao, 3fn, modelagem, arquitetura',
                'res' => 0
            ]);
        }
    }
}
