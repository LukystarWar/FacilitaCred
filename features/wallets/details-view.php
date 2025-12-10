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
        <?php if ($wallet['description']): ?>
            <p class="page-subtitle"><?= htmlspecialchars($wallet['description']) ?></p>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" onclick="openTransactionModal()">
        + Nova Movimentação
    </button>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card stat-primary">
        <div class="stat-value">R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></div>
        <div class="stat-label">Saldo Atual</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($transactions) ?></div>
        <div class="stat-label">Total de Transações</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= date('d/m/Y', strtotime($wallet['created_at'])) ?></div>
        <div class="stat-label">Criada em</div>
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
                                <?php if ($transaction['type'] === 'credit'): ?>
                                    <span class="badge badge-success">💰 Entrada</span>
                                <?php elseif ($transaction['type'] === 'debit'): ?>
                                    <span class="badge badge-danger">💸 Saída</span>
                                <?php elseif ($transaction['type'] === 'loan_disbursement'): ?>
                                    <span class="badge badge-warning">📤 Empréstimo</span>
                                <?php elseif ($transaction['type'] === 'payment_received'): ?>
                                    <span class="badge badge-info">📥 Pagamento</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td class="text-right">
                                <span class="transaction-amount <?= $transaction['type'] === 'credit' || $transaction['type'] === 'payment_received' ? 'positive' : 'negative' ?>">
                                    <?= $transaction['type'] === 'credit' || $transaction['type'] === 'payment_received' ? '+' : '-' ?>
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

<!-- Modal: Nova Movimentação -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nova Movimentação</h2>
            <button class="modal-close" onclick="closeModal('transactionModal')">&times;</button>
        </div>
        <form id="transactionForm" method="POST" action="<?= BASE_URL ?>/wallets/transaction">
            <input type="hidden" name="wallet_id" value="<?= $wallet['id'] ?>">
            <input type="hidden" name="redirect" value="details">
            <input type="hidden" id="transaction_type" name="type" value="credit">

            <div class="form-group">
                <label>Tipo de Movimentação</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="type_radio" value="credit" checked onchange="document.getElementById('transaction_type').value='credit'">
                        <span>💰 Adicionar Saldo</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="type_radio" value="debit" onchange="document.getElementById('transaction_type').value='debit'">
                        <span>💸 Retirar Saldo</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Valor *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0,00">
            </div>

            <div class="form-group">
                <label for="description">Descrição *</label>
                <textarea id="description" name="description" rows="3" required placeholder="Motivo da movimentação"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('transactionModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar Movimentação</button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-back {
    display: inline-block;
    color: #667eea;
    text-decoration: none;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.btn-back:hover {
    text-decoration: underline;
}

.page-subtitle {
    color: #7f8c8d;
    margin: 0.5rem 0 0 0;
}

.stat-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.radio-group {
    display: flex;
    gap: 1rem;
}

.radio-option {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.radio-option:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.radio-option input[type="radio"] {
    margin: 0;
}

.radio-option input[type="radio"]:checked + span {
    font-weight: bold;
    color: #667eea;
}

@media (max-width: 768px) {
    .radio-group {
        flex-direction: column;
    }
}
</style>

<script>
function openTransactionModal() {
    document.getElementById('transactionForm').reset();
    document.getElementById('transaction_type').value = 'credit';
    openModal('transactionModal');
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
