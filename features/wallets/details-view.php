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

// Capturar filtros
$filters = [
    'type' => $_GET['type'] ?? '',
    'search' => $_GET['search'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? ''
];

// Capturar página atual
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

// Buscar transações com filtros e paginação
$result = $walletService->getTransactions($walletId, $userId, 1000, $filters, $page, $perPage);
$transactions = $result['data'];
$pagination = $result['pagination'];
$totalTransactions = $pagination['total'];

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
        <div class="stat-value" style="color: #1C1C1C;"><?= $totalTransactions ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Transações</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= date('d/m/Y', strtotime($wallet['created_at'])) ?></div>
        <div class="stat-label" style="color: #6b7280;">Criada em</div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">🔍 Filtros</h3>
    </div>
    <form method="GET" action="<?= BASE_URL ?>/wallets/details" style="padding: 1.5rem;">
        <input type="hidden" name="id" value="<?= $walletId ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Tipo</label>
                <select name="type" class="form-control">
                    <option value="">Todos</option>
                    <option value="deposit" <?= $filters['type'] === 'deposit' ? 'selected' : '' ?>>Depósito</option>
                    <option value="withdrawal" <?= $filters['type'] === 'withdrawal' ? 'selected' : '' ?>>Retirada</option>
                    <option value="transfer_in" <?= $filters['type'] === 'transfer_in' ? 'selected' : '' ?>>Transferência Recebida</option>
                    <option value="transfer_out" <?= $filters['type'] === 'transfer_out' ? 'selected' : '' ?>>Transferência Enviada</option>
                    <option value="loan_out" <?= $filters['type'] === 'loan_out' ? 'selected' : '' ?>>Empréstimo</option>
                    <option value="loan_payment" <?= $filters['type'] === 'loan_payment' ? 'selected' : '' ?>>Pagamento</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                       placeholder="Descrição ou cliente" class="form-control">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Data Inicial</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>" class="form-control">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Data Final</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>" class="form-control">
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= BASE_URL ?>/wallets/details?id=<?= $walletId ?>" class="btn btn-secondary">Limpar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Histórico de Transações</h2>
        <span style="color: #6b7280; font-size: 0.875rem;">
            Mostrando <?= count($transactions) ?> de <?= $pagination['total'] ?> transações
        </span>
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
                            <td>
                                <?= htmlspecialchars($transaction['description']) ?>
                                <?php if (!empty($transaction['client_name'])): ?>
                                    <br><small class="text-muted">Cliente: <strong><?= htmlspecialchars($transaction['client_name']) ?></strong></small>
                                <?php endif; ?>
                            </td>
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

        <?php if ($pagination['total_pages'] > 1): ?>
        <!-- Paginação -->
        <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #6b7280; font-size: 0.875rem;">
                Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php
                // Construir URL com filtros
                $queryParams = array_merge(['id' => $walletId], array_filter($filters));
                $buildUrl = function($page) use ($queryParams) {
                    $params = array_merge($queryParams, ['page' => $page]);
                    return BASE_URL . '/wallets/details?' . http_build_query($params);
                };
                ?>

                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= $buildUrl(1) ?>" class="btn btn-sm btn-outline">« Primeira</a>
                    <a href="<?= $buildUrl($pagination['current_page'] - 1) ?>" class="btn btn-sm btn-outline">‹ Anterior</a>
                <?php endif; ?>

                <?php
                // Mostrar páginas próximas
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                    if ($i == $pagination['current_page']):
                ?>
                    <span class="btn btn-sm btn-primary"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $buildUrl($i) ?>" class="btn btn-sm btn-outline"><?= $i ?></a>
                <?php
                    endif;
                endfor;
                ?>

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="<?= $buildUrl($pagination['current_page'] + 1) ?>" class="btn btn-sm btn-outline">Próxima ›</a>
                    <a href="<?= $buildUrl($pagination['total_pages']) ?>" class="btn btn-sm btn-outline">Última »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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
    background: #28a745;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-warning {
    background: #ffc107;
    color: #212529;
}

.badge-info {
    background: #17a2b8;
    color: white;
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
