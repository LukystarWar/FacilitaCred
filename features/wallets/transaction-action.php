<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

$walletService = new WalletService();
$userId = Session::get('user_id');

// Validar campos obrigatórios
$walletId = intval($_POST['wallet_id'] ?? 0);
$type = $_POST['type'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($walletId <= 0) {
    Session::setFlash('error', 'Carteira inválida');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

if (!in_array($type, ['credit', 'debit'])) {
    Session::setFlash('error', 'Tipo de transação inválido');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

if ($amount <= 0) {
    Session::setFlash('error', 'O valor deve ser maior que zero');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Verificar se a carteira existe e pertence ao usuário
$wallet = $walletService->getWalletById($walletId, $userId);
if (!$wallet) {
    Session::setFlash('error', 'Carteira não encontrada');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Executar a transação
if ($type === 'credit') {
    $result = $walletService->addBalance($walletId, $userId, $amount, $description);
    $successMessage = 'Saldo adicionado com sucesso!';
} else {
    $result = $walletService->removeBalance($walletId, $userId, $amount, $description);
    $successMessage = 'Saldo removido com sucesso!';
}

if ($result['success']) {
    Session::setFlash('success', $successMessage);
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao processar transação');
}

// Redirecionar de volta
$redirect = $_POST['redirect'] ?? 'list';
if ($redirect === 'details') {
    header('Location: ' . BASE_URL . '/wallets/' . $walletId);
} else {
    header('Location: ' . BASE_URL . '/wallets');
}
exit;
