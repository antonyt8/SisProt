<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/utils/cpf.php';
require_once dirname(__DIR__) . '/utils/protocolo.php';
require_once dirname(__DIR__) . '/utils/logger.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/models/ProtocoloGenerationException.php';
require_once dirname(__DIR__) . '/models/Processo.php';
require_once dirname(__DIR__) . '/models/Tramitacao.php';
require_once dirname(__DIR__) . '/models/Setor.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/ProcessoController.php';
require_once dirname(__DIR__) . '/services/ProcessoService.php';
