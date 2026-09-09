<?php
require_once __DIR__ . '/../includes/dados.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido.']);
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    echo json_encode(['erro' => 'Preencha usuário e senha.']);
    exit;
}

$encontrado = null;
foreach (listarUsuarios() as $u) {
    if (($u['usuario'] ?? '') === $usuario) {
        $encontrado = $u;
        break;
    }
}

if ($encontrado && password_verify($senha, $encontrado['senha_hash'])) {
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $encontrado['id'];
    $_SESSION['usuario_nome'] = $encontrado['nome'];
    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['erro' => 'Usuário ou senha inválidos.']);
