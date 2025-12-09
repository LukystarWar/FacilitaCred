<?php
/**
 * Configurações Gerais do Sistema
 * Facilita Cred - Loan Management System
 */

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações da Aplicação
define('APP_NAME', 'Facilita Cred');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, production

// Paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('FEATURES_PATH', BASE_PATH . '/features');
define('SHARED_PATH', BASE_PATH . '/shared');
define('CORE_PATH', BASE_PATH . '/core');

// URLs
define('BASE_URL', 'http://localhost/FacilitaCred/public');
define('ASSETS_URL', BASE_URL . '/assets');

// Sessão
define('SESSION_NAME', 'facilita_cred_session');
define('SESSION_LIFETIME', 7200); // 2 horas em segundos

// Paginação
define('ITEMS_PER_PAGE', 20);

// Formato de datas
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Regras de negócio
define('INTEREST_RATE_SINGLE_PAYMENT', 20); // 20% à vista
define('INTEREST_RATE_INSTALLMENT', 15); // 15% ao mês

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
