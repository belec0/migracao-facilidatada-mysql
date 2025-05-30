<?php
include 'conexao.php';

$tabela = $_POST['tabela'] ?? '';
if (!$tabela) {
    http_response_code(400);
    echo "Tabela não especificada.";
    exit;
}

// Verificar se a tabela já existe no local
$res = $conn_local->query("SHOW TABLES LIKE '$tabela'");
if ($res && $res->num_rows > 0) {
    // Comparar estrutura produção com local
    $colunasProd = [];
    $colunasLocal = [];

    $resProd = $conn_producao->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resProd->fetch_assoc()) {
        $colunasProd[$row['Field']] = $row;
    }

    $resLocal = $conn_local->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resLocal->fetch_assoc()) {
        $colunasLocal[$row['Field']] = $row;
    }

    $estruturaDiferente = false;

    foreach ($colunasProd as $nome => $col) {
        if (!isset($colunasLocal[$nome])) {
            $estruturaDiferente = true;
            break;
        }
    }
    foreach ($colunasLocal as $nome => $col) {
        if (!isset($colunasProd[$nome])) {
            $estruturaDiferente = true;
            break;
        }
    }

    if (!$estruturaDiferente) {
        foreach ($colunasProd as $nome => $prdCol) {
            if (isset($colunasLocal[$nome])) {
                $locCol = $colunasLocal[$nome];
                if ($prdCol['Type'] !== $locCol['Type'] ||
                    $prdCol['Null'] !== $locCol['Null'] ||
                    $prdCol['Default'] !== $locCol['Default']) {
                    $estruturaDiferente = true;
                    break;
                }
            }
        }
    }

    if (!$estruturaDiferente) {
        echo "skip";
        exit;
    }

    // Apagar tabela local existente
    $conn_local->query("DROP TABLE `$tabela`");

    // Recriar estrutura com base na produção
    $create = $conn_producao->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
    if (!$conn_local->query($create['Create Table'])) {
        http_response_code(500);
        echo "Erro ao recriar tabela: " . $conn_local->error;
        exit;
    }

    // Inserir dados da produção
    $dados = $conn_producao->query("SELECT * FROM `$tabela`");
    while ($linha = $dados->fetch_assoc()) {
        $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
        $valores = "'" . implode("','", array_map([$conn_local, 'real_escape_string'], array_values($linha))) . "'";
        $conn_local->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
    }

    echo "ok";
    exit;
}

// Criar estrutura completa se a tabela não existe
$create = $conn_producao->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
if (!$conn_local->query($create['Create Table'])) {
    http_response_code(500);
    echo "Erro ao criar tabela: " . $conn_local->error;
    exit;
}

// Inserir dados da produção
$dados = $conn_producao->query("SELECT * FROM `$tabela`");
while ($linha = $dados->fetch_assoc()) {
    $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
    $valores = "'" . implode("','", array_map([$conn_local, 'real_escape_string'], array_values($linha))) . "'";
    $conn_local->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
}

echo "ok";
