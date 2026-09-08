<?php
/**
 * Model do SQL Playground Didático — Vértice
 * Gerencia o banco SQLite isolado com dados universitários para estudo
 */

namespace App\Models;

use PDO;
use PDOException;

class SqlPlayground
{
    private static ?PDO $pdo = null;
    private static string $dbPath = __DIR__ . '/../../banco_de_dados/vertice_sql_playground.sqlite';

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $isNew = !file_exists(self::$dbPath);
        self::$pdo = new PDO('sqlite:' . self::$dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($isNew) {
            self::resetDatabase();
        }

        return self::$pdo;
    }

    /**
     * Reseta ou inicializa o banco didático com dados acadêmicos realistas
     */
    public static function resetDatabase(): void
    {
        $db = self::$pdo ?? new PDO('sqlite:' . self::$dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Remove tabelas existentes
        $db->exec("DROP TABLE IF EXISTS matriculas;");
        $db->exec("DROP TABLE IF EXISTS projetos_extensao;");
        $db->exec("DROP TABLE IF EXISTS disciplinas;");
        $db->exec("DROP TABLE IF EXISTS professores;");
        $db->exec("DROP TABLE IF EXISTS alunos;");

        // Criação de Tabelas
        $db->exec("
            CREATE TABLE alunos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome VARCHAR(100) NOT NULL,
                matricula VARCHAR(20) UNIQUE NOT NULL,
                curso VARCHAR(80) NOT NULL,
                periodo INTEGER NOT NULL,
                cidade VARCHAR(60) NOT NULL,
                coeficiente_rendimento DECIMAL(4,2) DEFAULT 0.0
            );

            CREATE TABLE professores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome VARCHAR(100) NOT NULL,
                titulacao VARCHAR(30) NOT NULL,
                departamento VARCHAR(60) NOT NULL,
                email VARCHAR(100) NOT NULL
            );

            CREATE TABLE disciplinas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo VARCHAR(20) UNIQUE NOT NULL,
                nome VARCHAR(100) NOT NULL,
                professor_id INTEGER,
                carga_horaria INTEGER NOT NULL,
                FOREIGN KEY (professor_id) REFERENCES professores(id)
            );

            CREATE TABLE matriculas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                aluno_id INTEGER NOT NULL,
                disciplina_id INTEGER NOT NULL,
                semestre VARCHAR(10) NOT NULL,
                nota_1 DECIMAL(4,1),
                nota_2 DECIMAL(4,1),
                media DECIMAL(4,1),
                status VARCHAR(20) DEFAULT 'Cursando',
                FOREIGN KEY (aluno_id) REFERENCES alunos(id),
                FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id)
            );

            CREATE TABLE projetos_extensao (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(150) NOT NULL,
                orientador_id INTEGER,
                area VARCHAR(80) NOT NULL,
                vagas INTEGER NOT NULL,
                bolsa DECIMAL(8,2) DEFAULT 0.00,
                FOREIGN KEY (orientador_id) REFERENCES professores(id)
            );
        ");

        // Inserção de Dados Realistas da Faculdade
        $db->exec("
            INSERT INTO professores (nome, titulacao, departamento, email) VALUES
            ('Dr. Marcos Silva', 'Doutor', 'Ciência da Computação', 'marcos.silva@fasm.edu.br'),
            ('Msc. Juliana Rocha', 'Mestre', 'Sistemas de Informação', 'juliana.rocha@fasm.edu.br'),
            ('Dra. Beatriz Santos', 'Doutora', 'Matemática e Estatística', 'beatriz.santos@fasm.edu.br'),
            ('Msc. Rodrigo Nogueira', 'Mestre', 'Engenharia de Software', 'rodrigo.nogueira@fasm.edu.br');

            INSERT INTO alunos (nome, matricula, curso, periodo, cidade, coeficiente_rendimento) VALUES
            ('Gabriel Pedrosa', '20241001', 'Ciência da Computação', 4, 'Muriaé', 9.2),
            ('Ana Clara Oliveira', '20241002', 'Sistemas de Informação', 3, 'Cataguases', 8.5),
            ('Lucas Mendonça', '20231015', 'Ciência da Computação', 6, 'Ubá', 7.8),
            ('Mariana Costa', '20241003', 'Ciência da Computação', 4, 'Muriaé', 9.0),
            ('Felipe Almeida', '20232008', 'Sistemas de Informação', 5, 'Viçosa', 6.9),
            ('Camila Barbosa', '20251001', 'Ciência da Computação', 1, 'Muriaé', 8.1),
            ('Rafael Zaneti', '20241009', 'Sistemas de Informação', 4, 'Juiz de Fora', 7.4),
            ('Larissa Fontes', '20231022', 'Ciência da Computação', 6, 'Muriaé', 8.9);

            INSERT INTO disciplinas (codigo, nome, professor_id, carga_horaria) VALUES
            ('CC401', 'Banco de Dados Relacional', 1, 80),
            ('CC402', 'Estruturas de Dados Avançadas', 2, 80),
            ('SI301', 'Engenharia de Software', 4, 60),
            ('MAT201', 'Cálculo e Álgebra Linear', 3, 80),
            ('CC403', 'Redes de Computadores e WebRTC', 1, 60);

            INSERT INTO matriculas (aluno_id, disciplina_id, semestre, nota_1, nota_2, media, status) VALUES
            (1, 1, '2026/1', 9.5, 9.0, 9.25, 'Aprovado'),
            (1, 2, '2026/1', 8.5, 9.0, 8.75, 'Aprovado'),
            (1, 5, '2026/1', 9.0, 9.5, 9.25, 'Aprovado'),
            (2, 1, '2026/1', 8.0, 8.5, 8.25, 'Aprovado'),
            (2, 3, '2026/1', 9.0, 8.0, 8.50, 'Aprovado'),
            (3, 2, '2026/1', 6.5, 7.0, 6.75, 'Aprovado'),
            (3, 4, '2026/1', 5.0, 5.5, 5.25, 'Recuperação'),
            (4, 1, '2026/1', 9.0, 9.5, 9.25, 'Aprovado'),
            (4, 3, '2026/1', 8.5, 9.0, 8.75, 'Aprovado'),
            (5, 3, '2026/1', 6.0, 6.0, 6.00, 'Aprovado'),
            (5, 4, '2026/1', 4.0, 5.0, 4.50, 'Reprovado'),
            (7, 1, '2026/1', 7.5, 7.5, 7.50, 'Aprovado'),
            (8, 2, '2026/1', 9.0, 8.5, 8.75, 'Aprovado');

            INSERT INTO projetos_extensao (titulo, orientador_id, area, vagas, bolsa) VALUES
            ('Vértice — Plataforma Colaborativa de Estudos', 1, 'Extensão Universitária & Web', 4, 700.00),
            ('Inclusão Digital para Terceira Idade', 2, 'Sociedade & Informática', 3, 500.00),
            ('Monitoria Inteligente em Banco de Dados', 1, 'Ensino e Aprendizagem', 2, 600.00);
        ");

        self::$pdo = $db;
    }

    /**
     * Executa uma consulta SQL e retorna métricas, colunas e registros
     */
    public static function executeQuery(string $query): array
    {
        $db = self::getConnection();
        $trimmed = trim($query);

        if (empty($trimmed)) {
            return [
                'status' => 'error',
                'message' => 'Digite uma consulta SQL para executar.'
            ];
        }

        $startTime = microtime(true);

        try {
            // Executa com limite seguro se não tiver LIMIT para SELECT
            $isSelect = preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $trimmed);

            $stmt = $db->query($trimmed);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($isSelect && $stmt) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = [];
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                } elseif ($stmt->columnCount() > 0) {
                    for ($i = 0; $i < $stmt->columnCount(); $i++) {
                        $meta = $stmt->getColumnMeta($i);
                        $columns[] = $meta['name'] ?? "col_{$i}";
                    }
                }

                return [
                    'status' => 'success',
                    'type' => 'select',
                    'columns' => $columns,
                    'rows' => $rows,
                    'count' => count($rows),
                    'duration_ms' => $durationMs
                ];
            } else {
                $affected = $stmt ? $stmt->rowCount() : 0;
                return [
                    'status' => 'success',
                    'type' => 'mutation',
                    'affected_rows' => $affected,
                    'duration_ms' => $durationMs,
                    'message' => "Comando executado com sucesso! ({$affected} linhas afetadas em {$durationMs}ms)"
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Erro SQL: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Retorna a lista de tabelas e suas colunas para a barra lateral do explorador
     */
    public static function getSchemaOverview(): array
    {
        $db = self::getConnection();
        $tables = [];

        try {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC");
            $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tableNames as $tName) {
                $colStmt = $db->query("PRAGMA table_info(`{$tName}`)");
                $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);

                $countStmt = $db->query("SELECT COUNT(*) FROM `{$tName}`");
                $rowCount = (int)$countStmt->fetchColumn();

                $tables[] = [
                    'name' => $tName,
                    'columns' => $columns,
                    'row_count' => $rowCount
                ];
            }
        } catch (PDOException $e) {}

        return $tables;
    }
}
