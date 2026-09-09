(function () {
    'use strict';

    const form = document.getElementById('formLogin');
    const mensagemErro = document.getElementById('mensagemErro');
    const botaoEntrar = document.getElementById('botaoEntrar');

    function mostrarErro(texto) {
        mensagemErro.textContent = texto;
        mensagemErro.hidden = false;
    }

    // Se já existe uma sessão ativa, pula direto para o painel.
    fetch('api/sessao.php')
        .then((r) => r.json())
        .then((dados) => {
            if (dados.logado) {
                window.location.href = 'dashboard.php';
            }
        })
        .catch(() => {});

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        mensagemErro.hidden = true;
        botaoEntrar.disabled = true;
        botaoEntrar.textContent = 'Entrando...';

        try {
            const resposta = await fetch('api/login.php', {
                method: 'POST',
                body: new FormData(form),
            });
            const dados = await resposta.json();

            if (dados.sucesso) {
                window.location.href = 'dashboard.php';
                return;
            }

            mostrarErro(dados.erro || 'Não foi possível entrar. Tente novamente.');
        } catch (erro) {
            mostrarErro('Erro de conexão. Verifique sua internet e tente novamente.');
        } finally {
            botaoEntrar.disabled = false;
            botaoEntrar.textContent = 'Entrar';
        }
    });
})();
