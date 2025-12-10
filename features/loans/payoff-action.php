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

$loanId = intval($_POST['loan_id'] ?? 0);
$walletId = intval($_POST['wallet_id'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? '');
$adjustmentAmount = floatval($_POST['adjustment_amount'] ?? 0);
$finalAmount = floatval($_POST['final_amount'] ?? 0);
$adjustmentReason = trim($_POST['adjustment_reason'] ?? '');

// Validações
if ($loanId <= 0) {
    Session::setFlash('error', 'Empréstimo inválido');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

if ($walletId <= 0) {
    Session::setFlash('error', 'Por favor, selecione uma carteira');
    header('Location: ' . BASE_URL . '/loans/payoff?id=' . $loanId);
    exit;
}

if (empty($paymentMethod)) {
    Session::setFlash('error', 'Por favor, selecione a forma de pagamento');
    header('Location: ' . BASE_URL . '/loans/payoff?id=' . $loanId);
    exit;
}

// Verificar se empréstimo existe e está ativo
$loan = $loanService->getLoanById($loanId, $userId);
if (!$loan) {
    Session::setFlash('error', 'Empréstimo não encontrado');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

if ($loan['status'] === 'paid') {
    Session::setFlash('error', 'Este empréstimo já foi quitado');
    header('Location: ' . BASE_URL . '/loans/' . $loanId);
    exit;
}

// Processar quitação
$result = $loanService->payoffLoan($loanId, $walletId, $finalAmount, $adjustmentAmount, $adjustmentReason, $paymentMethod, $userId);

if ($result['success']) {
    Session::setFlash('success', '✅ Empréstimo quitado com sucesso!');
    header('Location: ' . BASE_URL . '/loans/' . $loanId);
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao quitar empréstimo. Tente novamente.');
    header('Location: ' . BASE_URL . '/loans/payoff?id=' . $loanId);
}
exit;
