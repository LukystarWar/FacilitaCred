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
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');

if ($id <= 0) {
    Session::setFlash('error', 'Carteira inválida');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

if (empty($name)) {
    Session::setFlash('error', 'O nome da carteira é obrigatório');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Verificar se a carteira existe e pertence ao usuário
$wallet = $walletService->getWalletById($id, $userId);
if (!$wallet) {
    Session::setFlash('error', 'Carteira não encontrada');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Atualizar a carteira
$result = $walletService->updateWallet($id, $userId, $name);

if ($result['success']) {
    Session::setFlash('success', 'Carteira atualizada com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao atualizar carteira');
}

header('Location: ' . BASE_URL . '/wallets');
exit;
