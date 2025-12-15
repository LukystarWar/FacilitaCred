<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../features/wallets/wallet-service.php';
require_once __DIR__ . '/../../features/loans/loan-service.php';

Session::requireAuth();

$userId = Session::get('user_id');
$walletService = new WalletService();
$loanService = new LoanService();

// Período: mês atual
$currentMonth = date('Y-m-01'); // Primeiro dia do mês
$currentMonthEnd = date('Y-m-t'); // Último dia do mês
$monthName = date('F/Y'); // Ex: December/2025

$totalCarteiras = $walletService->getTotalBalance($userId);
$wallets = $walletService->getAllWallets($userId);

// Buscar TODOS os empréstimos para estatísticas gerais (atemporais)
$allLoansResult = $loanService->getAllLoans($userId, [], 1, 10000);
$allLoans = $allLoansResult['data'];

// Calcular total emprestado (todos os empréstimos ativos)
$totalEmprestado = array_sum(array_map(function($l) {
    return $l['status'] === 'active' ? $l['amount'] : 0;
}, $allLoans));

// Calcular total a receber (todos os empréstimos ativos)
$totalReceber = array_sum(array_map(function($l) {
    if ($l['status'] !== 'active') return 0;
    $total = $l['total_amount'];
    $perInstallment = $total / $l['total_installments'];
    $remaining = $total - ($l['paid_installments'] * $perInstallment);
    return $remaining;
}, $allLoans));

// Parcelas próximas (próximo 1 dia)
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT i.*, l.client_id, c.name as client_name, l.id as loan_id
    FROM loan_installments i
    INNER JOIN loans l ON i.loan_id = l.id
    INNER JOIN clients c ON l.client_id = c.id
    WHERE i.status = 'pending'
      AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ORDER BY i.due_date ASC
    LIMIT 5
");
$stmt->execute();
$upcomingInstallments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parcelas atrasadas
$stmt = $db->prepare("
    SELECT i.*, l.client_id, c.name as client_name, l.id as loan_id
    FROM loan_installments i
    INNER JOIN loans l ON i.loan_id = l.id
    INNER JOIN clients c ON l.client_id = c.id
    WHERE i.status IN ('pending', 'overdue')
      AND i.due_date < CURDATE()
    ORDER BY i.due_date ASC
    LIMIT 5
");
$stmt->execute();
$overdueInstallments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="page-subtitle" style="color: #6b7280; margin-top: 0.25rem;">
            Visão geral do seu negócio
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/wallets" class="btn btn-secondary">Carteiras</a>
        <a href="<?= BASE_URL ?>/loans/create" class="btn btn-primary">+ Novo Empréstimo</a>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #11C76F;">R$ <?= number_format($totalCarteiras, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Saldo Disponível</div>
        <small style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Total em carteiras</small>
    </div>

    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($totalEmprestado, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Capital Emprestado</div>
        <small style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Empréstimos ativos</small>
    </div>

    <div class="stat-card" style="border-left: 4px solid #0D9488;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($totalReceber, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">A Receber</div>
        <small style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Pendente com juros</small>
    </div>

    <div class="stat-card" style="border-left: 4px solid #DC2626;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count(array_filter($allLoans, fn($l) => $l['status'] === 'active' && $l['overdue_installments'] > 0)) ?></div>
        <div class="stat-label" style="color: #6b7280;">Com Atraso</div>
        <small style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Empréstimos atrasados</small>
    </div>
</div>

<div class="grid-2">
    <?php if (!empty($overdueInstallments)): ?>
        <div class="card">
            <div class="card-header">
                <h2 style="color: #e74c3c;">🚨 Parcelas Atrasadas</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueInstallments as $inst): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($inst['client_name']) ?></strong>
                                    <br><small class="text-muted">Parcela #<?= $inst['installment_number'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($inst['due_date'])) ?>
                                    <br><small style="color: #e74c3c;">
                                        <?php
                                        $diff = (new DateTime())->diff(new DateTime($inst['due_date']));
                                        echo $diff->days . ' dias de atraso';
                                        ?>
                                    </small>
                                </td>
                                <td><strong>R$ <?= number_format($inst['amount'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/loans/<?= $inst['loan_id'] ?>" class="btn btn-sm btn-outline">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📅 Próximos Vencimentos (1 dia)</h2>
        </div>
        <?php if (empty($upcomingInstallments)): ?>
            <div style="padding: 2rem; text-align: center; color: #95a5a6;">
                Nenhuma parcela vencendo no próximo dia.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingInstallments as $inst): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($inst['client_name']) ?></strong>
                                    <br><small class="text-muted">Parcela #<?= $inst['installment_number'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($inst['due_date'])) ?>
                                    <br><small class="text-muted">
                                        <?php
                                        $diff = (new DateTime($inst['due_date']))->diff(new DateTime());
                                        echo 'Em ' . $diff->days . ' dias';
                                        ?>
                                    </small>
                                </td>
                                <td><strong>R$ <?= number_format($inst['amount'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/loans/<?= $inst['loan_id'] ?>" class="btn btn-sm btn-outline">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($wallets)): ?>
    <div class="card">
        <div class="card-header">
            <h2>🚀 Comece Agora</h2>
        </div>
        <div style="padding: 2rem;">
            <h3 style="margin-top: 0;">Primeiros Passos:</h3>
            <ol style="line-height: 2;">
                <li><strong>Criar Carteiras:</strong> <a href="<?= BASE_URL ?>/wallets">Acesse aqui</a> para criar suas carteiras</li>
                <li><strong>Cadastrar Clientes:</strong> <a href="<?= BASE_URL ?>/clients">Adicione seus clientes</a></li>
                <li><strong>Registrar Empréstimos:</strong> <a href="<?= BASE_URL ?>/loans/create">Crie seu primeiro empréstimo</a></li>
            </ol>
        </div>
    </div>
<?php endif; ?>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
