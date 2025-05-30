<?php
session_start();

// Carrega variáveis do .env
$env = parse_ini_file(__DIR__ . '/.env');

// Dados de login vindos do POST
$cpf = $_POST['cpf'] ?? '';
$senhaDigitada = $_POST['senha'] ?? '';

// Sanitiza CPF
$cpf = str_replace(['.', '-'], '', $cpf);

// Compara com dados do .env
$loginEnv = $env['USUARIO_1_LOGIN'] ?? '';
$senhaEnv = $env['USUARIO_1_SENHA'] ?? '';
$nomeEnv = $env['USUARIO_1_NOME'] ?? '';

if ($cpf !== $loginEnv) {
    echo "CPF não encontrado.";
    exit;
}

if ($senhaDigitada !== $senhaEnv) {
    echo "Senha incorreta.";
    exit;
}

// Autenticação válida, cria sessão
$_SESSION['usuario'] = [
    'nome' => $nomeEnv,
    'cpf' => $loginEnv
];

echo "success";
