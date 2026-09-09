<?php
require_once __DIR__ . '/includes/dados.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/consulta_acoes.php';

exigirLogin();

$linhas = buscarAcoesFiltradas(listarAcoes(), $_GET);

$nomeArquivo = 'acoes_comerciais_' . date('Y-m-d_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
echo "\xEF\xBB\xBF"; // BOM para acentuação correta ao abrir no Excel
?>
<table border="1">
    <thead>
        <tr>
            <th>Rede</th>
            <th>Loja</th>
            <th>Representante</th>
            <th>Tipo de Ação</th>
            <th>Data Início</th>
            <th>Data Fim</th>
            <th>Quantidade</th>
            <th>Valor Unit. (R$)</th>
            <th>Valor (R$)</th>
            <th>Status</th>
            <th>Observações</th>
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
            <td><?= !empty($l['quantidade']) ? htmlspecialchars((string)$l['quantidade']) : '' ?></td>
            <td><?= !empty($l['valor_unitario']) ? number_format((float)$l['valor_unitario'], 2, ',', '.') : '' ?></td>
            <td><?= number_format((float)$l['valor'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars(rotuloStatus($l['status'])) ?></td>
            <td><?= htmlspecialchars($l['observacoes'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
