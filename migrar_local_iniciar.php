<?php
include 'conexao.php';

$senha = $_POST['senha'] ?? '';
$senhaCorreta = $_ENV['SENHA_MIGRACAO_PRODUCAO'] ?? '';

if ($senha !== $senhaCorreta) {
    // http_response_code(401);
    echo json_encode(['erro' => 'Senha incorreta']);
    exit;
}

$res = $conn_producao->query("SHOW TABLES");
$tabelas = [];
while ($row = $res->fetch_array()) {
    $tabelas[] = $row[0];
}

echo json_encode($tabelas);
