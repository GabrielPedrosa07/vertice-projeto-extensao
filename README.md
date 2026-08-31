# Vértice — Plataforma de Estudos e Comunicação

> **Projeto de Extensão Universitária III**  
> Curso: Análise e Desenvolvimento de Sistemas (ADS)  
> Instituição: Faculdade de Santa Marcelina (FASM)  
> Orientador: Prof. Aldecir de Almeida Fonseca

---

## 👥 Equipe do Projeto

- **Gabriel Pedrosa** — Desenvolvedor
- **Guilherme Victor** — Desenvolvedor
- **Iuri Schelbi** — Desenvolvedor
- **João Carlos** — Desenvolvedor

---

## 📖 Sobre o Projeto

O **Vértice** é uma plataforma acadêmica de conversas em tempo real, fórum colaborativo e compartilhamento de materiais de estudo, desenvolvida em **PHP Puro (PHP 8.3 Nativo)** com **PDO**, **MySQL** e arquitetura **MVC**.

O projeto nasceu com o objetivo de oferecer um canal de comunicação acadêmico autônomo, seguro e descentralizado, livre de moderações algorítmicas opacas e com histórico auditável de moderação.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.3 Puro (Nativo) com PDO e Padrão Arquitetural MVC
- **Banco de Dados:** MySQL 8.0+ / MariaDB
- **Frontend:** HTML5 semântico, CSS3 Glassmorphism Dark Mode e JavaScript ES6
- **Autenticação:** Sessões nativas do PHP com criptografia de senhas em Bcrypt (`password_hash`)
- **Versionamento:** Git & GitHub

---

## 📁 Estrutura de Diretórios

```
VERTICE/
├── app/
│   ├── Controllers/        # Controladores (Home, Auth, Hub, Comunidades)
│   ├── Helpers/            # Utilitários (Session, Auth, Flash messages)
│   └── Models/             # Modelos de Dados com PDO (User, Category, Channel, Message)
├── banco_de_dados/         # Scripts DDL (schema_vertice.sql e seed_vertice.sql)
├── config/                 # Configuração de banco de dados (database.php)
├── Documentos do Projeto/  # Documento de Requisitos (PDF/DOCX) e Modelo ER
├── public/                 # Arquivos públicos (CSS, Imagens e JS)
│   ├── css/style.css
│   └── img/logo-fasm.png
├── views/                  # Telas do sistema (Layouts, Auth, Hub, Páginas)
├── .htaccess               # Configuração do Apache mod_rewrite
├── index.php               # Front Controller & Roteador Principal
└── README.md
```

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
- PHP 8.2 ou superior instalado (ex: WampServer, XAMPP ou PHP CLI)
- MySQL 8.0+ ou MariaDB

### Passo a Passo

1. **Importar o Banco de Dados:**
   - Execute o script `banco_de_dados/schema_vertice.sql` no seu gerenciador MySQL (ex: phpMyAdmin ou CLI).
   - Execute o script `banco_de_dados/seed_vertice.sql` para carregar as disciplinas e canais iniciais.

2. **Configurar o Banco:**
   - Se necessário, ajuste as credenciais de acesso em `config/database.php`.

3. **Iniciar o Servidor:**
   - Pelo terminal, na raiz do projeto:
     ```bash
     php -S localhost:8000
     ```
   - Ou acesse via Apache do WampServer (`http://localhost/vertice`).

4. **Acessar a Plataforma:**
   - Abra o navegador em: **`http://localhost:8000`**
   - Cadastre uma conta em **`http://localhost:8000/cadastro`** e acesse o **Hub de Comunidades**.
