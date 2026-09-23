<?php
require_once __DIR__ . '/includes/dados.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();

$id = $_GET['id'] ?? '';
$linha = null;

foreach (listarAcoes() as $l) {
    if ((string)$l['id'] === (string)$id) {
        $linha = $l;
        break;
    }
}

if (!$linha || empty($linha['comprovante'])) {
    http_response_code(404);
    die('Comprovante não encontrado.');
}

$caminhoArquivo = PASTA_UPLOADS . $linha['comprovante'];
if (!file_exists($caminhoArquivo)) {
    http_response_code(404);
    die('Arquivo não encontrado no servidor.');
}

$extensao = strtolower(pathinfo($linha['comprovante'], PATHINFO_EXTENSION));

if ($extensao === 'pdf') {
    // PDFs já têm visualizador e impressão nativos do navegador.
    header('Location: uploads/' . rawurlencode($linha['comprovante']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante - <?= htmlspecialchars($linha['rede']) ?></title>
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #1B2A4B; margin: 32px; }
    .cabecalho { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #E30613; padding-bottom: 16px; margin-bottom: 20px; }
    .cabecalho img { height: 50px; }
    .cabecalho h1 { font-size: 16px; margin: 0; text-align: right; }
    .info { font-size: 13px; color: #333; margin-bottom: 20px; line-height: 1.7; }
    .info strong { color: #1B2A4B; }
    .imagem-comprovante { max-width: 100%; display: block; margin: 0 auto; border: 1px solid #ddd; }
    .botao-imprimir { margin-bottom: 20px; }
    @media print {
        .botao-imprimir { display: none; }
        body { margin: 8mm; }
    }
</style>
</head>
<body>
    <div class="botao-imprimir">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="cabecalho">
        <img src="assets/img/logo-hiperroll.png" alt="Hiperroll">
        <h1>Comprovante de Ação Comercial</h1>
    </div>

    <div class="info">
        <strong>Rede:</strong> <?= htmlspecialchars($linha['rede']) ?> &nbsp;|&nbsp;
        <strong>Loja:</strong> <?= htmlspecialchars($linha['loja']) ?> &nbsp;|&nbsp;
        <strong>Representante:</strong> <?= htmlspecialchars($linha['representante']) ?><br>
        <strong>Tipo de Ação:</strong> <?= htmlspecialchars($linha['tipo_acao']) ?> &nbsp;|&nbsp;
        <strong>Período:</strong>
        <?= htmlspecialchars(date('d/m/Y', strtotime($linha['data_inicio']))) ?> a
        <?= htmlspecialchars(date('d/m/Y', strtotime($linha['data_fim']))) ?> &nbsp;|&nbsp;
        <strong>Valor:</strong> R$ <?= number_format((float)$linha['valor'], 2, ',', '.') ?>
    </div>

    <img class="imagem-comprovante" src="uploads/<?= rawurlencode($linha['comprovante']) ?>" alt="Comprovante">
</body>
</html>
