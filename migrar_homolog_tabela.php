<?php
include 'conexao.php';

$tabela = $_POST['tabela'] ?? '';
if (!$tabela) {
    http_response_code(400);
    echo "Tabela não especificada.";
    exit;
}

$log = [];

// Verificar se a tabela já existe no banco de homologação
$res = $conn_homolog->query("SHOW TABLES LIKE '$tabela'");
if ($res && $res->num_rows > 0) {
    // Comparar estrutura local com homologação
    $colunasLocal = [];
    $colunasHomolog = [];

    $resLocal = $conn_local->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resLocal->fetch_assoc()) {
        $colunasLocal[$row['Field']] = $row;
    }

    $resHomolog = $conn_homolog->query("SHOW FULL COLUMNS FROM `$tabela`");
    while ($row = $resHomolog->fetch_assoc()) {
        $colunasHomolog[$row['Field']] = $row;
    }

    $estruturaDiferente = false;

    foreach ($colunasLocal as $nome => $col) {
        if (!isset($colunasHomolog[$nome])) {
            $estruturaDiferente = true;
            $log[] = "Coluna <b>$nome</b> será adicionada";
        }
    }
    foreach ($colunasHomolog as $nome => $col) {
        if (!isset($colunasLocal[$nome])) {
            $estruturaDiferente = true;
            $log[] = "Coluna <b>$nome</b> será removida";
        }
    }

    foreach ($colunasLocal as $nome => $localCol) {
        if (isset($colunasHomolog[$nome])) {
            $hmlCol = $colunasHomolog[$nome];
            if ($localCol['Type'] !== $hmlCol['Type'] ||
                $localCol['Null'] !== $hmlCol['Null'] ||
                $localCol['Default'] !== $hmlCol['Default']) {
                $estruturaDiferente = true;
                $log[] = "Coluna <b>$nome</b> será modificada";
            }
        }
    }

    if (!$estruturaDiferente) {
        echo "skip";
        exit;
    }

    $conn_homolog->query("DROP TABLE `$tabela`");
    $log[] = "Tabela <b>$tabela</b> foi recriada";

    $create = $conn_local->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
    if (!$conn_homolog->query($create['Create Table'])) {
        http_response_code(500);
        echo "Erro ao recriar tabela: " . $conn_homolog->error;
        exit;
    }

    $dados = $conn_producao->query("SELECT * FROM `$tabela`");
    $count = 0;
    while ($linha = $dados->fetch_assoc()) {
        $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
        $valores = "'" . implode("','", array_map([$conn_homolog, 'real_escape_string'], array_values($linha))) . "'";
        $conn_homolog->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
        $count++;
    }

    $log[] = "$count registros inseridos na tabela <b>$tabela</b>";
    echo implode("<br>", $log);
    exit;
}

// Tabela ainda não existe → criar estrutura
$create = $conn_local->query("SHOW CREATE TABLE `$tabela`")->fetch_assoc();
if (!$conn_homolog->query($create['Create Table'])) {
    http_response_code(500);
    echo "Erro ao criar tabela: " . $conn_homolog->error;
    exit;
}
$log[] = "Tabela <b>$tabela</b> foi criada";

$dados = $conn_producao->query("SELECT * FROM `$tabela`");
$count = 0;
while ($linha = $dados->fetch_assoc()) {
    $colunas = "`" . implode("`,`", array_keys($linha)) . "`";
    $valores = "'" . implode("','", array_map([$conn_homolog, 'real_escape_string'], array_values($linha))) . "'";
    $conn_homolog->query("INSERT INTO `$tabela` ($colunas) VALUES ($valores)");
    $count++;
}
$log[] = "$count registros inseridos na tabela <b>$tabela</b>";

echo implode("<br>", $log);
