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
            self::initializeMysqlTables(self::$instance);
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
                self::initializeMysqlTables(self::$instance);
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

    public static function isSqlite(): bool
    {
        return self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public static function initializeMysqlTables(PDO $pdo): void
    {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `call_participants` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `peer_id` VARCHAR(100) NOT NULL,
                    `user_name` VARCHAR(255) NOT NULL,
                    `is_muted` TINYINT(1) DEFAULT 0,
                    `is_video_off` TINYINT(1) DEFAULT 0,
                    `is_screen_sharing` TINYINT(1) DEFAULT 0,
                    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_channel_user` (`channel_id`, `user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `call_signals` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `sender_id` BIGINT UNSIGNED NOT NULL,
                    `receiver_id` BIGINT UNSIGNED NOT NULL,
                    `type` VARCHAR(50) NOT NULL,
                    `payload` LONGTEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `online_users` (
                    `user_id` BIGINT UNSIGNED PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED DEFAULT NULL,
                    `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `remote_control_events` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `target_user_id` BIGINT UNSIGNED NOT NULL,
                    `controller_user_id` BIGINT UNSIGNED NOT NULL,
                    `event_type` VARCHAR(50) NOT NULL,
                    `payload` LONGTEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `channel_code_files` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `filename` VARCHAR(100) NOT NULL DEFAULT 'main.php',
                    `language` VARCHAR(50) NOT NULL DEFAULT 'php',
                    `content` LONGTEXT NOT NULL,
                    `updated_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
                    `updated_by_name` VARCHAR(100) DEFAULT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_channel_code` (`channel_id`, `filename`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `channel_whiteboard_shapes` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED DEFAULT NULL,
                    `user_name` VARCHAR(100) DEFAULT NULL,
                    `shape_data` LONGTEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `forum_questions` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `user_name` VARCHAR(100) NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `content` LONGTEXT NOT NULL,
                    `tags` VARCHAR(255) DEFAULT '',
                    `is_resolved` TINYINT(1) DEFAULT 0,
                    `solved_answer_id` BIGINT UNSIGNED DEFAULT NULL,
                    `views` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `forum_answers` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `question_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `user_name` VARCHAR(100) NOT NULL,
                    `user_role` VARCHAR(50) DEFAULT 'student',
                    `content` LONGTEXT NOT NULL,
                    `is_solution` TINYINT(1) DEFAULT 0,
                    `upvotes` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `forum_votes` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `answer_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_answer_vote` (`answer_id`, `user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `user_karma` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
                    `points` INT DEFAULT 0,
                    `badge_title` VARCHAR(100) DEFAULT 'Iniciante Curioso',
                    `solutions_count` INT DEFAULT 0,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `quizzes` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category_id` BIGINT UNSIGNED NOT NULL,
                    `title` VARCHAR(150) NOT NULL,
                    `description` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `quiz_questions` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `quiz_id` BIGINT UNSIGNED NOT NULL,
                    `question_text` TEXT NOT NULL,
                    `options_json` LONGTEXT NOT NULL,
                    `correct_index` INT NOT NULL,
                    `explanation` TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `quiz_attempts` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `quiz_id` BIGINT UNSIGNED NOT NULL,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `user_name` VARCHAR(100) NOT NULL,
                    `score` INT NOT NULL,
                    `total_questions` INT NOT NULL,
                    `time_taken_seconds` INT DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `friendships` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` BIGINT UNSIGNED NOT NULL,
                    `friend_id` BIGINT UNSIGNED NOT NULL,
                    `status` ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_friendship` (`user_id`, `friend_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `private_messages` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `sender_id` BIGINT UNSIGNED NOT NULL,
                    `receiver_id` BIGINT UNSIGNED NOT NULL,
                    `content` LONGTEXT NOT NULL,
                    `is_code_block` TINYINT(1) DEFAULT 0,
                    `code_language` VARCHAR(50) DEFAULT NULL,
                    `attachment_url` VARCHAR(255) DEFAULT NULL,
                    `attachment_name` VARCHAR(255) DEFAULT NULL,
                    `attachment_type` VARCHAR(50) DEFAULT NULL,
                    `is_read` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `private_call_signals` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `caller_id` BIGINT UNSIGNED NOT NULL,
                    `caller_name` VARCHAR(100) NOT NULL,
                    `receiver_id` BIGINT UNSIGNED NOT NULL,
                    `room_id` VARCHAR(100) NOT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'calling',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `community_invites` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `sender_id` BIGINT UNSIGNED NOT NULL,
                    `sender_name` VARCHAR(100) NOT NULL,
                    `receiver_id` BIGINT UNSIGNED NOT NULL,
                    `category_id` BIGINT UNSIGNED NOT NULL,
                    `category_name` VARCHAR(100) NOT NULL,
                    `category_slug` VARCHAR(100) NOT NULL,
                    `channel_id` BIGINT UNSIGNED NOT NULL,
                    `channel_name` VARCHAR(100) NOT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Verifica colunas de anexo e criador de comunidade
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_url` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_type` VARCHAR(50) DEFAULT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE `categories` ADD COLUMN `creator_id` BIGINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}
        } catch (\Throwable $e) {}
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
                attachment_url TEXT,
                attachment_name TEXT,
                attachment_type TEXT,
                parent_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS call_participants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                peer_id TEXT NOT NULL,
                user_name TEXT NOT NULL,
                is_muted INTEGER DEFAULT 0,
                is_video_off INTEGER DEFAULT 0,
                is_screen_sharing INTEGER DEFAULT 0,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(channel_id, user_id)
            );

            CREATE TABLE IF NOT EXISTS call_signals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                payload TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS online_users (
                user_id INTEGER PRIMARY KEY,
                channel_id INTEGER,
                last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
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

            CREATE TABLE IF NOT EXISTS remote_control_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                target_user_id INTEGER NOT NULL,
                controller_user_id INTEGER NOT NULL,
                event_type TEXT NOT NULL,
                payload TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS channel_code_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                filename TEXT NOT NULL DEFAULT 'main.php',
                language TEXT NOT NULL DEFAULT 'php',
                content TEXT NOT NULL,
                updated_by_user_id INTEGER,
                updated_by_name TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (channel_id, filename)
            );

            CREATE TABLE IF NOT EXISTS channel_whiteboard_shapes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                user_id INTEGER,
                user_name TEXT,
                shape_data TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS forum_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                user_name TEXT NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                tags TEXT DEFAULT '',
                is_resolved INTEGER DEFAULT 0,
                solved_answer_id INTEGER DEFAULT NULL,
                views INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS forum_answers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                question_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                user_name TEXT NOT NULL,
                user_role TEXT DEFAULT 'student',
                content TEXT NOT NULL,
                is_solution INTEGER DEFAULT 0,
                upvotes INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS forum_votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                answer_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (answer_id, user_id)
            );

            CREATE TABLE IF NOT EXISTS user_karma (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                points INTEGER DEFAULT 0,
                badge_title TEXT DEFAULT 'Iniciante Curioso',
                solutions_count INTEGER DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS quizzes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS quiz_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                question_text TEXT NOT NULL,
                options_json TEXT NOT NULL,
                correct_index INTEGER NOT NULL,
                explanation TEXT
            );

            CREATE TABLE IF NOT EXISTS quiz_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                user_name TEXT NOT NULL,
                score INTEGER NOT NULL,
                total_questions INTEGER NOT NULL,
                time_taken_seconds INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS friendships (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                friend_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, friend_id)
            );

            CREATE TABLE IF NOT EXISTS private_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                is_code_block INTEGER DEFAULT 0,
                code_language TEXT DEFAULT NULL,
                attachment_url TEXT DEFAULT NULL,
                attachment_name TEXT DEFAULT NULL,
                attachment_type TEXT DEFAULT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS private_call_signals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                caller_id INTEGER NOT NULL,
                caller_name TEXT NOT NULL,
                receiver_id INTEGER NOT NULL,
                room_id TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'calling',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS community_invites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                sender_name TEXT NOT NULL,
                receiver_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                category_name TEXT NOT NULL,
                category_slug TEXT NOT NULL,
                channel_id INTEGER NOT NULL,
                channel_name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Atualiza colunas na tabela messages se não existirem
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN attachment_url TEXT");
        } catch (\Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN attachment_name TEXT");
        } catch (\Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN attachment_type TEXT");
        } catch (\Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE categories ADD COLUMN creator_id INTEGER DEFAULT NULL");
        } catch (\Throwable $e) {}

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
