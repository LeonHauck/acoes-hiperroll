(function () {
    'use strict';

    const rotulosStatus = {
        em_analise: 'Em análise',
        aprovado: 'Aprovado',
        pago: 'Pago',
    };

    const tiposAcaoPadrao = ['Sell-in', 'Sell-out', 'Encarte', 'Ponta de gôndola', 'Degustação', 'Tabloide', 'TV indoor', 'Tremonha'];
    const tiposSellInOut = ['sell-in', 'sell-out'];

    const corpoTabela = document.getElementById('corpoTabela');
    const formFiltros = document.getElementById('formFiltros');
    const modal = document.getElementById('modalAcao');
    const formAcao = document.getElementById('formAcao');
    const mensagemFormulario = document.getElementById('mensagemFormulario');

    const campoTipoAcao = document.getElementById('campoTipoAcao');
    const blocoSellInOut = document.getElementById('blocoSellInOut');
    const campoQuantidade = document.getElementById('campoQuantidade');
    const campoValorUnitario = document.getElementById('campoValorUnitario');
    const campoValor = document.getElementById('campoValor');

    function ehTipoSellInOut(valor) {
        return tiposSellInOut.includes((valor || '').trim().toLowerCase());
    }

    function recalcularValorSellInOut() {
        const quantidade = parseFloat(campoQuantidade.value) || 0;
        const valorUnitario = parseFloat(campoValorUnitario.value) || 0;
        campoValor.value = (quantidade * valorUnitario).toFixed(2);
    }

    function atualizarModoSellInOut() {
        const ativo = ehTipoSellInOut(campoTipoAcao.value);

        blocoSellInOut.hidden = !ativo;
        campoQuantidade.required = false;
        campoValorUnitario.required = ativo;
        campoValor.readOnly = ativo;

        if (ativo) {
            recalcularValorSellInOut();
        }
    }

    campoTipoAcao.addEventListener('input', atualizarModoSellInOut);
    campoQuantidade.addEventListener('input', recalcularValorSellInOut);
    campoValorUnitario.addEventListener('input', recalcularValorSellInOut);

    function formatarMoeda(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatarData(dataIso) {
        const [ano, mes, dia] = dataIso.split('-');
        return `${dia}/${mes}/${ano}`;
    }

    function obterFiltros() {
        const dados = new FormData(formFiltros);
        const params = new URLSearchParams();
        for (const [chave, valor] of dados.entries()) {
            if (valor) params.append(chave, valor);
        }
        return params;
    }

    function atualizarLinksExportacao(params) {
        const query = params.toString();
        document.getElementById('linkExportarExcel').href = 'exportar_excel.php' + (query ? '?' + query : '');
        document.getElementById('linkExportarPdf').href = 'relatorio.php' + (query ? '?' + query : '');
    }

    function preencherDatalist(id, valores) {
        const lista = document.getElementById(id);
        lista.innerHTML = '';
        valores.forEach((v) => {
            const opcao = document.createElement('option');
            opcao.value = v;
            lista.appendChild(opcao);
        });
    }

    function atualizarCartoes(linhas) {
        const totais = { em_analise: 0, aprovado: 0, pago: 0 };
        const quantidades = { em_analise: 0, aprovado: 0, pago: 0 };

        linhas.forEach((l) => {
            totais[l.status] += parseFloat(l.valor);
            quantidades[l.status] += 1;
        });

        const totalGeral = totais.em_analise + totais.aprovado + totais.pago;

        document.getElementById('totalEmAnalise').textContent = formatarMoeda(totais.em_analise);
        document.getElementById('qtdEmAnalise').textContent = quantidades.em_analise + ' ação(ões)';

        document.getElementById('totalAprovado').textContent = formatarMoeda(totais.aprovado);
        document.getElementById('qtdAprovado').textContent = quantidades.aprovado + ' ação(ões)';

        document.getElementById('totalPago').textContent = formatarMoeda(totais.pago);
        document.getElementById('qtdPago').textContent = quantidades.pago + ' ação(ões)';

        document.getElementById('totalGeral').textContent = formatarMoeda(totalGeral);
        document.getElementById('qtdGeral').textContent = linhas.length + ' ação(ões)';
    }

    function renderizarTabela(linhas) {
        if (!linhas.length) {
            corpoTabela.innerHTML = '<tr><td colspan="10" class="tabela-vazia">Nenhuma ação encontrada.</td></tr>';
            return;
        }

        corpoTabela.innerHTML = linhas.map((l) => `
            <tr>
                <td>${escapeHtml(l.rede)}</td>
                <td>${escapeHtml(l.loja)}</td>
                <td>${escapeHtml(l.representante)}</td>
                <td>${escapeHtml(l.tipo_acao)}${detalheSellInOut(l)}</td>
                <td>${formatarData(l.data_inicio)}</td>
                <td>${formatarData(l.data_fim)}</td>
                <td>${formatarMoeda(l.valor)}</td>
                <td>${l.comprovante ? `<a class="link-comprovante" href="uploads/${encodeURIComponent(l.comprovante)}" target="_blank">Ver arquivo</a>` : '<span class="sem-comprovante">—</span>'}</td>
                <td>
                    <select class="select-status-tabela status-${l.status}" data-id="${l.id}" data-acao="mudar-status">
                        <option value="em_analise" ${l.status === 'em_analise' ? 'selected' : ''}>Em análise</option>
                        <option value="aprovado" ${l.status === 'aprovado' ? 'selected' : ''}>Aprovado</option>
                        <option value="pago" ${l.status === 'pago' ? 'selected' : ''}>Pago</option>
                    </select>
                </td>
                <td>
                    <div class="acoes-linha">
                        <button type="button" class="botao-icone editar" data-acao="editar" data-id="${l.id}">Editar</button>
                        <button type="button" class="botao-icone excluir" data-acao="excluir" data-id="${l.id}">Excluir</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function detalheSellInOut(l) {
        if (!l.valor_unitario) return '';
        if (l.quantidade) {
            return `<br><small class="detalhe-sellinout">${l.quantidade} un × ${formatarMoeda(l.valor_unitario)}</small>`;
        }
        return `<br><small class="detalhe-sellinout detalhe-pendente">Aguardando quantidade · ${formatarMoeda(l.valor_unitario)}/un</small>`;
    }

    function escapeHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    let cacheLinhas = [];

    async function carregarAcoes() {
        const params = obterFiltros();
        params.append('acao', 'listar');
        atualizarLinksExportacao(obterFiltros());

        corpoTabela.innerHTML = '<tr><td colspan="10" class="tabela-vazia">Carregando...</td></tr>';

        try {
            const resposta = await fetch('api/acoes.php?' + params.toString());
            const dados = await resposta.json();

            if (dados.erro) {
                corpoTabela.innerHTML = `<tr><td colspan="10" class="tabela-vazia">${escapeHtml(dados.erro)}</td></tr>`;
                return;
            }

            cacheLinhas = dados.linhas;
            renderizarTabela(dados.linhas);
            atualizarCartoes(dados.linhas);

            preencherDatalist('listaRedes', dados.sugestoes.rede);
            preencherDatalist('listaLojas', dados.sugestoes.loja);
            preencherDatalist('listaRepresentantes', dados.sugestoes.representante);

            const tiposAcaoCombinados = Array.from(new Set([...tiposAcaoPadrao, ...dados.sugestoes.tipo_acao])).sort();
            preencherDatalist('listaTiposAcao', tiposAcaoCombinados);
        } catch (erro) {
            corpoTabela.innerHTML = '<tr><td colspan="10" class="tabela-vazia">Erro ao carregar os dados. Tente novamente.</td></tr>';
        }
    }

    // ---------- Filtros ----------
    formFiltros.addEventListener('submit', (e) => {
        e.preventDefault();
        carregarAcoes();
    });

    document.getElementById('botaoLimparFiltros').addEventListener('click', () => {
        formFiltros.reset();
        carregarAcoes();
    });

    // ---------- Modal ----------
    function abrirModal(titulo) {
        document.getElementById('modalTitulo').textContent = titulo;
        mensagemFormulario.textContent = '';
        modal.classList.add('aberto');
    }

    function fecharModal() {
        modal.classList.remove('aberto');
        formAcao.reset();
        document.getElementById('campoId').value = '';
        document.getElementById('comprovanteAtual').textContent = '';
        atualizarModoSellInOut();
    }

    document.getElementById('botaoNovaAcao').addEventListener('click', () => {
        fecharModal();
        abrirModal('Nova Ação Comercial');
    });

    document.getElementById('botaoFecharModal').addEventListener('click', fecharModal);
    document.getElementById('botaoCancelar').addEventListener('click', fecharModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) fecharModal();
    });

    formAcao.addEventListener('submit', async (e) => {
        e.preventDefault();
        mensagemFormulario.textContent = 'Salvando...';

        try {
            const resposta = await fetch('api/acoes.php', {
                method: 'POST',
                body: new FormData(formAcao),
            });
            const dados = await resposta.json();

            if (dados.erro) {
                mensagemFormulario.textContent = dados.erro;
                return;
            }

            fecharModal();
            carregarAcoes();
        } catch (erro) {
            mensagemFormulario.textContent = 'Erro ao salvar. Verifique sua conexão e tente novamente.';
        }
    });

    // ---------- Ações na tabela (editar, excluir, mudar status) ----------
    corpoTabela.addEventListener('click', async (e) => {
        const botao = e.target.closest('button[data-acao]');
        if (!botao) return;

        const id = botao.dataset.id;
        const linha = cacheLinhas.find((l) => String(l.id) === String(id));
        if (!linha) return;

        if (botao.dataset.acao === 'editar') {
            abrirModal('Editar Ação Comercial');
            document.getElementById('campoId').value = linha.id;
            document.getElementById('campoRede').value = linha.rede;
            document.getElementById('campoLoja').value = linha.loja;
            document.getElementById('campoRepresentante').value = linha.representante;
            document.getElementById('campoTipoAcao').value = linha.tipo_acao;
            document.getElementById('campoDataInicio').value = linha.data_inicio;
            document.getElementById('campoDataFim').value = linha.data_fim;
            atualizarModoSellInOut();
            if (ehTipoSellInOut(linha.tipo_acao)) {
                campoQuantidade.value = linha.quantidade ?? '';
                campoValorUnitario.value = linha.valor_unitario ?? '';
                recalcularValorSellInOut();
            } else {
                document.getElementById('campoValor').value = linha.valor;
            }
            document.getElementById('campoStatus').value = linha.status;
            document.getElementById('campoObservacoes').value = linha.observacoes || '';
            document.getElementById('comprovanteAtual').textContent = linha.comprovante
                ? 'Comprovante atual: ' + linha.comprovante + ' (envie um novo arquivo para substituir)'
                : '';
        }

        if (botao.dataset.acao === 'excluir') {
            if (!confirm(`Excluir a ação "${linha.tipo_acao}" da rede ${linha.rede}? Essa ação não pode ser desfeita.`)) return;

            const dados = new FormData();
            dados.append('acao', 'excluir');
            dados.append('id', id);

            await fetch('api/acoes.php', { method: 'POST', body: dados });
            carregarAcoes();
        }
    });

    corpoTabela.addEventListener('change', async (e) => {
        const select = e.target.closest('select[data-acao="mudar-status"]');
        if (!select) return;

        const dados = new FormData();
        dados.append('acao', 'status');
        dados.append('id', select.dataset.id);
        dados.append('status', select.value);

        await fetch('api/acoes.php', { method: 'POST', body: dados });
        carregarAcoes();
    });

    carregarAcoes();
})();
