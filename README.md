# 📊 Controle de Ações Comerciais — Hiperroll Embalagens

Sistema web interno para o time comercial da **Hiperroll Embalagens** registrar,
acompanhar e comprovar as ações comerciais realizadas junto às redes de clientes
(supermercados), como base para a aprovação e o pagamento das verbas envolvidas.

Substitui o antigo processo manual em formulário de Google Docs por um painel único,
com histórico centralizado, upload de comprovante por ação, fluxo de aprovação por
status e exportação de relatórios em Excel e PDF.

## 🧭 Contexto

As redes de supermercados realizam ações comerciais em troca de verbas pagas pela
Hiperroll — encartes, pontas de gôndola, degustação, TV indoor, ações de Sell-in e
Sell-out, entre outras. Cada ação precisa ser registrada com comprovante, analisada
e aprovada antes do pagamento. Este sistema dá ao gestor comercial um lugar único
para lançar, acompanhar e comprovar tudo isso.

## ✨ Funcionalidades

- 🔐 **Login protegido por sessão**, com senha armazenada com hash (bcrypt).
- 📝 **Cadastro de ações comerciais** com campos inteligentes (autocomplete que
  aprende com o que já foi digitado): rede, loja, representante e tipo de ação.
- 🧮 **Sell-in e Sell-out com cálculo automático**: ao escolher um desses tipos, o
  sistema pede a quantidade vendida no período e a recomposição por unidade, e
  calcula o valor total sozinho (quantidade × valor unitário) — recalculado também
  no servidor, para não depender do que o navegador enviar.
- 📅 **Período da ação** (data início e data fim), já que uma ação pode durar vários
  dias (ex: uma campanha de TV).
- 📎 **Upload de comprovante** (foto ou PDF) por ação.
- 🔄 **Status de aprovação**: Em análise → Aprovado → Pago.
- 🔍 **Filtros** por rede, representante, status e período.
- 📈 **Dashboard com totais** por status e total geral.
- 📤 **Exportação em Excel** (.xls) e **PDF** (via relatório de impressão), respeitando
  os filtros aplicados na tela.
- 🎨 **Identidade visual da Hiperroll** (cores, tipografia e logo) em todo o sistema.

## 🛠️ Stack tecnológica

- **PHP 8+** (sem framework) no back-end.
- **HTML, CSS e JavaScript puro** no front-end — sem build step, sem dependências
  de terceiros.
- **Armazenamento em arquivos JSON** (`data/acoes.json` e `data/usuarios.json`) em
  vez de banco de dados — mais simples de hospedar e manter em hospedagem
  compartilhada (HostGator/cPanel), com trava de arquivo (`flock`) para evitar
  corrupção em gravações simultâneas.

## 📁 Estrutura do projeto

```
index.html              Tela de login (estática, fala com api/login.php via JS)
dashboard.php           Painel principal (única tela após login)
logout.php              Encerra a sessão
relatorio.php           Relatório para impressão / salvar como PDF
exportar_excel.php      Exportação em Excel (.xls)

includes/
  config.php            Caminhos e nome do sistema
  dados.php             Leitura/escrita segura dos arquivos JSON
  consulta_acoes.php    Filtros compartilhados (listagem, Excel e PDF)
  auth.php              Controle de sessão

api/
  login.php             Valida usuário/senha e inicia a sessão
  sessao.php            Informa se já existe uma sessão ativa
  acoes.php             CRUD de ações comerciais (listar, salvar, status, excluir)

assets/
  css/style.css         Estilo visual (paleta e tipografia da Hiperroll)
  js/app.js             Lógica do painel (tabela, filtros, modal, Sell-in/Sell-out)
  js/login.js           Lógica da tela de login
  img/logo-hiperroll.png

data/                   acoes.json e usuarios.json (bloqueado por .htaccess, fora do Git)
uploads/                Comprovantes enviados (bloqueado por .htaccess, fora do Git)
setup/criar_admin.php   Cria o usuário administrador (uso único, sem senha fixa no código)
```

## 🚀 Como publicar

Requer apenas **PHP 8+** — nenhum banco de dados é necessário.

1. Suba todo o conteúdo do projeto para o servidor (ex: via Gerenciador de Arquivos
   do cPanel, em um domínio ou subdomínio).
2. Garanta que as pastas `data/` e `uploads/` tenham permissão de escrita (755
   geralmente já funciona; use 775 se der erro ao salvar).
3. Crie o usuário administrador acessando pelo navegador, substituindo os valores:
   ```
   https://seudominio.com.br/setup/criar_admin.php?usuario=SEU_USUARIO&senha=SUA_SENHA&nome=Seu+Nome
   ```
4. **Apague o arquivo `setup/criar_admin.php`** do servidor depois de usá-lo.
5. Acesse `https://seudominio.com.br/` e faça login.

## 🔒 Segurança

- Senhas nunca ficam em texto puro — são armazenadas com `password_hash` (bcrypt).
- Nenhuma credencial fica gravada no código-fonte (o script de criação de usuário
  recebe usuário/senha pela URL na hora do uso, não tem valores fixos).
- As pastas `data/`, `uploads/` e `includes/` têm `.htaccess` bloqueando acesso
  direto pela internet.
- `data/*.json` e os arquivos enviados em `uploads/` ficam fora do controle de
  versão (`.gitignore`) — são dados reais do negócio, não código.
- Upload de comprovante valida extensão (JPG, PNG, WEBP, PDF) e tamanho máximo.

## 🗺️ Roadmap

- [ ] Tela de "trocar senha" para o usuário logado.
- [ ] Mais de um usuário / nível de acesso (ex: representante só visualiza suas
      próprias ações).
- [ ] Gráficos de acompanhamento de verba por rede e por período.
- [ ] Histórico de alterações por ação (auditoria).

## 👤 Sobre

Desenvolvido internamente para a Hiperroll Embalagens, com apoio do
[Claude Code](https://claude.com/claude-code) (Anthropic) no desenvolvimento.
