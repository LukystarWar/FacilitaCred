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
$typeFilter = $_GET['type'] ?? '';
$searchFilter = $_GET['search'] ?? '';
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

if (!empty($typeFilter)) {
    $sql .= " AND t.type = :type";
    $params['type'] = $typeFilter;
}

if (!empty($searchFilter)) {
    $sql .= " AND (c.name LIKE :search OR t.description LIKE :search)";
    $params['search'] = '%' . $searchFilter . '%';
}

// Contar total de registros para paginação
$countSql = "SELECT COUNT(*) FROM wallet_transactions t
    LEFT JOIN loans l ON ((t.type IN ('loan_out', 'loan_payment')) AND (t.description LIKE CONCAT('%#', l.id, '%')))
    LEFT JOIN clients c ON l.client_id = c.id
    WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date";
if ($walletFilter > 0) {
    $countSql .= " AND t.wallet_id = :wallet_id";
}
if (!empty($typeFilter)) {
    $countSql .= " AND t.type = :type";
}
if (!empty($searchFilter)) {
    $countSql .= " AND (c.name LIKE :search OR t.description LIKE :search)";
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

// Calcular lucro do período (juros de empréstimos pagos)
$lucroSql = "
    SELECT
        SUM(l.interest_amount) as total_lucro,
        COUNT(DISTINCT l.id) as total_emprestimos
    FROM loans l
    WHERE l.status = 'paid'
      AND DATE(l.updated_at) BETWEEN :start_date AND :end_date
";
$lucroParams = [
    'start_date' => $startDate,
    'end_date' => $endDate
];

if ($walletFilter > 0) {
    $lucroSql .= " AND l.wallet_id = :wallet_id";
    $lucroParams['wallet_id'] = $walletFilter;
}

$lucroStmt = $db->prepare($lucroSql);
$lucroStmt->execute($lucroParams);
$lucroData = $lucroStmt->fetch(PDO::FETCH_ASSOC);
$lucroTotal = $lucroData['total_lucro'] ?? 0;
$emprestimosQuitados = $lucroData['total_emprestimos'] ?? 0;

// Calcular estatísticas adicionais
require_once __DIR__ . '/../loans/loan-service.php';
$loanService = new LoanService();

// 1. Clientes com empréstimos ativos
$clientesAtivosSql = "
    SELECT COUNT(DISTINCT l.client_id) as total_clientes
    FROM loans l
    WHERE l.status = 'active'
";
$clientesAtivosParams = [];
if ($walletFilter > 0) {
    $clientesAtivosSql .= " AND l.wallet_id = :wallet_id";
    $clientesAtivosParams['wallet_id'] = $walletFilter;
}
$clientesAtivosStmt = $db->prepare($clientesAtivosSql);
$clientesAtivosStmt->execute($clientesAtivosParams);
$clientesAtivos = $clientesAtivosStmt->fetchColumn();

// 2. Valor total atrasado (com multas)
$atrasadoSql = "
    SELECT i.*, l.wallet_id
    FROM loan_installments i
    INNER JOIN loans l ON i.loan_id = l.id
    WHERE i.status = 'overdue'
";
$atrasadoParams = [];
if ($walletFilter > 0) {
    $atrasadoSql .= " AND l.wallet_id = :wallet_id";
    $atrasadoParams['wallet_id'] = $walletFilter;
}
$atrasadoStmt = $db->prepare($atrasadoSql);
$atrasadoStmt->execute($atrasadoParams);
$parcelasAtrasadas = $atrasadoStmt->fetchAll(PDO::FETCH_ASSOC);

$valorAtrasado = 0;
foreach ($parcelasAtrasadas as $parcela) {
    $lateFeeInfo = $loanService->calculateLateFee($parcela['amount'], $parcela['due_date']);
    $valorAtrasado += $lateFeeInfo['total_amount'];
}

// 3. Valor em dia (parcelas pendentes não atrasadas)
$emDiaSql = "
    SELECT SUM(i.amount) as total_em_dia
    FROM loan_installments i
    INNER JOIN loans l ON i.loan_id = l.id
    WHERE i.status = 'pending'
";
$emDiaParams = [];
if ($walletFilter > 0) {
    $emDiaSql .= " AND l.wallet_id = :wallet_id";
    $emDiaParams['wallet_id'] = $walletFilter;
}
$emDiaStmt = $db->prepare($emDiaSql);
$emDiaStmt->execute($emDiaParams);
$valorEmDia = $emDiaStmt->fetchColumn() ?? 0;

$pageTitle = 'Fluxo de Caixa';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Fluxo de Caixa</h1>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">🔍 Filtros</h3>
    </div>
    <form method="GET" action="<?= BASE_URL ?>/reports/cash-flow" style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label for="wallet_id">Carteira</label>
                <select id="wallet_id" name="wallet_id" class="form-control">
                    <option value="0">Todas</option>
                    <?php foreach ($wallets as $wallet): ?>
                        <option value="<?= $wallet['id'] ?>" <?= $wallet['id'] == $walletFilter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wallet['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="type">Tipo</label>
                <select id="type" name="type" class="form-control">
                    <option value="">Todos</option>
                    <option value="deposit" <?= $typeFilter === 'deposit' ? 'selected' : '' ?>>💰 Depósito</option>
                    <option value="withdrawal" <?= $typeFilter === 'withdrawal' ? 'selected' : '' ?>>💸 Retirada</option>
                    <option value="transfer_in" <?= $typeFilter === 'transfer_in' ? 'selected' : '' ?>>📥 Transferência Recebida</option>
                    <option value="transfer_out" <?= $typeFilter === 'transfer_out' ? 'selected' : '' ?>>📤 Transferência Enviada</option>
                    <option value="loan_out" <?= $typeFilter === 'loan_out' ? 'selected' : '' ?>>📤 Empréstimo</option>
                    <option value="loan_payment" <?= $typeFilter === 'loan_payment' ? 'selected' : '' ?>>📥 Pagamento</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="search">Buscar</label>
                <input type="text" id="search" name="search" class="form-control"
                       placeholder="Cliente ou descrição..." value="<?= htmlspecialchars($searchFilter) ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="start_date">Data Inicial</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="end_date">Data Final</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $endDate ?>">
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <?php if ($walletFilter > 0 || !empty($typeFilter) || !empty($searchFilter) || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-d')): ?>
                <a href="<?= BASE_URL ?>/reports/cash-flow" class="btn btn-secondary">Limpar Filtros</a>
            <?php endif; ?>
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

    <div class="stat-card" style="border-left: 4px solid #65A30D;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($lucroTotal, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">
            Lucro do Período (Juros)
            <?php if ($emprestimosQuitados > 0): ?>
                <br><small style="font-size: 0.75rem; font-weight: normal;"><?= $emprestimosQuitados ?> empréstimo<?= $emprestimosQuitados > 1 ? 's' : '' ?> quitado<?= $emprestimosQuitados > 1 ? 's' : '' ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cards de Empréstimos -->
<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem;">📊 Análise de Recebíveis</h3>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="stat-card" style="border-left: 4px solid #3B82F6;">
            <div class="stat-value" style="color: #1C1C1C;"><?= $clientesAtivos ?></div>
            <div class="stat-label" style="color: #6b7280;">Clientes com Empréstimos Ativos</div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #DC2626; position: relative;">
            <div style="position: absolute; top: 8px; right: 8px; background: #FEE2E2; color: #991B1B; font-size: 0.625rem; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">ATRASADO</div>
            <div class="stat-value" style="color: #DC2626;">R$ <?= number_format($valorAtrasado, 2, ',', '.') ?></div>
            <div class="stat-label" style="color: #6b7280;">
                Valor Total Atrasado
                <?php if (count($parcelasAtrasadas) > 0): ?>
                    <br><small style="font-size: 0.75rem; font-weight: normal;"><?= count($parcelasAtrasadas) ?> parcela<?= count($parcelasAtrasadas) > 1 ? 's' : '' ?> (com multas)</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #10B981; position: relative;">
            <div style="position: absolute; top: 8px; right: 8px; background: #D1FAE5; color: #065F46; font-size: 0.625rem; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">EM DIA</div>
            <div class="stat-value" style="color: #10B981;">R$ <?= number_format($valorEmDia, 2, ',', '.') ?></div>
            <div class="stat-label" style="color: #6b7280;">Valor Pendente Em Dia</div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #8B5CF6; background: linear-gradient(to bottom, #F5F3FF 0%, #FFFFFF 100%); position: relative;">
            <div style="position: absolute; top: 8px; right: 8px; background: #8B5CF6; color: white; font-size: 0.625rem; padding: 0.125rem 0.375rem; border-radius: 4px; font-weight: 600;">TOTAL</div>
            <div class="stat-value" style="color: #8B5CF6; font-size: 1.75rem;">R$ <?= number_format($valorAtrasado + $valorEmDia, 2, ',', '.') ?></div>
            <div class="stat-label" style="color: #6b7280;">
                Total a Receber (Ativos)
                <br><small style="font-size: 0.75rem; color: #9CA3AF; margin-top: 0.25rem; display: block;">
                    = <span style="color: #DC2626;">Atrasado</span> + <span style="color: #10B981;">Em Dia</span>
                </small>
            </div>
        </div>
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
                $buildUrl = function($p) use ($walletFilter, $startDate, $endDate, $typeFilter, $searchFilter) {
                    $params = ['page' => $p];
                    if ($walletFilter > 0) $params['wallet_id'] = $walletFilter;
                    if ($startDate) $params['start_date'] = $startDate;
                    if ($endDate) $params['end_date'] = $endDate;
                    if (!empty($typeFilter)) $params['type'] = $typeFilter;
                    if (!empty($searchFilter)) $params['search'] = $searchFilter;
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
