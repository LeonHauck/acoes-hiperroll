<?php
require_once __DIR__ . '/includes/dados.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/consulta_acoes.php';

exigirLogin();

$linhas = buscarAcoesFiltradas(listarAcoes(), $_GET);

$total = 0;
$totaisPorStatus = ['em_analise' => 0, 'aprovado' => 0, 'pago' => 0];
foreach ($linhas as $l) {
    $total += (float)$l['valor'];
    $totaisPorStatus[$l['status']] += (float)$l['valor'];
}

function formatoMoeda(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Ações Comerciais - Hiperroll</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #1B2A4B; margin: 32px; }
    .cabecalho { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #E30613; padding-bottom: 16px; margin-bottom: 24px; }
    .cabecalho img { height: 56px; }
    .cabecalho h1 { font-size: 18px; margin: 0; text-align: right; }
    .cabecalho p { margin: 4px 0 0; text-align: right; font-size: 12px; color: #555; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 24px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #1B2A4B; color: #fff; }
    tbody tr:nth-child(even) { background: #F5F6F8; }
    .totais { display: flex; gap: 24px; margin-bottom: 24px; }
    .totais div { border: 1px solid #ddd; border-left: 4px solid #E30613; padding: 10px 16px; font-size: 12px; }
    .totais strong { display: block; font-size: 15px; margin-top: 4px; }
    .botao-imprimir { margin-bottom: 20px; }
    @media print {
        .botao-imprimir { display: none; }
        body { margin: 8mm; }
    }
</style>
</head>
<body>
    <div class="botao-imprimir">
        <button onclick="window.print()">Salvar como PDF / Imprimir</button>
    </div>

    <div class="cabecalho">
        <img src="assets/img/logo-hiperroll.png" alt="Hiperroll">
        <div>
            <h1>Relatório de Ações Comerciais</h1>
            <p>Gerado em <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>

    <div class="totais">
        <div>Em análise<strong><?= formatoMoeda($totaisPorStatus['em_analise']) ?></strong></div>
        <div>Aprovado<strong><?= formatoMoeda($totaisPorStatus['aprovado']) ?></strong></div>
        <div>Pago<strong><?= formatoMoeda($totaisPorStatus['pago']) ?></strong></div>
        <div>Total geral<strong><?= formatoMoeda($total) ?></strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rede</th>
                <th>Loja</th>
                <th>Representante</th>
                <th>Tipo de Ação</th>
                <th>Data Início</th>
                <th>Data Fim</th>
                <th>Qtd.</th>
                <th>Valor Unit.</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['rede']) ?></td>
                <td><?= htmlspecialchars($l['loja']) ?></td>
                <td><?= htmlspecialchars($l['representante']) ?></td>
                <td><?= htmlspecialchars($l['tipo_acao']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($l['data_inicio']))) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($l['data_fim']))) ?></td>
                <td><?= !empty($l['quantidade']) ? htmlspecialchars((string)$l['quantidade']) : '—' ?></td>
                <td><?= !empty($l['valor_unitario']) ? formatoMoeda((float)$l['valor_unitario']) : '—' ?></td>
                <td><?= formatoMoeda((float)$l['valor']) ?></td>
                <td><?= htmlspecialchars(rotuloStatus($l['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$linhas): ?>
            <tr><td colspan="9" style="text-align:center; color:#888;">Nenhum registro encontrado para os filtros selecionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
