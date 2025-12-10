<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

$userId = Session::get('user_id');
$db = Database::getInstance()->getConnection();

// Validar campos
$fromWalletId = intval($_POST['from_wallet_id'] ?? 0);
$toWalletId = intval($_POST['to_wallet_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($fromWalletId <= 0 || $toWalletId <= 0) {
    Session::setFlash('error', 'Carteiras inválidas');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

if ($fromWalletId === $toWalletId) {
    Session::setFlash('error', 'Não é possível transferir para a mesma carteira');
    header('Location: ' . BASE_URL . '/wallets/transfer?wallet_id=' . $fromWalletId);
    exit;
}

if ($amount <= 0) {
    Session::setFlash('error', 'O valor deve ser maior que zero');
    header('Location: ' . BASE_URL . '/wallets/transfer?wallet_id=' . $fromWalletId);
    exit;
}

try {
    $db->beginTransaction();

    // Buscar carteira de origem
    $stmt = $db->prepare("SELECT * FROM wallets WHERE id = :id");
    $stmt->execute(['id' => $fromWalletId]);
    $fromWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fromWallet) {
        throw new Exception('Carteira de origem não encontrada');
    }

    // Verificar saldo
    if ($fromWallet['balance'] < $amount) {
        throw new Exception('Saldo insuficiente na carteira de origem');
    }

    // Buscar carteira de destino
    $stmt = $db->prepare("SELECT * FROM wallets WHERE id = :id");
    $stmt->execute(['id' => $toWalletId]);
    $toWallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$toWallet) {
        throw new Exception('Carteira de destino não encontrada');
    }

    // Debitar da carteira de origem
    $stmt = $db->prepare("
        UPDATE wallets
        SET balance = balance - :amount, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $fromWalletId,
        'amount' => $amount
    ]);

    // Creditar na carteira de destino
    $stmt = $db->prepare("
        UPDATE wallets
        SET balance = balance + :amount, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $toWalletId,
        'amount' => $amount
    ]);

    // Descrição padrão se não fornecida
    if (empty($description)) {
        $description = "Transferência para {$toWallet['name']}";
    }

    // Registrar transação de saída
    $stmt = $db->prepare("
        INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id, related_wallet_id, created_at)
        VALUES (:wallet_id, 'transfer_out', :amount, :description, 'transfer', :to_wallet_id, :related_wallet_id, NOW())
    ");
    $stmt->execute([
        'wallet_id' => $fromWalletId,
        'amount' => $amount,
        'description' => $description,
        'to_wallet_id' => $toWalletId,
        'related_wallet_id' => $toWalletId
    ]);

    // Registrar transação de entrada
    $stmt = $db->prepare("
        INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id, related_wallet_id, created_at)
        VALUES (:wallet_id, 'transfer_in', :amount, :description, 'transfer', :from_wallet_id, :related_wallet_id, NOW())
    ");
    $stmt->execute([
        'wallet_id' => $toWalletId,
        'amount' => $amount,
        'description' => "Transferência de {$fromWallet['name']}" . ($description ? " - $description" : ""),
        'from_wallet_id' => $fromWalletId,
        'related_wallet_id' => $fromWalletId
    ]);

    $db->commit();

    Session::setFlash('success', 'Transferência realizada com sucesso!');
    header('Location: ' . BASE_URL . '/wallets/' . $fromWalletId);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log("Erro na transferência: " . $e->getMessage());
    Session::setFlash('error', $e->getMessage());
    header('Location: ' . BASE_URL . '/wallets/transfer?wallet_id=' . $fromWalletId);
    exit;
}
