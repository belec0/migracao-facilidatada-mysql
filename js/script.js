$(document).ready(function () {
    $('#verificar').click(function () {
        $.post('verificar.php', {}, function (res) {
            $('#resultado').html(res);
        });
    });
});