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

$pageTitle = 'Nova Movimentação';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn-back">← Voltar</a>
        <h1>Nova Movimentação</h1>
        <p class="page-subtitle">Carteira: <?= htmlspecialchars($wallet['name']) ?> - Saldo: R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></p>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/wallets/transaction">
        <input type="hidden" name="wallet_id" value="<?= $wallet['id'] ?>">
        <input type="hidden" name="redirect" value="details">

        <div class="form-group">
            <label>Tipo de Movimentação *</label>
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="type" value="credit" checked>
                    <div>
                        <div class="radio-title">💰 Adicionar Saldo</div>
                        <small>Depósito ou entrada de dinheiro</small>
                    </div>
                </label>
                <label class="radio-option">
                    <input type="radio" name="type" value="debit">
                    <div>
                        <div class="radio-title">💸 Retirar Saldo</div>
                        <small>Retirada ou saída de dinheiro</small>
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="amount">Valor *</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0,00" autofocus>
        </div>

        <div class="form-group">
            <label for="description">Descrição *</label>
            <textarea id="description" name="description" rows="3" required placeholder="Motivo da movimentação..."></textarea>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Confirmar Movimentação</button>
        </div>
    </form>
</div>

<style>
.page-subtitle {
    color: #7f8c8d;
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
}

.radio-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.radio-option:hover {
    border-color: #11C76F;
    background: #f8f9ff;
}

.radio-option input[type="radio"] {
    margin-top: 0.25rem;
}

.radio-option input[type="radio"]:checked {
    accent-color: #11C76F;
}

.radio-option:has(input[type="radio"]:checked) {
    border-color: #11C76F;
    background: #f8f9ff;
}

.radio-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.radio-option small {
    color: #7f8c8d;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .radio-group {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
