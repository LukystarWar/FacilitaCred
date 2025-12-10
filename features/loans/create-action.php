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

$clientId = intval($_POST['client_id'] ?? 0);
$walletId = intval($_POST['wallet_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$installmentsCount = intval($_POST['installments_count'] ?? 1);

if ($clientId <= 0) {
    Session::setFlash('error', 'Selecione um cliente');
    header('Location: ' . BASE_URL . '/loans/create');
    exit;
}

if ($walletId <= 0) {
    Session::setFlash('error', 'Selecione uma carteira');
    header('Location: ' . BASE_URL . '/loans/create');
    exit;
}

if ($amount <= 0) {
    Session::setFlash('error', 'O valor deve ser maior que zero');
    header('Location: ' . BASE_URL . '/loans/create');
    exit;
}

if ($installmentsCount < 1 || $installmentsCount > 12) {
    Session::setFlash('error', 'Número de parcelas inválido');
    header('Location: ' . BASE_URL . '/loans/create');
    exit;
}

$result = $loanService->createLoan($userId, $clientId, $walletId, $amount, $installmentsCount);

if ($result['success']) {
    Session::setFlash('success', 'Empréstimo criado com sucesso!');
    header('Location: ' . BASE_URL . '/loans/' . $result['id']);
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao criar empréstimo');
    header('Location: ' . BASE_URL . '/loans/create');
}
exit;
