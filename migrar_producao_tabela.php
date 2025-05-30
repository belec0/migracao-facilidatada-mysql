<?php
include 'conexao.php';

$tabela = $_POST['tabela'] ?? '';
if (!$tabela) {
    http_response_code(400);
    echo "Tabela não especificada.";
    exit;
}

$log = "";

// Verificar se a tabela já existe na produção
$res = $conn_producao->query("SHOW TABLES LIKE '$tabela'");
if ($res && $res->num_rows > 0) {
    // Comparar estrutura homologação com produção
    $colunasHml = [];
    $colunasProd = [];

    $resHml = $conn_homolog->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resHml->fetch_assoc()) {
        $colunasHml[$row['Field']] = $row;
    }

    $resProd = $conn_producao->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resProd->fetch_assoc()) {
        $colunasProd[$row['Field']] = $row;
    }

    $estruturaDiferente = false;

    foreach ($colunasHml as $nome => $col) {
        if (!isset($colunasProd[$nome])) {
            $estruturaDiferente = true;
            break;
        }
    }
    foreach ($colunasProd as $nome => $col) {
        if (!isset($colunasHml[$nome])) {
            $estruturaDiferente = true;
            break;
        }
    }

    if (!$estruturaDiferente) {
        foreach ($colunasHml as $nome => $hmlCol) {
            if (isset($colunasProd[$nome])) {
                $prdCol = $colunasProd[$nome];
                if (
                    $hmlCol['Type'] !== $prdCol['Type'] ||
                    $hmlCol['Null'] !== $prdCol['Null'] ||
                    $hmlCol['Default'] !== $prdCol['Default']
                ) {
                    $estruturaDiferente = true;
                    break;
                }
            }
        }
    }

    if (!$estruturaDiferente) {
        echo "ok - nenhuma alteração";
        exit;
    }

    // Apagar e recriar estrutura
    $conn_producao->query("DROP TABLE `$tabela`");
    $create = $conn_homolog->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
    if (!$conn_producao->query($create['Create Table'])) {
        http_response_code(500);
        echo "Erro ao recriar tabela: " . $conn_producao->error;
        exit;
    }

    // Repopular com dados da homologação
    $dados = $conn_homolog->query("SELECT * FROM `$tabela`");
    $count = 0;
    while ($linha = $dados->fetch_assoc()) {
        $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
        $valores = "'" . implode("','", array_map([$conn_producao, 'real_escape_string'], array_values($linha))) . "'";
        $conn_producao->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
        $count++;
    }

    echo "ok - tabela recriada e $count registros inseridos";
    exit;
}

// Criar tabela que ainda não existe
$create = $conn_homolog->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
if (!$conn_producao->query($create['Create Table'])) {
    http_response_code(500);
    echo "Erro ao criar tabela: " . $conn_producao->error;
    exit;
}

$dados = $conn_homolog->query("SELECT * FROM `$tabela`");
$count = 0;
while ($linha = $dados->fetch_assoc()) {
    $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
    $valores = "'" . implode("','", array_map([$conn_producao, 'real_escape_string'], array_values($linha))) . "'";
    $conn_producao->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
    $count++;
}

echo "ok - tabela criada e $count registros inseridos";
