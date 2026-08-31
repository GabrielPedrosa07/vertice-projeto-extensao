<?php
/**
 * Conexão com Banco de Dados via PDO — Vértice
 * PHP Puro
 */

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require __DIR__ . '/../../config/database.php';

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            self::$instance = new PDO($dsn, $config['user'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Tenta criar o banco caso não exista no MySQL
            try {
                $dsnWithoutDb = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
                $tempPdo = new PDO($dsnWithoutDb, $config['user'], $config['password'], $config['options']);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
                self::$instance = new PDO($dsn, $config['user'], $config['password'], $config['options']);
                
                // Inicializa schema
                self::initializeSchema(self::$instance);
            } catch (PDOException $ex) {
                // Fallback automático para SQLite local garantindo funcionamento mesmo sem MySQL ativo no momento
                $sqlitePath = __DIR__ . '/../../banco_de_dados/vertice_local.sqlite';
                self::$instance = new PDO("sqlite:" . $sqlitePath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::initializeSqliteSchema(self::$instance);
            }
        }

        return self::$instance;
    }

    public static function initializeSchema(PDO $pdo): void
    {
        $schemaFile = __DIR__ . '/../../banco_de_dados/schema_vertice.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
        $seedFile = __DIR__ . '/../../banco_de_dados/seed_vertice.sql';
        if (file_exists($seedFile)) {
            $sql = file_get_contents($seedFile);
            $pdo->exec($sql);
        }
    }

    private static function initializeSqliteSchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                phone TEXT,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'student',
                avatar_url TEXT,
                bio TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                color TEXT DEFAULT '#5b6abf',
                icon TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS channels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT,
                category_id INTEGER NOT NULL,
                is_private INTEGER DEFAULT 0,
                created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                is_code_block INTEGER DEFAULT 0,
                code_language TEXT,
                parent_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS community_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                role TEXT DEFAULT 'member',
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, category_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );
        ");

        // Seed inicial caso esteja vazio
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
        $count = $stmt->fetch()['total'] ?? 0;
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO categories (name, slug, description, color, icon) VALUES
                ('Banco de Dados', 'banco-de-dados', 'Modelagem relacional, SQL, normalização de dados e SGBDs.', '#4ecdc4', '🗄️'),
                ('Redes de Computadores', 'redes-de-computadores', 'Protocolos TCP/IP, roteamento, camadas OSI e segurança de redes.', '#5b6abf', '🌐'),
                ('Programação Orientada a Objetos', 'poo', 'Classes, herança, polimorfismo, interfaces e design patterns.', '#9b72cf', '☕'),
                ('Engenharia de Software', 'engenharia-de-software', 'Metodologias ágeis, diagramas UML, requisitos e arquitetura de software.', '#e8915a', '📐'),
                ('Estrutura de Dados', 'estrutura-de-dados', 'Listas, pilhas, filas, árvores binárias, grafos e algoritmos de ordenação.', '#45b87f', '🌳'),
                ('Desenvolvimento Web', 'desenvolvimento-web', 'HTML5, CSS3, JavaScript, PHP nativo, APIs REST e aplicações web.', '#d9534f', '💻');

                INSERT INTO channels (name, slug, description, category_id) VALUES
                ('Geral', 'geral', 'Canal principal para discussões da matéria', 1),
                ('Dúvidas de SQL', 'duvidas-sql', 'Tire dúvidas sobre consultas e modelagem', 1),
                ('Geral', 'geral', 'Canal principal de Redes', 2),
                ('Geral', 'geral', 'Canal principal de POO', 3),
                ('Geral', 'geral', 'Canal principal de Engenharia de Software', 4),
                ('Geral', 'geral', 'Canal principal de Estrutura de Dados', 5),
                ('Geral', 'geral', 'Canal principal de Desenvolvimento Web', 6);
            ");
        }
    }
}
