<?php
require_once __DIR__ . '/../includes/dados.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/limite_login.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['erro' => 'Método inválido.'], 405);
}

$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    responder(['erro' => 'Preencha usuário e senha.']);
}

$ip = ipCliente();
$tentativa = reservarTentativa($ip);

if (!$tentativa['liberado']) {
    $minutos = (int) ceil($tentativa['espera'] / 60);
    responder([
        'erro' => 'Muitas tentativas de login. Tente novamente em ' . $minutos . ($minutos === 1 ? ' minuto.' : ' minutos.'),
    ], 429);
}

$encontrado = null;
foreach (listarUsuarios() as $u) {
    if (($u['usuario'] ?? '') === $usuario) {
        $encontrado = $u;
        break;
    }
}

if ($encontrado && password_verify($senha, $encontrado['senha_hash'])) {
    limparTentativas($ip);
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $encontrado['id'];
    $_SESSION['usuario_nome'] = $encontrado['nome'];
    responder(['sucesso' => true]);
}

$restantes = $tentativa['restantes'];
$aviso = match (true) {
    $restantes === 0 => ' Limite de tentativas atingido: aguarde 15 minutos para tentar de novo.',
    $restantes === 1 => ' Resta 1 tentativa.',
    default => ' Restam ' . $restantes . ' tentativas.',
};

responder(['erro' => 'Usuário ou senha inválidos.' . $aviso]);
