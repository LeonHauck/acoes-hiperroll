<?php
require_once __DIR__ . '/../includes/dados.php';
require_once __DIR__ . '/../includes/auth.php';

exigirLoginApi();
header('Content-Type: application/json; charset=utf-8');

const TAMANHO_MINIMO_SENHA = 8;

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['erro' => 'Método inválido.'], 405);
}

$senhaAtual = $_POST['senha_atual'] ?? '';
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmacao = $_POST['confirmar_senha'] ?? '';

if ($senhaAtual === '' || $novaSenha === '' || $confirmacao === '') {
    responder(['erro' => 'Preencha todos os campos.'], 422);
}

if ($novaSenha !== $confirmacao) {
    responder(['erro' => 'A confirmação não confere com a nova senha.'], 422);
}

if (mb_strlen($novaSenha) < TAMANHO_MINIMO_SENHA) {
    responder(['erro' => 'A nova senha precisa ter pelo menos ' . TAMANHO_MINIMO_SENHA . ' caracteres.'], 422);
}

if ($novaSenha === $senhaAtual) {
    responder(['erro' => 'A nova senha precisa ser diferente da atual.'], 422);
}

$idUsuario = $_SESSION['usuario_id'];
$erro = null;

// A conferência da senha atual acontece dentro da trava do arquivo, para que
// duas trocas simultâneas não se sobreponham.
alterarUsuarios(function (array $usuarios) use ($idUsuario, $senhaAtual, $novaSenha, &$erro) {
    foreach ($usuarios as &$usuario) {
        if ((string)($usuario['id'] ?? '') !== (string)$idUsuario) {
            continue;
        }

        if (!password_verify($senhaAtual, $usuario['senha_hash'])) {
            $erro = ['erro' => 'A senha atual está incorreta.', 'codigo' => 422];
            return $usuarios;
        }

        $usuario['senha_hash'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        $usuario['senha_alterada_em'] = date('Y-m-d H:i:s');
        return $usuarios;
    }
    unset($usuario);

    $erro = ['erro' => 'Usuário não encontrado.', 'codigo' => 404];
    return $usuarios;
});

if ($erro) {
    responder(['erro' => $erro['erro']], $erro['codigo']);
}

session_regenerate_id(true);
responder(['sucesso' => true]);
