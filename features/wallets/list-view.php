<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

$walletService = new WalletService();
$wallets = $walletService->getAllWallets(Session::get('user_id'));
$totalBalance = $walletService->getTotalBalance(Session::get('user_id'));

$pageTitle = 'Carteiras';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Carteiras</h1>
    <a href="<?= BASE_URL ?>/wallets/create" class="btn btn-primary">
        + Nova Carteira
    </a>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($totalBalance, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Saldo Total</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count($wallets) ?></div>
        <div class="stat-label" style="color: #6b7280;">Carteiras Ativas</div>
    </div>
</div>

<?php if (empty($wallets)): ?>
    <div class="empty-state">
        <div class="empty-icon">💰</div>
        <h3>Nenhuma carteira cadastrada</h3>
        <p>Comece criando sua primeira carteira para gerenciar seus empréstimos.</p>
        <a href="<?= BASE_URL ?>/wallets/create" class="btn btn-primary">
            + Criar Primeira Carteira
        </a>
    </div>
<?php else: ?>
    <div class="wallets-grid">
        <?php foreach ($wallets as $wallet): ?>
            <div class="wallet-card">
                <div class="wallet-header">
                    <h3><?= htmlspecialchars($wallet['name']) ?></h3>
                    <div class="wallet-actions">
                        <a href="<?= BASE_URL ?>/wallets/edit?id=<?= $wallet['id'] ?>" class="btn-icon" title="Editar">
                            ✏️
                        </a>
                        <a href="<?= BASE_URL ?>/wallets/delete?id=<?= $wallet['id'] ?>"
                           class="btn-icon"
                           title="Excluir"
                           onclick="return confirm('Tem certeza que deseja excluir a carteira \'<?= htmlspecialchars($wallet['name']) ?>\'?\n\nATENÇÃO: Só é possível excluir carteiras sem transações.')">
                            🗑️
                        </a>
                    </div>
                </div>

                <div class="wallet-balance">
                    <div class="balance-label">Saldo Disponível</div>
                    <div class="balance-value">R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></div>
                </div>

                <div class="wallet-info">
                    <span>📊 <?= $wallet['transaction_count'] ?> transações</span>
                    <span>📅 <?= date('d/m/Y', strtotime($wallet['created_at'])) ?></span>
                </div>

                <div class="wallet-footer">
                    <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn btn-sm btn-outline">
                        Ver Detalhes
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.wallets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.wallet-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.wallet-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.wallet-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #2c3e50;
}

.wallet-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
    text-decoration: none;
}

.btn-icon:hover {
    opacity: 1;
}

.wallet-balance {
    background: linear-gradient(135deg, #11C76F 0%, #0E9F59 100%);
    color: white;
    padding: 1.25rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.balance-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.balance-value {
    font-size: 1.75rem;
    font-weight: bold;
}

.wallet-info {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-top: 1px solid #ecf0f1;
    border-bottom: 1px solid #ecf0f1;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.wallet-footer {
    display: flex;
    gap: 0.75rem;
}

.wallet-footer .btn {
    flex: 1;
    text-align: center;
}

@media (max-width: 768px) {
    .wallets-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
