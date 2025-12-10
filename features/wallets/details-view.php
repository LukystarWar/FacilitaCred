<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

$walletService = new WalletService();
$userId = Session::get('user_id');

// Pegar ID da carteira da URL
$walletId = intval($_GET['id'] ?? 0);

if ($walletId <= 0) {
    Session::setFlash('error', 'Carteira inválida');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Buscar carteira
$wallet = $walletService->getWalletById($walletId, $userId);

if (!$wallet) {
    Session::setFlash('error', 'Carteira não encontrada');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

// Buscar transações
$transactions = $walletService->getTransactions($walletId, $userId, 100);

$pageTitle = 'Detalhes da Carteira';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/wallets" class="btn-back">← Voltar</a>
        <h1><?= htmlspecialchars($wallet['name']) ?></h1>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/wallets/transfer?wallet_id=<?= $wallet['id'] ?>" class="btn btn-secondary">
            ↔️ Transferir
        </a>
        <a href="<?= BASE_URL ?>/wallets/transaction?wallet_id=<?= $wallet['id'] ?>" class="btn btn-primary">
            + Movimentar
        </a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card stat-primary">
        <div class="stat-value">R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></div>
        <div class="stat-label">Saldo Atual</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count($transactions) ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Transações</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= date('d/m/Y', strtotime($wallet['created_at'])) ?></div>
        <div class="stat-label" style="color: #6b7280;">Criada em</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Histórico de Transações</h2>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3>Nenhuma transação registrada</h3>
            <p>Esta carteira ainda não possui movimentações.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <div><?= date('d/m/Y', strtotime($transaction['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i:s', strtotime($transaction['created_at'])) ?></small>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'deposit' => '<span class="badge badge-success">💰 Depósito</span>',
                                    'withdrawal' => '<span class="badge badge-danger">💸 Retirada</span>',
                                    'transfer_in' => '<span class="badge badge-info">📥 Transferência Recebida</span>',
                                    'transfer_out' => '<span class="badge badge-warning">📤 Transferência Enviada</span>',
                                    'loan_out' => '<span class="badge badge-warning">📤 Empréstimo</span>',
                                    'loan_payment' => '<span class="badge badge-success">📥 Pagamento</span>'
                                ];
                                echo $badges[$transaction['type']] ?? $transaction['type'];
                                ?>
                            </td>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td class="text-right">
                                <?php
                                $isIncome = in_array($transaction['type'], ['deposit', 'transfer_in', 'loan_payment']);
                                ?>
                                <span class="transaction-amount <?= $isIncome ? 'positive' : 'negative' ?>">
                                    <?= $isIncome ? '+' : '-' ?>
                                    R$ <?= number_format($transaction['amount'], 2, ',', '.') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-back {
    display: inline-block;
    color: #11C76F;
    text-decoration: none;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.btn-back:hover {
    text-decoration: underline;
}

.stat-primary {
    background: linear-gradient(135deg, #11C76F 0%, #0E9F59 100%);
    color: white;
}

.stat-primary .stat-value,
.stat-primary .stat-label {
    color: white;
}

.transaction-amount {
    font-weight: bold;
    font-size: 1.1rem;
}

.transaction-amount.positive {
    color: #27ae60;
}

.transaction-amount.negative {
    color: #e74c3c;
}

.text-muted {
    color: #95a5a6;
    font-size: 0.85rem;
}

.badge {
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
