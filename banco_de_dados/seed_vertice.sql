-- ============================================================================
-- PLATAFORMA VÉRTICE - SEED DE DADOS INICIAIS
-- ============================================================================

USE `vertice_db`;

-- Categorias / Disciplinas
INSERT INTO `categories` (`name`, `slug`, `description`, `color`, `icon`) VALUES
('Banco de Dados', 'banco-de-dados', 'Modelagem relacional, SQL, normalização de dados e SGBDs.', '#4ecdc4', 'database'),
('Redes de Computadores', 'redes-de-computadores', 'Protocolos TCP/IP, roteamento, camadas OSI e segurança de redes.', '#5b6abf', 'network'),
('Programação Orientada a Objetos', 'poo', 'Classes, herança, polimorfismo, interfaces e design patterns.', '#9b72cf', 'code'),
('Engenharia de Software', 'engenharia-de-software', 'Metodologias ágeis, diagramas UML, requisitos e arquitetura de software.', '#e8915a', 'design'),
('Estrutura de Dados', 'estrutura-de-dados', 'Listas, pilhas, filas, árvores binárias, grafos e algoritmos de ordenação.', '#45b87f', 'tree'),
('Desenvolvimento Web', 'desenvolvimento-web', 'HTML5, CSS3, JavaScript, PHP nativo, APIs REST e aplicações web.', '#d9534f', 'web')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Canais Iniciais
INSERT INTO `channels` (`category_id`, `name`, `slug`, `description`) VALUES
(1, 'Geral', 'geral', 'Canal principal para discussões de Banco de Dados'),
(1, 'Dúvidas de SQL', 'duvidas-sql', 'Tire dúvidas sobre consultas, joins e modelagem'),
(2, 'Geral', 'geral', 'Canal principal de Redes de Computadores'),
(3, 'Geral', 'geral', 'Canal principal de Programação Orientada a Objetos'),
(4, 'Geral', 'geral', 'Canal principal de Engenharia de Software'),
(5, 'Geral', 'geral', 'Canal principal de Estrutura de Dados'),
(6, 'Geral', 'geral', 'Canal principal de Desenvolvimento Web');
