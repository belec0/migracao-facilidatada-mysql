<?php
include 'conexao.php';

$senhaInformada = $_POST['senha'] ?? '';
$senhaCorreta = 'senha123'; // Altere conforme necessário

if ($senhaInformada !== $senhaCorreta) {
    http_response_code(401);
    echo "Senha incorreta.";
    exit;
}

// Obter todas as tabelas do banco local
$res = $conn_local->query("SHOW TABLES");
if (!$res) {
    http_response_code(500);
    echo "Erro ao buscar tabelas no banco local.";
    exit;
}

// Para cada tabela...
while ($row = $res->fetch_array()) {
    $tabela = $row[0];

    // Apagar tabela em homolog se existir
    $conn_homolog->query("DROP TABLE IF EXISTS `$tabela`");

    // Criar estrutura
    $ddl = $conn_local->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
    if (!$conn_homolog->query($ddl['Create Table'])) {
        echo "Erro ao criar tabela $tabela em homologação: " . $conn_homolog->error;
        exit;
    }

    // Copiar registros
    $dados = $conn_local->query("SELECT * FROM `$tabela`");
    while ($linha = $dados->fetch_assoc()) {
        $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
        $valores = "'" . implode("','", array_map([$conn_homolog, 'real_escape_string'], array_values($linha))) . "'";
        $sqlInsert = "INSERT INTO `$tabela` ($colunas) VALUES ($valores)";
        if (!$conn_homolog->query($sqlInsert)) {
            echo "Erro ao inserir dados na tabela $tabela: " . $conn_homolog->error;
            exit;
        }
    }
}

echo "ok";
