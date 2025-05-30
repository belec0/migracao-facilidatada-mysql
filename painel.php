<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Migração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .diff-box {
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 12px;
            margin-top: 20px;
        }
        .status-ok { color: green; font-weight: bold; }
        .status-diff { color: red; font-weight: bold; }
        .diff-action-add { color: green; font-style: italic; }
        .diff-action-remove { color: orange; font-style: italic; }
    </style>
</head>
<body class="container mt-5">
    <h2 class="mb-4">Bem-vindo, <?= $_SESSION['usuario']['nome'] ?></h2>
    <p>Verifique abaixo se os bancos estão sincronizados.</p>

    <button class="btn btn-primary mb-3" id="verificarDiferencas">Verificar Diferenças</button>
    <div id="resultadoComparacao"></div>

    <button class="btn btn-danger mt-4 me-2" id="btnMigrar">Migrar Local → Homologação</button>
    <button class="btn btn-warning mt-4" id="btnMigrarProducao">Migrar Homologação → Produção</button>
    <button class="btn btn-success mt-4" id="btnMigrarProducaoLocal">Migrar Produção → Local</button>

    <!-- Overlay de carregamento -->
    <div id="overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#ffffffd9; z-index:9999; text-align:center;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);">
            <h4>Migrando dados...</h4>
            <div class="progress" style="width:300px; margin:auto;">
                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%">0%</div>
            </div>
        </div>
    </div>

    <script>
    $('#btnMigrarProducaoLocal').click(function () {
        iniciarMigracao('migrar_local_iniciar.php', 'migrar_local_tabela.php', 'Produção', 'Local');
    });

    $('#verificarDiferencas').click(function () {
        $.ajax({
            url: 'verificar_todos.php',
            method: 'POST',
            success: function (res) {
                res = res.replaceAll('(presente apenas em Local)', '<span class="diff-action-add">(será adicionada)</span>')
                         .replaceAll('(presente apenas em Homologação)', '<span class="diff-action-remove">(será removida)</span>')
                         .replaceAll('(presente apenas em Produção)', '<span class="diff-action-remove">(será removida)</span>');
                $('#resultadoComparacao').html(res);
            },
            error: function () {
                Swal.fire('Erro', 'Falha ao consultar os bancos.', 'error');
            }
        });
    });

    function iniciarMigracao(iniciarURL, tabelaURL, origem, destino) {
        Swal.fire({
            title: `Confirmação`,
            input: 'password',
            inputLabel: `Digite a senha de ${destino}`,
            inputPlaceholder: 'Senha...',
            showCancelButton: true,
            confirmButtonText: 'Iniciar Migração',
            preConfirm: (senha) => {
                if (!senha) {
                    Swal.showValidationMessage('Informe a senha para continuar');
                    return false;
                }

                $('#overlay').fadeIn();
                $('#progressBar').css('width', '0%').text('0%');

                return $.post(iniciarURL, { senha: senha })
                    .then((res) => {
                        if (typeof res === 'string') {
                            res = JSON.parse(res);
                        }
                        if (res.erro) {
                            if (res.erro === 'Senha incorreta') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Senha invalida',
                                    text: 'Senha invalida'
                                })
                            }
                            throw new Error(res.erro);
                        }

                        let tabelas = res;
                        let total = tabelas.length;
                        let migradas = 0;
                        let logs = [];

                        const migrarTabela = (i) => {
                            if (i >= total) {
                                $('#progressBar').css('width', '100%').text('100%');
                                $('#overlay').fadeOut();
                                // Exibir resumo no Swal com scroll e reload após confirmação
                                Swal.fire({
                                    title: 'Migração concluída!',
                                    html: `<p>Migração de <b>${origem}</b> para <b>${destino}</b> concluída com sucesso!</p>
                                           <hr><div style="max-height:300px; overflow:auto; text-align:left;">${logs.join('<br>')}</div>`,
                                    icon: 'success',
                                }).then(() => location.reload());

                                return;
                            }

                            $.post(tabelaURL, { tabela: tabelas[i] })
                                .done((resposta) => {
                                    migradas++;
                                    let progresso = Math.round((migradas / total) * 100);
                                    $('#progressBar').css('width', progresso + '%').text(progresso + '%');
                                    logs.push(`<b>${tabelas[i]}</b>: ${resposta}`);
                                    migrarTabela(i + 1);
                                })
                                .fail(() => {
                                    $('#overlay').fadeOut();
                                    Swal.fire('Erro', `Falha ao migrar a tabela ${tabelas[i]}`, 'error');
                                });
                        };

                        migrarTabela(0);
                    })
                    .catch((err) => {
                        $('#overlay').fadeOut();
                        Swal.fire('Erro', err.message, 'error');
                    });
            }
        });
    }

    $('#btnMigrar').click(function () {
        iniciarMigracao('migrar_homolog_iniciar.php', 'migrar_homolog_tabela.php', 'Local', 'Homologação');
    });

    $('#btnMigrarProducao').click(function () {
        iniciarMigracao('migrar_producao_iniciar.php', 'migrar_producao_tabela.php', 'Homologação', 'Produção');
    });
    </script>
</body>
</html>
