<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

$loanService = new LoanService();
$userId = Session::get('user_id');

$installmentId = intval($_POST['installment_id'] ?? 0);
$loanId = intval($_POST['loan_id'] ?? 0);

if ($installmentId <= 0) {
    Session::setFlash('error', 'Parcela inválida');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

$result = $loanService->payInstallment($installmentId, $userId);

if ($result['success']) {
    Session::setFlash('success', 'Pagamento registrado com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao registrar pagamento');
}

if ($loanId > 0) {
    header('Location: ' . BASE_URL . '/loans/' . $loanId);
} else {
    header('Location: ' . BASE_URL . '/loans');
}
exit;
