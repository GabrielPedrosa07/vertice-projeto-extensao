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
            ");

            // Verifica colunas de anexo
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_url` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
            try { $pdo->exec("ALTER TABLE `messages` ADD COLUMN `attachment_type` VARCHAR(50) DEFAULT NULL"); } catch (\Throwable $e) {}
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
