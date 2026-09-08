<?php
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) require $file;
});

$res = \App\Models\SqlPlayground::executeQuery('SELECT a.nome, d.nome as disciplina, m.media, m.status FROM matriculas m JOIN alunos a ON m.aluno_id = a.id JOIN disciplinas d ON m.disciplina_id = d.id LIMIT 3;');
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

$schema = \App\Models\SqlPlayground::getSchemaOverview();
echo "Tables found: " . count($schema) . "\n";
foreach ($schema as $t) {
    echo "- Table {$t['name']}: {$t['row_count']} rows\n";
}
