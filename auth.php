<?php
session_start();
include 'conexao.php';

if (!$conn_producao || $conn_producao->connect_error) {
    http_response_code(500);
    echo "Erro ao conectar com o banco de produção.";
    exit;
}

$cpf = $_POST['cpf'] ?? '';
$cpf = str_replace(['.', '-'], '', $cpf);

$senha = md5($_POST['senha'] ?? '');

$sql = "SELECT * FROM usuarios WHERE cpf = ?";
$stmt = $conn_producao->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo "Erro na preparação da consulta.";
    exit;
}

$stmt->bind_param("s", $cpf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "CPF não encontrado.";
    exit;
}

$usuario = $result->fetch_assoc();

if ($usuario['senha'] !== $senha) {
    echo "Senha incorreta.";
    exit;
}

$_SESSION['usuario'] = $usuario;
echo "success";
