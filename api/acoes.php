<?php
require_once __DIR__ . '/../includes/dados.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/consulta_acoes.php';

exigirLoginApi();
header('Content-Type: application/json; charset=utf-8');

$acaoRequisicao = $_GET['acao'] ?? $_POST['acao'] ?? '';

$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
$tamanhoMaximo = 8 * 1024 * 1024; // 8MB

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($acaoRequisicao) {

    case 'listar': {
        $todasLinhas = listarAcoes();
        $linhas = buscarAcoesFiltradas($todasLinhas, $_GET);

        // Listas de sugestão (autocomplete) a partir do que já foi cadastrado.
        $sugestoes = ['rede' => [], 'loja' => [], 'representante' => [], 'tipo_acao' => []];
        foreach ($todasLinhas as $linha) {
            foreach ($sugestoes as $campo => &$valores) {
                if (!empty($linha[$campo]) && !in_array($linha[$campo], $valores, true)) {
                    $valores[] = $linha[$campo];
                }
            }
            unset($valores);
        }
        foreach ($sugestoes as &$valores) {
            sort($valores, SORT_STRING | SORT_FLAG_CASE);
        }
        unset($valores);

        responder(['linhas' => $linhas, 'sugestoes' => $sugestoes]);
        break;
    }

    case 'salvar': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(['erro' => 'Método inválido.'], 405);
        }

        $id = $_POST['id'] ?? '';
        $rede = trim($_POST['rede'] ?? '');
        $loja = trim($_POST['loja'] ?? '');
        $representante = trim($_POST['representante'] ?? '');
        $tipoAcao = trim($_POST['tipo_acao'] ?? '');
        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';
        $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
        $observacoes = trim($_POST['observacoes'] ?? '');
        $status = $_POST['status'] ?? 'em_analise';

        if ($rede === '' || $loja === '' || $representante === '' || $tipoAcao === '' || $dataInicio === '' || $dataFim === '') {
            responder(['erro' => 'Preencha todos os campos obrigatórios.'], 422);
        }

        if ($dataFim < $dataInicio) {
            responder(['erro' => 'A data fim não pode ser anterior à data início.'], 422);
        }

        // Sell-in e Sell-out têm o valor calculado a partir da quantidade vendida
        // no período multiplicada pela recomposição por unidade — o valor é
        // recalculado aqui no servidor para não depender do que o navegador enviou.
        // A quantidade só costuma ser conhecida no fim da ação, então ela é opcional
        // no cadastro: sem quantidade, o valor fica pendente (0) até ser completada
        // depois, editando a ação.
        $quantidade = null;
        $valorUnitario = null;

        if (in_array(mb_strtolower($tipoAcao), ['sell-in', 'sell-out'], true)) {
            $quantidadeInformada = trim($_POST['quantidade'] ?? '');
            $quantidade = $quantidadeInformada !== '' ? (int) $quantidadeInformada : null;
            $valorUnitario = (float) str_replace(',', '.', $_POST['valor_unitario'] ?? '0');

            if ($valorUnitario <= 0) {
                responder(['erro' => 'Informe a recomposição por unidade.'], 422);
            }

            $valor = $quantidade ? round($quantidade * $valorUnitario, 2) : 0.0;
        }

        if (!in_array($status, ['em_analise', 'aprovado', 'pago'], true)) {
            $status = 'em_analise';
        }

        $nomeArquivo = null;

        if (!empty($_FILES['comprovante']['name'])) {
            $arquivo = $_FILES['comprovante'];

            if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                responder(['erro' => 'Falha no envio do comprovante.'], 422);
            }
            if ($arquivo['size'] > $tamanhoMaximo) {
                responder(['erro' => 'Comprovante maior que 8MB.'], 422);
            }

            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, $extensoesPermitidas, true)) {
                responder(['erro' => 'Formato de comprovante não permitido. Use JPG, PNG, WEBP ou PDF.'], 422);
            }

            $nomeArquivo = uniqid('comp_', true) . '.' . $extensao;
            $destino = PASTA_UPLOADS . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                responder(['erro' => 'Não foi possível salvar o comprovante no servidor.'], 500);
            }
        }

        $resultado = null;
        $erro = null;
        $arquivoAntigoParaRemover = null;

        alterarAcoes(function (array $linhas) use (
            $id, $rede, $loja, $representante, $tipoAcao, $dataInicio, $dataFim, $valor, $status, $observacoes, $nomeArquivo,
            $quantidade, $valorUnitario, &$resultado, &$erro, &$arquivoAntigoParaRemover
        ) {
            if ($id !== '') {
                $encontrado = false;
                foreach ($linhas as &$linha) {
                    if ((string)$linha['id'] === (string)$id) {
                        $encontrado = true;

                        $comprovanteFinal = $linha['comprovante'] ?? null;
                        if ($nomeArquivo !== null) {
                            $arquivoAntigoParaRemover = $comprovanteFinal;
                            $comprovanteFinal = $nomeArquivo;
                        }

                        $linha = [
                            'id' => $linha['id'],
                            'rede' => $rede,
                            'loja' => $loja,
                            'representante' => $representante,
                            'tipo_acao' => $tipoAcao,
                            'data_inicio' => $dataInicio,
                            'data_fim' => $dataFim,
                            'valor' => $valor,
                            'quantidade' => $quantidade,
                            'valor_unitario' => $valorUnitario,
                            'status' => $linha['status'] ?? $status,
                            'observacoes' => $observacoes,
                            'comprovante' => $comprovanteFinal,
                            'criado_em' => $linha['criado_em'] ?? date('Y-m-d H:i:s'),
                            'atualizado_em' => date('Y-m-d H:i:s'),
                        ];
                        $resultado = ['sucesso' => true, 'id' => $linha['id']];
                        break;
                    }
                }
                unset($linha);

                if (!$encontrado) {
                    $erro = ['erro' => 'Registro não encontrado.', 'codigo' => 404];
                }
            } else {
                $novoId = proximoId($linhas);
                $linhas[] = [
                    'id' => $novoId,
                    'rede' => $rede,
                    'loja' => $loja,
                    'representante' => $representante,
                    'tipo_acao' => $tipoAcao,
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'valor' => $valor,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $valorUnitario,
                    'status' => $status,
                    'observacoes' => $observacoes,
                    'comprovante' => $nomeArquivo,
                    'criado_em' => date('Y-m-d H:i:s'),
                    'atualizado_em' => date('Y-m-d H:i:s'),
                ];
                $resultado = ['sucesso' => true, 'id' => $novoId];
            }

            return $linhas;
        });

        if ($arquivoAntigoParaRemover) {
            @unlink(PASTA_UPLOADS . $arquivoAntigoParaRemover);
        }

        if ($erro) {
            responder($erro, $erro['codigo']);
        }

        responder($resultado);
        break;
    }

    case 'status': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(['erro' => 'Método inválido.'], 405);
        }

        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? '';

        if (!$id || !in_array($status, ['em_analise', 'aprovado', 'pago'], true)) {
            responder(['erro' => 'Dados inválidos.'], 422);
        }

        alterarAcoes(function (array $linhas) use ($id, $status) {
            foreach ($linhas as &$linha) {
                if ((string)$linha['id'] === (string)$id) {
                    $linha['status'] = $status;
                    $linha['atualizado_em'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($linha);
            return $linhas;
        });

        responder(['sucesso' => true]);
        break;
    }

    case 'excluir': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(['erro' => 'Método inválido.'], 405);
        }

        $id = $_POST['id'] ?? '';
        if (!$id) {
            responder(['erro' => 'ID inválido.'], 422);
        }

        $arquivoParaRemover = null;

        alterarAcoes(function (array $linhas) use ($id, &$arquivoParaRemover) {
            $restantes = [];
            foreach ($linhas as $linha) {
                if ((string)$linha['id'] === (string)$id) {
                    $arquivoParaRemover = $linha['comprovante'] ?? null;
                    continue;
                }
                $restantes[] = $linha;
            }
            return $restantes;
        });

        if ($arquivoParaRemover) {
            @unlink(PASTA_UPLOADS . $arquivoParaRemover);
        }

        responder(['sucesso' => true]);
        break;
    }

    default:
        responder(['erro' => 'Ação desconhecida.'], 400);
}
