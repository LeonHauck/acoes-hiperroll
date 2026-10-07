<?php
require_once __DIR__ . '/dados.php';

define('ARQUIVO_TENTATIVAS', PASTA_DADOS . 'tentativas.json');

const LIMITE_TENTATIVAS = 5;
const JANELA_TENTATIVAS = 15 * 60; // segundos

function ipCliente(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
}

/**
 * Reserva uma tentativa de login para o IP, dentro da trava do arquivo.
 * A tentativa é contada antes de a senha ser conferida, para que várias
 * requisições simultâneas não consigam passar do limite.
 *
 * Devolve ['liberado' => bool, 'restantes' => int, 'espera' => segundos até liberar].
 */
function reservarTentativa(string $ip): array
{
    $agora = time();
    $resultado = ['liberado' => true, 'restantes' => 0, 'espera' => 0];

    acessarJson(ARQUIVO_TENTATIVAS, [], function (array $registros) use ($ip, $agora, &$resultado) {
        // Descarta tentativas fora da janela, de todos os IPs, para o arquivo não crescer.
        foreach ($registros as $chave => $momentos) {
            $recentes = array_values(array_filter((array)$momentos, fn($m) => $m > $agora - JANELA_TENTATIVAS));
            if ($recentes) {
                $registros[$chave] = $recentes;
            } else {
                unset($registros[$chave]);
            }
        }

        $doIp = $registros[$ip] ?? [];

        if (count($doIp) >= LIMITE_TENTATIVAS) {
            $resultado['liberado'] = false;
            $resultado['espera'] = max(1, min($doIp) + JANELA_TENTATIVAS - $agora);
            return $registros;
        }

        $doIp[] = $agora;
        $registros[$ip] = $doIp;
        $resultado['restantes'] = LIMITE_TENTATIVAS - count($doIp);

        return $registros;
    });

    return $resultado;
}

function limparTentativas(string $ip): void
{
    acessarJson(ARQUIVO_TENTATIVAS, [], function (array $registros) use ($ip) {
        unset($registros[$ip]);
        return $registros;
    });
}
