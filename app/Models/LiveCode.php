<?php
/**
 * Model de Código Colaborativo em Tempo Real — Vértice
 */

namespace App\Models;

use PDO;

class LiveCode
{
    /**
     * Retorna o arquivo de código ativo de um canal ou cria com template padrão
     */
    public static function getActiveFile(int $channelId, string $filename = 'main.php'): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM channel_code_files
            WHERE channel_id = :ch AND filename = :fn
            LIMIT 1
        ");
        $stmt->execute(['ch' => $channelId, 'fn' => $filename]);
        $file = $stmt->fetch();

        if (!$file) {
            $defaultLang = 'php';
            $defaultContent = self::getDefaultTemplate($defaultLang);

            $insertStmt = $db->prepare("
                INSERT INTO channel_code_files (channel_id, filename, language, content, updated_by_name)
                VALUES (:ch, :fn, :lang, :content, 'Sistema')
            ");
            $insertStmt->execute([
                'ch' => $channelId,
                'fn' => $filename,
                'lang' => $defaultLang,
                'content' => $defaultContent
            ]);

            return [
                'id' => (int)$db->lastInsertId(),
                'channel_id' => $channelId,
                'filename' => $filename,
                'language' => $defaultLang,
                'content' => $defaultContent,
                'updated_by_name' => 'Sistema',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return $file;
    }

    /**
     * Salva ou atualiza o conteúdo e linguagem de um arquivo de código
     */
    public static function saveFile(int $channelId, string $filename, string $language, string $content, ?int $userId, string $userName): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO channel_code_files (channel_id, filename, language, content, updated_by_user_id, updated_by_name, updated_at)
            VALUES (:ch, :fn, :lang, :content, :uid, :uname, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                language = VALUES(language),
                content = VALUES(content),
                updated_by_user_id = VALUES(updated_by_user_id),
                updated_by_name = VALUES(updated_by_name),
                updated_at = CURRENT_TIMESTAMP
        ");

        try {
            return $stmt->execute([
                'ch' => $channelId,
                'fn' => $filename,
                'lang' => $language,
                'content' => $content,
                'uid' => $userId,
                'uname' => $userName
            ]);
        } catch (\Throwable $e) {
            // Fallback para SQLite
            $sqliteStmt = $db->prepare("
                INSERT INTO channel_code_files (channel_id, filename, language, content, updated_by_user_id, updated_by_name, updated_at)
                VALUES (:ch, :fn, :lang, :content, :uid, :uname, CURRENT_TIMESTAMP)
                ON CONFLICT(channel_id, filename) DO UPDATE SET
                    language = excluded.language,
                    content = excluded.content,
                    updated_by_user_id = excluded.updated_by_user_id,
                    updated_by_name = excluded.updated_by_name,
                    updated_at = CURRENT_TIMESTAMP
            ");
            return $sqliteStmt->execute([
                'ch' => $channelId,
                'fn' => $filename,
                'lang' => $language,
                'content' => $content,
                'uid' => $userId,
                'uname' => $userName
            ]);
        }
    }

    /**
     * Template didático de boas-vindas para cada linguagem
     */
    public static function getDefaultTemplate(string $language): string
    {
        switch (strtolower($language)) {
            case 'python':
                return "# Vértice — Live Code Editor Colaborativo\n# Linguagem: Python 3\n\ndef calcular_media(notas):\n    return sum(notas) / len(notas)\n\nnotas_aluno = [8.5, 9.0, 7.5, 9.5]\nmedia = calcular_media(notas_aluno)\n\nprint(f'Notas: {notas_aluno}')\nprint(f'Média final: {media:.2f}')\nif media >= 7.0:\n    print('Status: APROVADO! Parabéns!')\n";
            case 'javascript':
                return "// Vértice — Live Code Editor Colaborativo\n// Linguagem: JavaScript (ES6)\n\nfunction fibonacci(n) {\n    const seq = [0, 1];\n    for (let i = 2; i < n; i++) {\n        seq.push(seq[i - 1] + seq[i - 2]);\n    }\n    return seq;\n}\n\nconsole.log('🚀 Sequência de Fibonacci (10 primeiros termos):');\nconsole.log(fibonacci(10));\n";
            case 'sql':
                return "-- Vértice — Live Code Editor Colaborativo\n-- Linguagem: SQL\n\nSELECT a.nome, d.nome AS disciplina, m.media, m.status\nFROM matriculas m\nJOIN alunos a ON m.aluno_id = a.id\nJOIN disciplinas d ON m.disciplina_id = d.id\nWHERE m.media >= 7.0\nORDER BY m.media DESC;\n";
            case 'c_cpp':
            case 'cpp':
            case 'c':
                return "// Vértice — Live Code Editor Colaborativo\n// Linguagem: C / C++\n\n#include <stdio.h>\n\nint main() {\n    printf(\"Olá do Vértice! Programando em C colaborativo.\\n\");\n    int a = 15, b = 25;\n    printf(\"Soma: %d + %d = %d\\n\", a, b, a + b);\n    return 0;\n}\n";
            case 'php':
            default:
                return "<?php\n/**\n * Vértice — Live Code Editor Colaborativo\n * Digite código com seus colegas de sala em tempo real!\n */\n\n\$alunos = [\n    ['nome' => 'Gabriel', 'nota' => 9.5],\n    ['nome' => 'Colega', 'nota' => 8.8]\n];\n\necho \"=== Alunos da Monitoria Vértice ===\\n\";\nforeach (\$alunos as \$a) {\n    echo \"Aluno: {\$a['nome']} — Nota: {\$a['nota']}\\n\";\n}\n";
        }
    }
}
