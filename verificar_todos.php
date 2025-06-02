<?php
include 'conexao.php';

function getEstrutura($conn, $nome_conexao) {
    $estruturas = [];

    if ($conn->connect_error) {
        return ["__ERRO__" => "[$nome_conexao] Erro de conexão: " . $conn->connect_error];
    }

    $db_selected = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    if (!$db_selected) {
        return ["__ERRO__" => "[$nome_conexao] Nenhum banco selecionado."];
    }

    $res = $conn->query("SHOW TABLES");
    if (!$res) {
        return ["__ERRO__" => "[$nome_conexao] Erro ao listar tabelas: " . $conn->error];
    }

    while ($row = $res->fetch_array()) {
        $tabela = $row[0];
        $cols = $conn->query("SHOW FULL COLUMNS FROM `$tabela`");
        if (!$cols) {
            $estruturas[$tabela] = ["__ERRO__" => "Erro ao obter colunas da tabela $tabela: " . $conn->error];
            continue;
        }

        $colunas = [];
        while ($col = $cols->fetch_assoc()) {
            $colunas[$col['Field']] = [
                'Type' => $col['Type'],
                'Null' => $col['Null'],
                'Default' => $col['Default'],
                'Key' => $col['Key']
            ];
        }

        $estruturas[$tabela] = $colunas;
    }

    return $estruturas;
}

function normalizaTimestamp($valor) {
    return strtolower(trim(str_replace(['()', ' '], '', $valor)));
}

function compararEstruturas($nome1, $nome2, $estrutura1, $estrutura2) {
    $html = "<div class='diff-box'>";
    $html .= "<h5>Comparando: <strong>$nome1</strong> ⇄ <strong>$nome2</strong></h5>";

    if (isset($estrutura1['__ERRO__'])) {
        return "<div class='alert alert-danger'>" . $estrutura1['__ERRO__'] . "</div>";
    }
    if (isset($estrutura2['__ERRO__'])) {
        return "<div class='alert alert-danger'>" . $estrutura2['__ERRO__'] . "</div>";
    }

    $tabelas1 = array_keys($estrutura1);
    $tabelas2 = array_keys($estrutura2);

    $tabelas_somente_1 = array_diff($tabelas1, $tabelas2);
    $tabelas_somente_2 = array_diff($tabelas2, $tabelas1);
    $tabelas_em_ambos = array_intersect($tabelas1, $tabelas2);

    if (empty($tabelas_somente_1) && empty($tabelas_somente_2)) {
        $html .= "<p class='text-success'>Todas as tabelas estão presentes em ambos os bancos.</p>";
    } else {
        if ($tabelas_somente_1) {
            $html .= "<p class='text-danger'>Tabelas apenas em $nome1:</p><ul>";
            foreach ($tabelas_somente_1 as $t) {
                $html .= "<li>$t</li>";
            }
            $html .= "</ul>";
        }
        if ($tabelas_somente_2) {
            $html .= "<p class='text-danger'>Tabelas apenas em $nome2:</p><ul>";
            foreach ($tabelas_somente_2 as $t) {
                $html .= "<li>$t</li>";
            }
            $html .= "</ul>";
        }
    }

    foreach ($tabelas_em_ambos as $tabela) {
        if (isset($estrutura1[$tabela]['__ERRO__'])) {
            $html .= "<div class='alert alert-warning'>Erro em $nome1 → $tabela: " . $estrutura1[$tabela]['__ERRO__'] . "</div>";
            continue;
        }
        if (isset($estrutura2[$tabela]['__ERRO__'])) {
            $html .= "<div class='alert alert-warning'>Erro em $nome2 → $tabela: " . $estrutura2[$tabela]['__ERRO__'] . "</div>";
            continue;
        }

        $col1 = $estrutura1[$tabela];
        $col2 = $estrutura2[$tabela];

        $colunas_1 = array_keys($col1);
        $colunas_2 = array_keys($col2);

        $faltando_em_2 = array_diff($colunas_1, $colunas_2);
        $faltando_em_1 = array_diff($colunas_2, $colunas_1);
        $em_ambos = array_intersect($colunas_1, $colunas_2);

        if (!empty($faltando_em_1) || !empty($faltando_em_2)) {
            $html .= "<p class='text-danger'>Colunas diferentes na tabela <strong>$tabela</strong>:</p><ul>";
            foreach ($faltando_em_2 as $col) {
                $html .= "<li>$col está em <strong>$nome1</strong>, mas não em <strong>$nome2</strong> <span class='diff-action-add'>(será adicionado em $nome2)</span></li>";
            }
            foreach ($faltando_em_1 as $col) {
                $html .= "<li>$col está em <strong>$nome2</strong>, mas não em <strong>$nome1</strong> <span class='diff-action-remove'>(será excluído de $nome2)</span></li>";
            }
            $html .= "</ul>";
        }

        foreach ($em_ambos as $coluna) {
            $a = $col1[$coluna];
            $b = $col2[$coluna];

            $diferencas = [];

            foreach (['Type', 'Null', 'Default', 'Key'] as $atributo) {
                if ($a[$atributo] != $b[$atributo]) {
                    // Tratamento especial para ignorar diferenças de sintaxe em CURRENT_TIMESTAMP
                    if (
                        $atributo === 'Default' &&
                        is_string($a['Default']) && is_string($b['Default']) &&
                        normalizaTimestamp($a['Default']) === normalizaTimestamp($b['Default'])
                    ) {
                        continue; // ignora a diferença
                    }

                    $diferencas[] = "$atributo: $nome1=[$a[$atributo]] ≠ $nome2=[$b[$atributo]]";
                }

            }

            if (!empty($diferencas)) {
                $html .= "<p class='text-warning'>Coluna <strong>$coluna</strong> diferente em <strong>$tabela</strong>:</p><ul>";
                foreach ($diferencas as $d) {
                    $html .= "<li>$d</li>";
                }
                $html .= "</ul>";
            }
        }
    }

    $html .= "</div>";
    return $html;
}

$estrutura_local = getEstrutura($conn_local, 'Local');
$estrutura_homolog = getEstrutura($conn_homolog, 'Homologacao');
$estrutura_producao = getEstrutura($conn_producao, 'Producao');

echo compararEstruturas('Local', 'Homologacao', $estrutura_local, $estrutura_homolog);
echo compararEstruturas('Homologacao', 'Producao', $estrutura_homolog, $estrutura_producao);
echo compararEstruturas('Local', 'Producao', $estrutura_local, $estrutura_producao);
?>