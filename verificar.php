<?php
include 'conexao.php';

$tabela = 'usuarios';

$local = $conn_local->query("SHOW COLUMNS FROM $tabela");
$colunas_local = [];
while ($col = $local->fetch_assoc()) {
    $colunas_local[] = $col['Field'];
}

$homolog = $conn_homolog->query("SHOW COLUMNS FROM $tabela");
$colunas_homolog = [];
while ($col = $homolog->fetch_assoc()) {
    $colunas_homolog[] = $col['Field'];
}

$dif = array_diff($colunas_local, $colunas_homolog);

if ($dif) {
    echo "<p>Diferenças encontradas:</p><ul>";
    foreach ($dif as $nova) {
        echo "<li>Coluna nova: <strong>$nova</strong></li>";
    }
    echo "</ul><button onclick=\"migrarColunas()\">Migrar essas colunas</button>";
} else {
    echo "Nenhuma diferença encontrada.";
}