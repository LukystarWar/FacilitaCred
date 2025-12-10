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
    <button class="btn btn-primary" onclick="openCreateModal()">
        + Nova Carteira
    </button>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-value">R$ <?= number_format($totalBalance, 2, ',', '.') ?></div>
        <div class="stat-label">Saldo Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($wallets) ?></div>
        <div class="stat-label">Carteiras Ativas</div>
    </div>
</div>

<?php if (empty($wallets)): ?>
    <div class="empty-state">
        <div class="empty-icon">💰</div>
        <h3>Nenhuma carteira cadastrada</h3>
        <p>Comece criando sua primeira carteira para gerenciar seus empréstimos.</p>
        <button class="btn btn-primary" onclick="openCreateModal()">
            + Criar Primeira Carteira
        </button>
    </div>
<?php else: ?>
    <div class="wallets-grid">
        <?php foreach ($wallets as $wallet): ?>
            <div class="wallet-card">
                <div class="wallet-header">
                    <h3><?= htmlspecialchars($wallet['name']) ?></h3>
                    <div class="wallet-actions">
                        <button class="btn-icon" onclick="openEditModal(<?= $wallet['id'] ?>, '<?= htmlspecialchars($wallet['name']) ?>', '<?= htmlspecialchars($wallet['description']) ?>')" title="Editar">
                            ✏️
                        </button>
                        <button class="btn-icon" onclick="confirmDelete(<?= $wallet['id'] ?>, '<?= htmlspecialchars($wallet['name']) ?>')" title="Excluir">
                            🗑️
                        </button>
                    </div>
                </div>

                <?php if ($wallet['description']): ?>
                    <p class="wallet-description"><?= htmlspecialchars($wallet['description']) ?></p>
                <?php endif; ?>

                <div class="wallet-balance">
                    <div class="balance-label">Saldo Disponível</div>
                    <div class="balance-value">R$ <?= number_format($wallet['balance'], 2, ',', '.') ?></div>
                </div>

                <div class="wallet-info">
                    <span>📊 <?= $wallet['transaction_count'] ?> transações</span>
                    <span>📅 <?= date('d/m/Y', strtotime($wallet['created_at'])) ?></span>
                </div>

                <div class="wallet-footer">
                    <button class="btn btn-sm btn-secondary" onclick="openTransactionModal(<?= $wallet['id'] ?>, '<?= htmlspecialchars($wallet['name']) ?>')">
                        💸 Movimentar
                    </button>
                    <a href="<?= BASE_URL ?>/wallets/<?= $wallet['id'] ?>" class="btn btn-sm btn-outline">
                        Ver Detalhes
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Criar Carteira -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nova Carteira</h2>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form id="createForm" method="POST" action="<?= BASE_URL ?>/wallets/create">
            <div class="form-group">
                <label for="name">Nome da Carteira *</label>
                <input type="text" id="name" name="name" required placeholder="Ex: Caixa Principal">
            </div>

            <div class="form-group">
                <label for="initial_balance">Saldo Inicial</label>
                <input type="number" id="initial_balance" name="initial_balance" step="0.01" min="0" value="0" placeholder="0,00">
                <small>Deixe zero se não houver saldo inicial</small>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" rows="3" placeholder="Descrição opcional da carteira"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Carteira</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Carteira -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Carteira</h2>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm" method="POST">
            <input type="hidden" id="edit_id" name="id">

            <div class="form-group">
                <label for="edit_name">Nome da Carteira *</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_description">Descrição</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Movimentar Saldo -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Movimentar Saldo</h2>
            <button class="modal-close" onclick="closeModal('transactionModal')">&times;</button>
        </div>
        <form id="transactionForm" method="POST">
            <input type="hidden" id="transaction_wallet_id" name="wallet_id">
            <input type="hidden" id="transaction_type" name="type">

            <div class="form-group">
                <label>Carteira</label>
                <input type="text" id="transaction_wallet_name" readonly class="readonly">
            </div>

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
                <label for="transaction_description">Descrição</label>
                <textarea id="transaction_description" name="description" rows="2" placeholder="Motivo da movimentação"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('transactionModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar Movimentação</button>
            </div>
        </form>
    </div>
</div>

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
}

.btn-icon:hover {
    opacity: 1;
}

.wallet-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin: 0 0 1rem 0;
}

.wallet-balance {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.readonly {
    background: #f5f5f5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .wallets-grid {
        grid-template-columns: 1fr;
    }

    .radio-group {
        flex-direction: column;
    }
}
</style>

<script>
function openCreateModal() {
    document.getElementById('createForm').reset();
    openModal('createModal');
}

function openEditModal(id, name, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editForm').action = '<?= BASE_URL ?>/wallets/update';
    openModal('editModal');
}

function openTransactionModal(walletId, walletName) {
    document.getElementById('transactionForm').reset();
    document.getElementById('transaction_wallet_id').value = walletId;
    document.getElementById('transaction_wallet_name').value = walletName;
    document.getElementById('transaction_type').value = 'credit';
    document.getElementById('transactionForm').action = '<?= BASE_URL ?>/wallets/transaction';
    openModal('transactionModal');
}

function confirmDelete(id, name) {
    if (confirm(`Tem certeza que deseja excluir a carteira "${name}"?\n\nATENÇÃO: Só é possível excluir carteiras sem transações.`)) {
        window.location.href = '<?= BASE_URL ?>/wallets/delete?id=' + id;
    }
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
