<?php
// Script de uso único: cria (ou redefine a senha d)o usuário administrador do sistema.
// Nenhuma credencial fica gravada neste arquivo — você informa usuário e senha na hora,
// pela própria URL, o que é seguro mesmo com este arquivo versionado no Git.
//
// Como usar:
//   1. Suba este arquivo (ou o projeto inteiro) para o servidor.
//   2. Acesse pelo navegador, substituindo os valores:
//      https://seusite.com.br/setup/criar_admin.php?usuario=Gabriel.Ferreira&senha=SUA_SENHA&nome=Gabriel+Ferreira
//   3. Confira a mensagem de sucesso.
//   4. IMPORTANTE: apague este arquivo (ou a pasta /setup) do servidor em seguida.

require_once __DIR__ . '/../includes/dados.php';

$usuarioLogin = trim($_GET['usuario'] ?? '');
$senha = trim($_GET['senha'] ?? '');
$nome = trim($_GET['nome'] ?? '') ?: $usuarioLogin;

if ($usuarioLogin === '' || $senha === '') {
    http_response_code(400);
    die(
        'Informe usuário e senha na URL. Exemplo:<br>' .
        '<code>criar_admin.php?usuario=Gabriel.Ferreira&senha=SUA_SENHA&nome=Gabriel+Ferreira</code>'
    );
}

if (strlen($senha) < 6) {
    http_response_code(400);
    die('Use uma senha com pelo menos 6 caracteres.');
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$jaExistia = false;

alterarUsuarios(function (array $usuarios) use ($usuarioLogin, $hash, $nome, &$jaExistia) {
    foreach ($usuarios as &$u) {
        if (($u['usuario'] ?? '') === $usuarioLogin) {
            $u['senha_hash'] = $hash;
            $u['nome'] = $nome;
            $jaExistia = true;
            return $usuarios;
        }
    }
    unset($u);

    $usuarios[] = [
        'id' => proximoId($usuarios),
        'usuario' => $usuarioLogin,
        'senha_hash' => $hash,
        'nome' => $nome,
    ];

    return $usuarios;
});

echo $jaExistia
    ? 'Usuário já existia: senha redefinida com sucesso para "' . htmlspecialchars($usuarioLogin) . '".'
    : 'Usuário administrador "' . htmlspecialchars($usuarioLogin) . '" criado com sucesso.';

echo '<br><strong>Agora apague este arquivo (setup/criar_admin.php) do servidor por segurança.</strong>';
