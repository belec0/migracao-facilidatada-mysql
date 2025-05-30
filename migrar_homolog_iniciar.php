<?php
include 'conexao.php';

$senha = $_POST['senha'] ?? '';
$senhaCorreta = $_ENV['SENHA_MIGRACAO_HOMOLOG'] ?? '';

if ($senha !== $senhaCorreta) {
    // http_response_code(401);
    echo json_encode(['erro' => 'Senha incorreta']);
    exit;
}

$res = $conn_local->query("SHOW TABLES");
$tabelas = [];
while ($row = $res->fetch_array()) {
    $tabelas[] = $row[0];
}

echo json_encode($tabelas);
