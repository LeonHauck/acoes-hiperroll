<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'logado' => usuarioLogado(),
    'nome' => $_SESSION['usuario_nome'] ?? null,
]);
