<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuarioLogado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function exigirLogin(): void
{
    if (!usuarioLogado()) {
        header('Location: index.html');
        exit;
    }
}

// Usada pelas rotas da API: responde em JSON em vez de redirecionar.
function exigirLoginApi(): void
{
    if (!usuarioLogado()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }
}
