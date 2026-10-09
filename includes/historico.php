<?php
// Campos de uma ação acompanhados no histórico de alterações.
const CAMPOS_HISTORICO = [
    'rede', 'loja', 'representante', 'tipo_acao', 'data_inicio', 'data_fim',
    'quantidade', 'valor_unitario', 'valor', 'observacoes', 'comprovante',
];

const CAMPOS_NUMERICOS_HISTORICO = ['quantidade', 'valor_unitario', 'valor'];

/**
 * Monta um evento do histórico: quem fez, quando e o que mudou.
 * $tipo: 'criacao', 'edicao' ou 'status'.
 */
function eventoHistorico(string $tipo, array $alteracoes = []): array
{
    return [
        'quando' => date('Y-m-d H:i:s'),
        'usuario' => $_SESSION['usuario_nome'] ?? 'Sistema',
        'tipo' => $tipo,
        'alteracoes' => $alteracoes,
    ];
}

/**
 * Compara a ação antes e depois de uma edição e devolve só os campos que mudaram,
 * no formato [['campo' => ..., 'de' => ..., 'para' => ...], ...].
 */
function diferencasAcao(array $antes, array $depois): array
{
    $alteracoes = [];

    foreach (CAMPOS_HISTORICO as $campo) {
        $de = $antes[$campo] ?? null;
        $para = $depois[$campo] ?? null;

        // Números são comparados pelo valor (12500 e 12500.0 são iguais);
        // o restante, como texto (null e '' contam como "vazio").
        $iguais = in_array($campo, CAMPOS_NUMERICOS_HISTORICO, true) && is_numeric($de) && is_numeric($para)
            ? abs((float)$de - (float)$para) < 0.00001
            : (string)$de === (string)$para;

        if (!$iguais) {
            $alteracoes[] = ['campo' => $campo, 'de' => $de, 'para' => $para];
        }
    }

    return $alteracoes;
}
