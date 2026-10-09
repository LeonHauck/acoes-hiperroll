<?php
// Datas e horas gravadas pelo sistema (cadastro, histórico, relatórios) usam o horário de Brasília.
date_default_timezone_set('America/Sao_Paulo');

// Pasta (com barra no final) onde ficam os arquivos de dados (JSON) do sistema.
define('PASTA_DADOS', __DIR__ . '/../data/');

// Pasta (com barra no final) onde os comprovantes enviados ficam salvos.
define('PASTA_UPLOADS', __DIR__ . '/../uploads/');

// Nome da empresa/sistema, usado em títulos e relatórios.
define('NOME_SISTEMA', 'Controle de Ações Comerciais - Hiperroll Embalagens');
