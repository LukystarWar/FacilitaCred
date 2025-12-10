<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

$walletService = new WalletService();
$userId = Session::get('user_id');
$walletId = intval($_GET['wallet_id'] ?? 0);

if ($walletId <= 0) {
    Session::setFlash('error', 'Carteira inválida');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

$wallet = $walletService->getWalletById($walletId, $userId);

if (!$wallet) {
    Session::setFlash('error', 'Carteira não encontrada');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Buscar outras carteiras para transferência
$allWallets = $walletService->getAllWallets($userId);
$otherWallets = array_filter($allWallets, fn($w) => $w['id'] != $walletId);

if (empty($otherWallets)) {
    Session::setFlash('warning', 'Você precisa ter pelo menos 2 carteiras para fazer transferências');
    header('Location: ' . BASE_URL . '/wallets/' . $walletId);
    exit;
}

$pageTitle = 'Transferir Saldo';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn-back">← Voltar</a>
        <h1>Transferir Saldo</h1>
        <p class="page-subtitle">De: <?= htmlspecialchars($wallet['name']) ?> - Saldo: R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></p>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/wallets/transfer">
        <input type="hidden" name="from_wallet_id" value="<?= $wallet['id'] ?>">

        <div class="form-group">
            <label for="to_wallet_id">Transferir Para *</label>
            <select id="to_wallet_id" name="to_wallet_id" required autofocus>
                <option value="">Selecione a carteira de destino...</option>
                <?php foreach ($otherWallets as $otherWallet): ?>
                    <option value="<?= $otherWallet['id'] ?>">
                        <?= htmlspecialchars($otherWallet['name']) ?>
                        - Saldo: R$ <?= number_format($otherWallet['balance'], 2, ',', '.') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Valor da Transferência *</label>
            <input type="number"
                   id="amount"
                   name="amount"
                   step="0.01"
                   min="0.01"
                   max="<?= $wallet['balance'] ?>"
                   required
                   placeholder="0,00">
            <small>Saldo disponível: R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></small>
        </div>

        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3" placeholder="Motivo da transferência (opcional)"></textarea>
        </div>

        <div class="alert alert-info">
            <strong>ℹ️ Atenção:</strong> A transferência será registrada nas duas carteiras - uma saída na origem e uma entrada no destino.
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Confirmar Transferência</button>
        </div>
    </form>
</div>

<style>
.page-subtitle {
    color: #7f8c8d;
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
