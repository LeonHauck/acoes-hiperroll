<?php
require_once __DIR__ . '/includes/dados.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Controle de Ações Comerciais | Hiperroll</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="cabecalho-app">
    <div class="cabecalho-conteudo">
        <span class="logo-cabecalho-caixa">
            <img src="assets/img/logo-hiperroll.png" alt="Hiperroll Embalagens" class="logo-cabecalho">
        </span>
        <div class="titulo-cabecalho">
            <h1>Controle de Ações Comerciais</h1>
            <span>Gestão de verbas com redes de clientes</span>
        </div>
        <div class="usuario-cabecalho">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
            <a href="logout.php" class="botao botao-secundario">Sair</a>
        </div>
    </div>
</header>

<main class="conteudo-principal">

    <section class="cartoes-resumo" id="cartoesResumo">
        <div class="cartao-resumo cartao-analise">
            <span class="rotulo-cartao">Em análise</span>
            <strong id="totalEmAnalise">R$ 0,00</strong>
            <small id="qtdEmAnalise">0 ações</small>
        </div>
        <div class="cartao-resumo cartao-aprovado">
            <span class="rotulo-cartao">Aprovado</span>
            <strong id="totalAprovado">R$ 0,00</strong>
            <small id="qtdAprovado">0 ações</small>
        </div>
        <div class="cartao-resumo cartao-pago">
            <span class="rotulo-cartao">Pago</span>
            <strong id="totalPago">R$ 0,00</strong>
            <small id="qtdPago">0 ações</small>
        </div>
        <div class="cartao-resumo cartao-geral">
            <span class="rotulo-cartao">Total geral</span>
            <strong id="totalGeral">R$ 0,00</strong>
            <small id="qtdGeral">0 ações</small>
        </div>
    </section>

    <section class="painel">
        <div class="painel-cabecalho">
            <h2>Ações Comerciais</h2>
            <button type="button" class="botao botao-primario" id="botaoNovaAcao">+ Nova Ação</button>
        </div>

        <form id="formFiltros" class="barra-filtros">
            <div class="campo-filtro">
                <label>Rede</label>
                <input type="text" name="rede" placeholder="Todas">
            </div>
            <div class="campo-filtro">
                <label>Representante</label>
                <input type="text" name="representante" placeholder="Todos">
            </div>
            <div class="campo-filtro">
                <label>Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="em_analise">Em análise</option>
                    <option value="aprovado">Aprovado</option>
                    <option value="pago">Pago</option>
                </select>
            </div>
            <div class="campo-filtro">
                <label>De</label>
                <input type="date" name="periodo_de">
            </div>
            <div class="campo-filtro">
                <label>Até</label>
                <input type="date" name="periodo_ate">
            </div>
            <div class="campo-filtro botoes-filtro">
                <button type="submit" class="botao botao-primario">Filtrar</button>
                <button type="button" class="botao botao-secundario" id="botaoLimparFiltros">Limpar</button>
            </div>
        </form>

        <div class="barra-exportar">
            <a href="#" id="linkExportarExcel" class="botao botao-secundario" target="_blank">Exportar Excel</a>
            <a href="#" id="linkExportarPdf" class="botao botao-secundario" target="_blank">Exportar PDF</a>
        </div>

        <div class="tabela-wrapper">
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <th>Rede</th>
                        <th>Loja</th>
                        <th>Representante</th>
                        <th>Tipo de Ação</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Valor</th>
                        <th>Comprovante</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="corpoTabela">
                    <tr><td colspan="10" class="tabela-vazia">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Modal de cadastro/edição -->
<div class="modal-fundo" id="modalAcao">
    <div class="modal-caixa">
        <div class="modal-cabecalho">
            <h3 id="modalTitulo">Nova Ação Comercial</h3>
            <button type="button" class="modal-fechar" id="botaoFecharModal">&times;</button>
        </div>

        <form id="formAcao" enctype="multipart/form-data">
            <input type="hidden" name="id" id="campoId">
            <input type="hidden" name="acao" value="salvar">

            <div class="grade-formulario">
                <div class="campo">
                    <label for="campoRede">Rede *</label>
                    <input type="text" id="campoRede" name="rede" list="listaRedes" required>
                    <datalist id="listaRedes"></datalist>
                </div>
                <div class="campo">
                    <label for="campoLoja">Loja *</label>
                    <input type="text" id="campoLoja" name="loja" list="listaLojas" required>
                    <datalist id="listaLojas"></datalist>
                </div>
                <div class="campo">
                    <label for="campoRepresentante">Representante *</label>
                    <input type="text" id="campoRepresentante" name="representante" list="listaRepresentantes" required>
                    <datalist id="listaRepresentantes"></datalist>
                </div>
                <div class="campo">
                    <label for="campoTipoAcao">Tipo de Ação *</label>
                    <input type="text" id="campoTipoAcao" name="tipo_acao" list="listaTiposAcao" placeholder="Ex: Ponta de gôndola, Encarte, Degustação..." required>
                    <datalist id="listaTiposAcao">
                        <option value="Sell-in">
                        <option value="Sell-out">
                        <option value="Encarte">
                        <option value="Ponta de gôndola">
                        <option value="Degustação">
                        <option value="Tabloide">
                        <option value="TV indoor">
                        <option value="Tremonha">
                    </datalist>
                </div>
                <div class="campo">
                    <label for="campoDataInicio">Data Início *</label>
                    <input type="date" id="campoDataInicio" name="data_inicio" required>
                </div>
                <div class="campo">
                    <label for="campoDataFim">Data Fim *</label>
                    <input type="date" id="campoDataFim" name="data_fim" required>
                </div>

                <div class="campo campo-largo bloco-sellinout" id="blocoSellInOut" hidden>
                    <div class="grade-sellinout">
                        <div class="campo">
                            <label for="campoQuantidade">Quantidade Vendida no Período</label>
                            <input type="number" id="campoQuantidade" name="quantidade" min="0" step="1" placeholder="Preencha quando souber">
                        </div>
                        <div class="campo">
                            <label for="campoValorUnitario">Recomposição por Unidade (R$) *</label>
                            <input type="number" id="campoValorUnitario" name="valor_unitario" min="0" step="0.01">
                        </div>
                    </div>
                    <small class="texto-ajuda">O valor total é calculado automaticamente: quantidade × recomposição por unidade. Se a quantidade ainda não fechou, deixe em branco — o valor fica pendente (R$ 0,00) até você completar depois, editando a ação.</small>
                </div>

                <div class="campo">
                    <label for="campoValor">Valor (R$) *</label>
                    <input type="number" id="campoValor" name="valor" step="0.01" min="0" required>
                </div>
                <div class="campo">
                    <label for="campoStatus">Status</label>
                    <select id="campoStatus" name="status">
                        <option value="em_analise">Em análise</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="pago">Pago</option>
                    </select>
                </div>
                <div class="campo campo-largo">
                    <label for="campoComprovante">Comprovante (foto ou PDF)</label>
                    <input type="file" id="campoComprovante" name="comprovante" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small id="comprovanteAtual" class="texto-ajuda"></small>
                </div>
                <div class="campo campo-largo">
                    <label for="campoObservacoes">Observações</label>
                    <textarea id="campoObservacoes" name="observacoes" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-rodape">
                <span class="mensagem-formulario" id="mensagemFormulario"></span>
                <button type="button" class="botao botao-secundario" id="botaoCancelar">Cancelar</button>
                <button type="submit" class="botao botao-primario">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
