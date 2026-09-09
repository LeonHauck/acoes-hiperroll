<?php
// Aplica os filtros vindos da URL ($_GET) sobre a lista de ações já carregada do JSON.
// Usada pela API (listagem), pela exportação em Excel e pelo relatório para impressão/PDF,
// para que os três lugares apliquem exatamente os mesmos filtros.
function buscarAcoesFiltradas(array $linhas, array $filtros): array
{
    $rede = mb_strtolower(trim($filtros['rede'] ?? ''));
    $representante = mb_strtolower(trim($filtros['representante'] ?? ''));
    $status = $filtros['status'] ?? '';
    $periodoDe = $filtros['periodo_de'] ?? '';
    $periodoAte = $filtros['periodo_ate'] ?? '';

    $filtradas = array_filter($linhas, function (array $linha) use ($rede, $representante, $status, $periodoDe, $periodoAte) {
        if ($rede !== '' && !str_contains(mb_strtolower($linha['rede'] ?? ''), $rede)) {
            return false;
        }
        if ($representante !== '' && !str_contains(mb_strtolower($linha['representante'] ?? ''), $representante)) {
            return false;
        }
        if ($status !== '' && ($linha['status'] ?? '') !== $status) {
            return false;
        }
        // Considera a ação dentro do período filtrado se o intervalo dela
        // (data_inicio .. data_fim) tiver qualquer sobreposição com o filtro.
        if ($periodoDe !== '' && ($linha['data_fim'] ?? '') < $periodoDe) {
            return false;
        }
        if ($periodoAte !== '' && ($linha['data_inicio'] ?? '') > $periodoAte) {
            return false;
        }
        return true;
    });

    $filtradas = array_values($filtradas);

    usort($filtradas, function ($a, $b) {
        return [$b['data_inicio'] ?? '', $b['id'] ?? 0] <=> [$a['data_inicio'] ?? '', $a['id'] ?? 0];
    });

    return $filtradas;
}

function rotuloStatus(string $status): string
{
    return match ($status) {
        'em_analise' => 'Em análise',
        'aprovado' => 'Aprovado',
        'pago' => 'Pago',
        default => $status,
    };
}
