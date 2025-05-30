<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: painel.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Migrador</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body, html {
            height: 100%;
            background-color: #f4f4f4;
        }
        .login-container {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h3 class="text-center mb-4">Acesso ao Migrador</h3>
            <form id="loginForm">
                <div class="mb-3">
                    <input type="text" name="cpf" id="cpf" class="form-control" placeholder="CPF (somente números)" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'auth.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res === 'success') {
                        window.location.href = 'painel.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de login',
                            text: res
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de servidor',
                        text: 'Ocorreu um erro ao processar sua requisição. Tente novamente mais tarde.'
                    });
                }
            });
        });
    </script>
</body>
</html>
