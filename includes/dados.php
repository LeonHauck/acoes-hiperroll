<?php
require_once __DIR__ . '/config.php';

define('ARQUIVO_ACOES', PASTA_DADOS . 'acoes.json');
define('ARQUIVO_USUARIOS', PASTA_DADOS . 'usuarios.json');

function garantirArquivo(string $caminho, $conteudoPadrao): void
{
    if (!file_exists($caminho)) {
        file_put_contents($caminho, json_encode($conteudoPadrao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

/**
 * Lê (e opcionalmente altera) um arquivo JSON com trava de arquivo (flock),
 * para evitar que duas gravações simultâneas corrompam os dados.
 * Se $alterar for passado, ele recebe o array atual e deve devolver o array novo,
 * que é gravado no arquivo antes da trava ser liberada.
 */
function acessarJson(string $caminho, array $padrao, ?callable $alterar = null): array
{
    garantirArquivo($caminho, $padrao);

    $ponteiro = fopen($caminho, 'c+');
    if (!$ponteiro) {
        throw new RuntimeException('Não foi possível acessar o arquivo de dados: ' . $caminho);
    }

    flock($ponteiro, LOCK_EX);

    $conteudo = stream_get_contents($ponteiro);
    $dados = $conteudo !== '' ? json_decode($conteudo, true) : $padrao;
    if (!is_array($dados)) {
        $dados = $padrao;
    }

    if ($alterar !== null) {
        $dados = $alterar($dados);
        rewind($ponteiro);
        ftruncate($ponteiro, 0);
        fwrite($ponteiro, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($ponteiro);
    }

    flock($ponteiro, LOCK_UN);
    fclose($ponteiro);

    return $dados;
}

function listarAcoes(): array
{
    return acessarJson(ARQUIVO_ACOES, []);
}

function alterarAcoes(callable $alterar): array
{
    return acessarJson(ARQUIVO_ACOES, [], $alterar);
}

function listarUsuarios(): array
{
    return acessarJson(ARQUIVO_USUARIOS, []);
}

function alterarUsuarios(callable $alterar): array
{
    return acessarJson(ARQUIVO_USUARIOS, [], $alterar);
}

function proximoId(array $linhas): int
{
    $maior = 0;
    foreach ($linhas as $linha) {
        $maior = max($maior, (int)($linha['id'] ?? 0));
    }
    return $maior + 1;
}
