(function () {
    'use strict';

    const NS = 'http://www.w3.org/2000/svg';
    const STATUS = ['em_analise', 'aprovado', 'pago'];
    const ROTULOS = { em_analise: 'Em análise', aprovado: 'Aprovado', pago: 'Pago' };
    const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    const MAX_REDES = 8;
    const MAX_MESES = 24;
    const FOLGA = 2;
    const RAIO = 4;

    const secao = document.getElementById('secaoGraficos');
    if (!secao) return;

    const contRedes = document.getElementById('graficoRedes');
    const contPeriodo = document.getElementById('graficoPeriodo');
    const subtituloRedes = document.getElementById('subtituloRedes');
    const subtituloPeriodo = document.getElementById('subtituloPeriodo');
    const legenda = document.getElementById('legendaGrafico');
    const botaoAlternar = document.getElementById('botaoAlternarTabela');

    const estado = { redes: [], meses: [], redesAgrupadas: false, mesesCortados: false, modoTabela: false, pronto: false };

    // ---------- utilitários ----------
    function criar(tag, atributos, texto) {
        const no = document.createElementNS(NS, tag);
        for (const [chave, valor] of Object.entries(atributos || {})) no.setAttribute(chave, valor);
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    function html(tag, classe, texto) {
        const no = document.createElement(tag);
        if (classe) no.className = classe;
        if (texto !== undefined) no.textContent = texto;
        return no;
    }

    function moeda(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function moedaCompacta(valor) {
        const abs = Math.abs(valor);
        if (abs >= 1e6) return 'R$ ' + (valor / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mi';
        if (abs >= 1e3) return 'R$ ' + (valor / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil';
        return 'R$ ' + valor.toLocaleString('pt-BR', { maximumFractionDigits: 0 });
    }

    function truncar(texto, maximo) {
        return texto.length > maximo ? texto.slice(0, maximo - 1) + '…' : texto;
    }

    function ticksLimpos(maximo, alvo) {
        if (!(maximo > 0)) return [0, 1];
        const bruto = maximo / alvo;
        const magnitude = Math.pow(10, Math.floor(Math.log10(bruto)));
        const norm = bruto / magnitude;
        const passo = (norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 2.5 ? 2.5 : norm <= 5 ? 5 : 10) * magnitude;
        const ticks = [0];
        let v = 0;
        while (v < maximo) {
            v += passo;
            ticks.push(Math.round(v * 1e6) / 1e6);
        }
        return ticks;
    }

    function descricao(nome, d) {
        const partes = STATUS.map((s) => `${ROTULOS[s]} ${moeda(d[s])}`);
        return `${nome}: ${partes.join(', ')}, total ${moeda(d.total)}`;
    }

    // ---------- agregação ----------
    function novoGrupo(nome) {
        return { nome, total: 0, em_analise: 0, aprovado: 0, pago: 0, acoes: 0 };
    }

    function somar(grupo, linha) {
        const status = STATUS.includes(linha.status) ? linha.status : 'em_analise';
        const valor = parseFloat(linha.valor) || 0;
        grupo[status] += valor;
        grupo.total += valor;
        grupo.acoes += 1;
    }

    function agruparPorRede(linhas) {
        const mapa = new Map();
        linhas.forEach((l) => {
            const nome = (l.rede || '').trim() || 'Sem rede';
            const chave = nome.toLowerCase();
            if (!mapa.has(chave)) mapa.set(chave, novoGrupo(nome));
            somar(mapa.get(chave), l);
        });

        let lista = Array.from(mapa.values()).sort((a, b) => b.total - a.total);
        let agrupadas = false;

        if (lista.length > MAX_REDES) {
            const topo = lista.slice(0, MAX_REDES - 1);
            const resto = lista.slice(MAX_REDES - 1);
            const outras = novoGrupo(`Outras (${resto.length} redes)`);
            resto.forEach((g) => {
                STATUS.forEach((s) => { outras[s] += g[s]; });
                outras.total += g.total;
                outras.acoes += g.acoes;
            });
            lista = topo.concat(outras);
            agrupadas = true;
        }

        return { lista, agrupadas };
    }

    function agruparPorMes(linhas) {
        const mapa = new Map();
        linhas.forEach((l) => {
            const chave = (l.data_inicio || '').slice(0, 7);
            if (!chave) return;
            if (!mapa.has(chave)) mapa.set(chave, novoGrupo(chave));
            somar(mapa.get(chave), l);
        });

        if (!mapa.size) return { lista: [], cortados: false };

        const chaves = Array.from(mapa.keys()).sort();
        let [ano, mes] = chaves[0].split('-').map(Number);
        const [anoFim, mesFim] = chaves[chaves.length - 1].split('-').map(Number);
        const meses = [];

        while (ano < anoFim || (ano === anoFim && mes <= mesFim)) {
            const chave = `${ano}-${String(mes).padStart(2, '0')}`;
            const grupo = mapa.get(chave) || novoGrupo(chave);
            grupo.nome = `${MESES[mes - 1]}/${String(ano).slice(2)}`;
            meses.push(grupo);
            mes += 1;
            if (mes > 12) { mes = 1; ano += 1; }
        }

        const cortados = meses.length > MAX_MESES;
        return { lista: cortados ? meses.slice(-MAX_MESES) : meses, cortados };
    }

    // ---------- tooltip ----------
    const tooltip = html('div', 'tooltip-grafico');
    tooltip.hidden = true;
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);

    function preencherTooltip(titulo, d) {
        tooltip.replaceChildren();
        tooltip.appendChild(html('div', 'tt-titulo', titulo));

        STATUS.forEach((s) => {
            const linha = html('div', 'tt-linha');
            linha.appendChild(html('span', 'tt-chave seg-' + s));
            linha.appendChild(html('span', 'tt-rotulo', ROTULOS[s]));
            linha.appendChild(html('strong', 'tt-valor', moeda(d[s])));
            tooltip.appendChild(linha);
        });

        const total = html('div', 'tt-linha tt-total');
        total.appendChild(html('span', 'tt-rotulo', `Total · ${d.acoes} ${d.acoes === 1 ? 'ação' : 'ações'}`));
        total.appendChild(html('strong', 'tt-valor', moeda(d.total)));
        tooltip.appendChild(total);
    }

    function posicionarTooltip(evento, alvo) {
        const distancia = 14;
        tooltip.hidden = false;
        const caixa = tooltip.getBoundingClientRect();
        let x;
        let y;

        if (evento) {
            x = evento.clientX + distancia;
            y = evento.clientY + distancia;
            if (x + caixa.width > window.innerWidth - 8) x = evento.clientX - distancia - caixa.width;
        } else {
            const r = alvo.getBoundingClientRect();
            x = r.left + r.width / 2 - caixa.width / 2;
            y = r.top - caixa.height - 8;
        }

        if (y + caixa.height > window.innerHeight - 8) y = window.innerHeight - caixa.height - 8;
        tooltip.style.left = Math.max(8, x) + 'px';
        tooltip.style.top = Math.max(8, y) + 'px';
    }

    function ligarInteracao(hit, grupo, titulo, dados) {
        const mostrar = (evento) => {
            grupo.classList.add('ativa');
            preencherTooltip(titulo, dados);
            posicionarTooltip(evento, hit);
        };
        const esconder = () => {
            grupo.classList.remove('ativa');
            tooltip.hidden = true;
        };

        hit.addEventListener('pointermove', mostrar);
        hit.addEventListener('pointerleave', esconder);
        hit.addEventListener('focus', () => mostrar(null));
        hit.addEventListener('blur', esconder);
    }

    // ---------- formas ----------
    function caminhoDireita(x, y, w, h, r) {
        r = Math.min(r, w, h / 2);
        return `M${x},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h - r}Q${x + w},${y + h} ${x + w - r},${y + h}H${x}Z`;
    }

    function caminhoTopo(x, y, w, h, r) {
        r = Math.min(r, h, w / 2);
        return `M${x},${y + h}V${y + r}Q${x},${y} ${x + r},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h}Z`;
    }

    function segmentosHorizontais(grupo, d, escalaX, y, espessura) {
        const ativos = STATUS.filter((s) => d[s] > 0);
        let acumulado = 0;

        ativos.forEach((s, i) => {
            const inicio = escalaX(acumulado);
            acumulado += d[s];
            const fim = escalaX(acumulado);
            const ultimo = i === ativos.length - 1;
            const largura = Math.max(fim - inicio - (ultimo ? 0 : FOLGA), 3);
            const forma = ultimo
                ? criar('path', { d: caminhoDireita(inicio, y, largura, espessura, RAIO) })
                : criar('rect', { x: inicio, y, width: largura, height: espessura });
            forma.setAttribute('class', 'seg seg-' + s);
            grupo.appendChild(forma);
        });
    }

    function segmentosVerticais(grupo, d, escalaY, x, espessura) {
        const ativos = STATUS.filter((s) => d[s] > 0);
        let acumulado = 0;

        ativos.forEach((s, i) => {
            const base = escalaY(acumulado);
            acumulado += d[s];
            const topoReal = escalaY(acumulado);
            const ultimo = i === ativos.length - 1;
            const topo = ultimo ? topoReal : topoReal + FOLGA;
            const altura = Math.max(base - topo, 3);
            const forma = ultimo
                ? criar('path', { d: caminhoTopo(x, base - altura, espessura, altura, RAIO) })
                : criar('rect', { x, y: base - altura, width: espessura, height: altura });
            forma.setAttribute('class', 'seg seg-' + s);
            grupo.appendChild(forma);
        });
    }

    function mensagemVazia(container) {
        container.replaceChildren(html('p', 'grafico-vazio', 'Sem dados para os filtros selecionados.'));
    }

    // ---------- gráfico: verba por rede (barras horizontais empilhadas) ----------
    function desenharRedes(dados) {
        if (!dados.length) return mensagemVazia(contRedes);

        const largura = Math.max(240, contRedes.clientWidth);
        const compacto = largura < 440;
        const m = { esq: compacto ? 104 : 146, dir: compacto ? 76 : 88, topo: 4, base: 28 };
        const alturaLinha = 34;
        const espessura = 20;
        const areaPlot = dados.length * alturaLinha;
        const altura = m.topo + areaPlot + m.base;
        const larguraPlot = largura - m.esq - m.dir;
        const ticks = ticksLimpos(Math.max(...dados.map((d) => d.total)), 4);
        const teto = ticks[ticks.length - 1];
        const escalaX = (v) => m.esq + (v / teto) * larguraPlot;

        const svg = criar('svg', { width: largura, height: altura, viewBox: `0 0 ${largura} ${altura}`, role: 'group', 'aria-label': 'Gráfico de barras: verba por rede' });

        ticks.forEach((t) => {
            const x = escalaX(t);
            svg.appendChild(criar('line', { x1: x, x2: x, y1: m.topo, y2: m.topo + areaPlot, class: 'grade' }));
            svg.appendChild(criar('text', { x, y: altura - 8, 'text-anchor': 'middle', class: 'eixo-texto' }, moedaCompacta(t)));
        });

        dados.forEach((d, i) => {
            const y = m.topo + i * alturaLinha;
            const grupo = criar('g', { class: 'linha' });

            grupo.appendChild(criar('rect', { x: 0, y, width: largura, height: alturaLinha, rx: 6, class: 'faixa-hover' }));
            grupo.appendChild(criar('text', { x: m.esq - 12, y: y + alturaLinha / 2, 'text-anchor': 'end', 'dominant-baseline': 'central', class: 'rotulo-categoria' }, truncar(d.nome, compacto ? 14 : 21)));
            segmentosHorizontais(grupo, d, escalaX, y + (alturaLinha - espessura) / 2, espessura);
            grupo.appendChild(criar('text', { x: escalaX(d.total) + 8, y: y + alturaLinha / 2, 'dominant-baseline': 'central', class: 'rotulo-valor' }, moedaCompacta(d.total)));

            const hit = criar('rect', { x: 0, y, width: largura, height: alturaLinha, fill: 'transparent', tabindex: 0, role: 'img', 'aria-label': descricao(d.nome, d), class: 'hit' });
            ligarInteracao(hit, grupo, d.nome, d);
            grupo.appendChild(hit);
            svg.appendChild(grupo);
        });

        contRedes.replaceChildren(svg);
    }

    // ---------- gráfico: verba por período (colunas empilhadas por mês) ----------
    function desenharPeriodo(dados) {
        if (!dados.length) return mensagemVazia(contPeriodo);

        const largura = Math.max(240, contPeriodo.clientWidth);
        const m = { esq: 64, dir: 8, topo: 26, base: 30 };
        const alturaPlot = 220;
        const altura = m.topo + alturaPlot + m.base;
        const larguraPlot = largura - m.esq - m.dir;
        const passo = larguraPlot / dados.length;
        const espessura = Math.min(24, passo * 0.6);
        const ticks = ticksLimpos(Math.max(...dados.map((d) => d.total)), 4);
        const teto = ticks[ticks.length - 1];
        const escalaY = (v) => m.topo + alturaPlot - (v / teto) * alturaPlot;
        const cadaRotulos = Math.max(1, Math.ceil(40 / passo));

        const svg = criar('svg', { width: largura, height: altura, viewBox: `0 0 ${largura} ${altura}`, role: 'group', 'aria-label': 'Gráfico de colunas: verba por período' });

        ticks.forEach((t) => {
            const y = escalaY(t);
            svg.appendChild(criar('line', { x1: m.esq, x2: largura - m.dir, y1: y, y2: y, class: 'grade' }));
            svg.appendChild(criar('text', { x: m.esq - 8, y, 'text-anchor': 'end', 'dominant-baseline': 'central', class: 'eixo-texto' }, moedaCompacta(t)));
        });

        dados.forEach((d, i) => {
            const xBanda = m.esq + i * passo;
            const centro = xBanda + passo / 2;
            const grupo = criar('g', { class: 'linha' });

            grupo.appendChild(criar('rect', { x: xBanda, y: m.topo, width: passo, height: alturaPlot, rx: 6, class: 'faixa-hover' }));
            segmentosVerticais(grupo, d, escalaY, centro - espessura / 2, espessura);

            if (passo >= 52 && d.total > 0) {
                grupo.appendChild(criar('text', { x: centro, y: escalaY(d.total) - 8, 'text-anchor': 'middle', class: 'rotulo-valor' }, moedaCompacta(d.total)));
            }
            if (i % cadaRotulos === 0) {
                grupo.appendChild(criar('text', { x: centro, y: altura - 8, 'text-anchor': 'middle', class: 'eixo-texto' }, d.nome));
            }

            const hit = criar('rect', { x: xBanda, y: m.topo, width: passo, height: alturaPlot, fill: 'transparent', tabindex: 0, role: 'img', 'aria-label': descricao(d.nome, d), class: 'hit' });
            ligarInteracao(hit, grupo, d.nome, d);
            grupo.appendChild(hit);
            svg.appendChild(grupo);
        });

        contPeriodo.replaceChildren(svg);
    }

    // ---------- visão em tabela (alternativa acessível ao gráfico) ----------
    function tabela(tituloColuna, dados) {
        if (!dados.length) return html('p', 'grafico-vazio', 'Sem dados para os filtros selecionados.');

        const envoltorio = html('div', 'tabela-grafico-wrap');
        const tab = html('table', 'tabela-grafico');
        const cabecalho = html('tr');

        [tituloColuna, ROTULOS.em_analise, ROTULOS.aprovado, ROTULOS.pago, 'Total', 'Ações'].forEach((texto, i) => {
            cabecalho.appendChild(html('th', i > 0 ? 'num' : '', texto));
        });
        tab.appendChild(html('thead')).appendChild(cabecalho);

        const corpo = html('tbody');
        dados.forEach((d) => {
            const linha = html('tr');
            linha.appendChild(html('td', '', d.nome));
            STATUS.forEach((s) => linha.appendChild(html('td', 'num', moeda(d[s]))));
            linha.appendChild(html('td', 'num destaque', moeda(d.total)));
            linha.appendChild(html('td', 'num', String(d.acoes)));
            corpo.appendChild(linha);
        });
        tab.appendChild(corpo);
        envoltorio.appendChild(tab);
        return envoltorio;
    }

    // ---------- orquestração ----------
    function desenhar() {
        subtituloRedes.textContent = estado.redesAgrupadas
            ? `As ${MAX_REDES - 1} maiores redes; as demais somadas em "Outras"`
            : 'Total por rede, dividido por status';
        subtituloPeriodo.textContent = estado.mesesCortados
            ? `Últimos ${MAX_MESES} meses, por mês de início da ação`
            : 'Total por mês de início da ação, dividido por status';

        legenda.hidden = estado.modoTabela;
        botaoAlternar.textContent = estado.modoTabela ? 'Ver gráficos' : 'Ver como tabela';
        botaoAlternar.setAttribute('aria-pressed', String(estado.modoTabela));
        tooltip.hidden = true;

        if (estado.modoTabela) {
            contRedes.replaceChildren(tabela('Rede', estado.redes));
            contPeriodo.replaceChildren(tabela('Mês', estado.meses));
        } else {
            desenharRedes(estado.redes);
            desenharPeriodo(estado.meses);
        }
    }

    botaoAlternar.addEventListener('click', () => {
        estado.modoTabela = !estado.modoTabela;
        desenhar();
    });

    let larguraAnterior = 0;
    let quadro = 0;
    new ResizeObserver(() => {
        const largura = Math.round(secao.clientWidth);
        if (!estado.pronto || estado.modoTabela || largura === larguraAnterior) return;
        larguraAnterior = largura;
        cancelAnimationFrame(quadro);
        quadro = requestAnimationFrame(desenhar);
    }).observe(secao);

    window.Graficos = {
        carregando(sim) {
            secao.classList.toggle('carregando', sim);
        },
        atualizar(linhas) {
            const redes = agruparPorRede(linhas);
            const meses = agruparPorMes(linhas);
            estado.redes = redes.lista;
            estado.redesAgrupadas = redes.agrupadas;
            estado.meses = meses.lista;
            estado.mesesCortados = meses.cortados;
            estado.pronto = true;
            larguraAnterior = Math.round(secao.clientWidth);
            secao.classList.remove('carregando');
            desenhar();
        },
    };
})();
