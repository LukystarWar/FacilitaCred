<?php
/**
 * Helper Functions
 * Funções auxiliares globais do sistema
 */

/**
 * Sanitiza uma string (previne XSS)
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata um valor para moeda brasileira
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata uma data do formato do banco para exibição
 */
function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Converte uma data do formato brasileiro para o formato do banco
 */
function dateToDb($date) {
    if (empty($date)) {
        return null;
    }
    $dateTime = DateTime::createFromFormat('d/m/Y', $date);
    return $dateTime ? $dateTime->format('Y-m-d') : null;
}

/**
 * Retorna o status de uma parcela com base na data de vencimento
 */
function getInstallmentStatus($dueDate, $paidDate) {
    if ($paidDate) {
        return 'paid';
    }

    $today = new DateTime();
    $due = new DateTime($dueDate);

    if ($due < $today) {
        return 'overdue';
    }

    return 'pending';
}

/**
 * Retorna a classe CSS para o status
 */
function getStatusClass($status) {
    $classes = [
        'paid' => 'status-success',
        'pending' => 'status-warning',
        'overdue' => 'status-danger',
        'active' => 'status-info',
    ];

    return $classes[$status] ?? 'status-default';
}

/**
 * Retorna o texto do status em português
 */
function getStatusText($status) {
    $texts = [
        'paid' => 'Pago',
        'pending' => 'Pendente',
        'overdue' => 'Atrasado',
        'active' => 'Ativo',
    ];

    return $texts[$status] ?? $status;
}

/**
 * Calcula o total de juros baseado no tipo de pagamento
 */
function calculateInterest($amount, $installments = 1) {
    if ($installments === 1) {
        // À vista: 20%
        return $amount * (INTEREST_RATE_SINGLE_PAYMENT / 100);
    } else {
        // Parcelado: 15% ao mês
        $rate = INTEREST_RATE_INSTALLMENT * $installments;
        return $amount * ($rate / 100);
    }
}

/**
 * Calcula o valor total com juros
 */
function calculateTotalWithInterest($amount, $installments = 1) {
    $interest = calculateInterest($amount, $installments);
    return $amount + $interest;
}

/**
 * Valida CPF
 */
function validateCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Validação do primeiro dígito verificador
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

/**
 * Formata CPF
 */
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Formata telefone
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    } elseif (strlen($phone) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
    }

    return $phone;
}

/**
 * Redireciona com mensagem flash
 */
function redirectWithMessage($url, $type, $message) {
    Session::flash($type, $message);
    Router::redirect($url);
}

/**
 * Retorna dados JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Verifica se a requisição é POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Verifica se a requisição é GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Retorna um valor de POST ou valor padrão
 */
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Retorna um valor de GET ou valor padrão
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}
