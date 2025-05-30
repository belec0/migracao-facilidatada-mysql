<?php
// Carrega variáveis do arquivo .env
function carregarEnv($caminho = __DIR__ . '/.env') {
    if (!file_exists($caminho)) return;

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) continue; // ignora comentários
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);
        $_ENV[$chave] = $valor;
    }
}

carregarEnv();

// Conexões usando variáveis do .env
$conn_local = new mysqli(
    $_ENV['DB_LOCAL_HOST'] ?? 'localhost',
    $_ENV['DB_LOCAL_USER'] ?? 'root',
    $_ENV['DB_LOCAL_PASS'] ?? '',
    $_ENV['DB_LOCAL_NAME'] ?? 'banco'
);

$conn_homolog = new mysqli(
    $_ENV['DB_HML_HOST'] ?? 'localhost',
    $_ENV['DB_HML_USER'] ?? 'root',
    $_ENV['DB_HML_PASS'] ?? '',
    $_ENV['DB_HML_NAME'] ?? 'banco_hml'
);

$conn_producao = new mysqli(
    $_ENV['DB_PRD_HOST'] ?? 'localhost',
    $_ENV['DB_PRD_USER'] ?? 'root',
    $_ENV['DB_PRD_PASS'] ?? '',
    $_ENV['DB_PRD_NAME'] ?? 'banco_prd'
);
