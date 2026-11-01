<?php
declare(strict_types=1);

$raiz = dirname(__DIR__, 2);
require_once $raiz . '/vendor/autoload.php';

require_once $raiz . '/helpers/Prazo.php';
require_once $raiz . '/helpers/StatusRegra.php';
require_once $raiz . '/helpers/Senha.php';
require_once $raiz . '/helpers/NotificacaoProducao.php';
require_once $raiz . '/helpers/ProducaoService.php';
require_once $raiz . '/models/Pedidostatus.php';
require_once $raiz . '/models/Usuario.php';
require_once $raiz . '/controllers/RecuperacaoController.php';
require_once $raiz . '/controllers/AlterarSenhaController.php';
