<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

$walletService = new WalletService();
$userId = Session::get('user_id');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    Session::setFlash('error', 'Carteira inválida');
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

// Tentar excluir a carteira
$result = $walletService->deleteWallet($id, $userId);

if ($result['success']) {
    Session::setFlash('success', 'Carteira excluída com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao excluir carteira');
}

header('Location: ' . BASE_URL . '/wallets');
exit;
