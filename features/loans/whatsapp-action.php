<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';
require_once __DIR__ . '/../whatsapp/template-service.php';

Session::requireAuth();

$loanService = new LoanService();
$templateService = new WhatsAppTemplateService();
$userId = Session::get('user_id');

$loanId = intval($_GET['loan_id'] ?? 0);
$installmentId = intval($_GET['installment_id'] ?? 0);
$templateType = $_GET['template'] ?? 'cobranca';

// Validações
if ($loanId <= 0) {
    Session::setFlash('error', 'Empréstimo inválido');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

// Buscar empréstimo
$loan = $loanService->getLoanById($loanId, $userId);
if (!$loan) {
    Session::setFlash('error', 'Empréstimo não encontrado');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

// Buscar parcela se especificada
$installment = null;
if ($installmentId > 0) {
    $stmt = Database::getInstance()->getConnection()->prepare("
        SELECT * FROM loan_installments WHERE id = ? AND loan_id = ?
    ");
    $stmt->execute([$installmentId, $loanId]);
    $installment = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar template ativo da categoria
$templates = $templateService->getAllTemplates(['category' => $templateType, 'is_active' => 1]);
if (empty($templates)) {
    Session::setFlash('error', 'Nenhum template de WhatsApp encontrado para esta categoria');
    header('Location: ' . BASE_URL . '/loans/' . $loanId);
    exit;
}

$template = $templates[0]; // Usar o primeiro template ativo da categoria

// Preparar dados para substituição
$data = [
    'cliente' => $loan['client_name'],
    'numero_parcela' => $installment ? $installment['installment_number'] : '',
    'total_parcelas' => $loan['installments_count'],
    'valor' => $installment ? $installment['amount'] : $loan['total_amount'],
    'vencimento' => $installment ? date('d/m/Y', strtotime($installment['due_date'])) : '',
    'data_pagamento' => date('d/m/Y'),
    'total_pago' => $loan['total_amount']
];

// Substituir variáveis no template
$message = $templateService->replaceVariables($template['message'], $data);

// Preparar número de telefone (remover caracteres especiais)
$phone = preg_replace('/\D/', '', $loan['client_phone']);

// Se não tiver código do país, adicionar 55 (Brasil)
if (strlen($phone) <= 11) {
    $phone = '55' . $phone;
}

// Gerar URL do WhatsApp
$whatsappURL = 'https://wa.me/' . $phone . '?text=' . urlencode($message);

// Registrar log (opcional - você pode criar uma tabela de logs depois)
error_log("WhatsApp enviado para {$loan['client_name']} - Empréstimo #{$loanId}");

// Redirecionar para WhatsApp
header('Location: ' . $whatsappURL);
exit;
