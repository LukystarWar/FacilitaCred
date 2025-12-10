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
$name = trim($_POST['name'] ?? '');
$initialBalance = floatval($_POST['initial_balance'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (empty($name)) {
    Session::setFlash('error', 'O nome da carteira é obrigatório');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

if ($initialBalance < 0) {
    Session::setFlash('error', 'O saldo inicial não pode ser negativo');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Criar a carteira
$result = $walletService->createWallet($userId, $name, $initialBalance, $description);

if ($result['success']) {
    Session::setFlash('success', 'Carteira criada com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao criar carteira');
}

header('Location: ' . BASE_URL . '/wallets');
exit;
