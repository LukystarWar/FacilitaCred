<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../features/wallets/wallet-service.php';

Session::requireAuth();

$userId = Session::get('user_id');
$db = Database::getInstance()->getConnection();
$walletService = new WalletService();

$walletFilter = intval($_GET['wallet_id'] ?? 0);
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$wallets = $walletService->getAllWallets($userId);

$sql = "
    SELECT
        t.*,
        w.name as wallet_name,
        c.name as client_name,
        l.id as loan_id
    FROM wallet_transactions t
    INNER JOIN wallets w ON t.wallet_id = w.id
    LEFT JOIN loans l ON (
        (t.type IN ('loan_out', 'loan_payment')) AND
        (t.description LIKE CONCAT('%#', l.id, '%'))
    )
    LEFT JOIN clients c ON l.client_id = c.id
    WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date
";

$params = [
    'start_date' => $startDate,
    'end_date' => $endDate
];

if ($walletFilter > 0) {
    $sql .= " AND t.wallet_id = :wallet_id";
    $params['wallet_id'] = $walletFilter;
}

// Contar total de registros para paginação
$countSql = "SELECT COUNT(*) FROM wallet_transactions t WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date";
if ($walletFilter > 0) {
    $countSql .= " AND t.wallet_id = :wallet_id";
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEntradas = array_sum(array_map(fn($t) => in_array($t['type'], ['deposit', 'transfer_in', 'loan_payment']) ? $t['amount'] : 0, $transactions));
$totalSaidas = array_sum(array_map(fn($t) => in_array($t['type'], ['withdrawal', 'transfer_out', 'loan_out']) ? $t['amount'] : 0, $transactions));
$saldo = $totalEntradas - $totalSaidas;

$pageTitle = 'Fluxo de Caixa';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Fluxo de Caixa</h1>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <form method="GET" action="<?= BASE_URL ?>/reports/cash-flow">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label for="wallet_id">Carteira</label>
                <select id="wallet_id" name="wallet_id">
                    <option value="0">Todas as carteiras</option>
                    <?php foreach ($wallets as $wallet): ?>
                        <option value="<?= $wallet['id'] ?>" <?= $wallet['id'] == $walletFilter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wallet['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="start_date">Data Inicial</label>
                <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="end_date">Data Final</label>
                <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>">
            </div>

            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    🔍 Filtrar
                </button>
            </div>
        </div>
    </form>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Entradas</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($totalSaidas, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Saídas</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #0D9488;">
        <div class="stat-value" style="color: <?= $saldo >= 0 ? '#1C1C1C' : '#DC2626' ?>;">
            R$ <?= number_format($saldo, 2, ',', '.') ?>
        </div>
        <div class="stat-label" style="color: #6b7280;">Saldo do Período</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count($transactions) ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Transações</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Movimentações</h2>
    </div>

    <?php if (empty($transactions)): ?>
        <div style="padding: 3rem; text-align: center; color: #95a5a6;">
            Nenhuma transação encontrada no período selecionado.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Carteira</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <?= date('d/m/Y', strtotime($transaction['created_at'])) ?>
                                <br><small class="text-muted"><?= date('H:i:s', strtotime($transaction['created_at'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($transaction['wallet_name']) ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'deposit' => '<span class="badge badge-success">💰 Depósito</span>',
                                    'withdrawal' => '<span class="badge badge-danger">💸 Retirada</span>',
                                    'transfer_in' => '<span class="badge badge-info">📥 Transferência Recebida</span>',
                                    'transfer_out' => '<span class="badge badge-warning">📤 Transferência Enviada</span>',
                                    'loan_out' => '<span class="badge badge-warning">📤 Empréstimo Concedido</span>',
                                    'loan_payment' => '<span class="badge badge-success">📥 Pagamento Recebido</span>'
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
                                <strong style="color: <?= in_array($transaction['type'], ['deposit', 'transfer_in', 'loan_payment']) ? '#10b981' : '#e74c3c' ?>">
                                    <?= in_array($transaction['type'], ['deposit', 'transfer_in', 'loan_payment']) ? '+' : '-' ?>
                                    R$ <?= number_format($transaction['amount'], 2, ',', '.') ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="4" class="text-right">TOTAL:</td>
                        <td class="text-right" style="color: <?= $saldo >= 0 ? '#10b981' : '#e74c3c' ?>">
                            R$ <?= number_format($saldo, 2, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <!-- Paginação -->
        <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #6b7280; font-size: 0.875rem;">
                Página <?= $page ?> de <?= $totalPages ?> (<?= $totalRecords ?> transações)
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php
                $buildUrl = function($p) use ($walletFilter, $startDate, $endDate) {
                    $params = ['page' => $p];
                    if ($walletFilter > 0) $params['wallet_id'] = $walletFilter;
                    if ($startDate) $params['start_date'] = $startDate;
                    if ($endDate) $params['end_date'] = $endDate;
                    return BASE_URL . '/reports/cash-flow?' . http_build_query($params);
                };
                ?>

                <?php if ($page > 1): ?>
                    <a href="<?= $buildUrl(1) ?>" class="btn btn-sm btn-outline">« Primeira</a>
                    <a href="<?= $buildUrl($page - 1) ?>" class="btn btn-sm btn-outline">‹ Anterior</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                    if ($i == $page):
                ?>
                    <span class="btn btn-sm btn-primary"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $buildUrl($i) ?>" class="btn btn-sm btn-outline"><?= $i ?></a>
                <?php
                    endif;
                endfor;
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $buildUrl($page + 1) ?>" class="btn btn-sm btn-outline">Próxima ›</a>
                    <a href="<?= $buildUrl($totalPages) ?>" class="btn btn-sm btn-outline">Última »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
