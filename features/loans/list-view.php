<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';

Session::requireAuth();

$loanService = new LoanService();
$loanService->updateOverdueInstallments();
$loans = $loanService->getAllLoans(Session::get('user_id'));

$pageTitle = 'Empréstimos';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Empréstimos</h1>
    <a href="<?= BASE_URL ?>/loans/create" class="btn btn-primary">
        + Novo Empréstimo
    </a>
</div>

<?php
$totalEmprestado = array_sum(array_column($loans, 'amount'));
$totalReceber = array_sum(array_map(function($l) {
    return $l['status'] === 'active' ? $l['total_amount'] * (($l['total_installments'] - $l['paid_installments']) / $l['total_installments']) : 0;
}, $loans));
$totalJuros = array_sum(array_column($loans, 'interest_amount'));
$ativos = count(array_filter($loans, fn($l) => $l['status'] === 'active'));
?>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-value"><?= count($loans) ?></div>
        <div class="stat-label">Total de Empréstimos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $ativos ?></div>
        <div class="stat-label">Empréstimos Ativos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">R$ <?= number_format($totalEmprestado, 2, ',', '.') ?></div>
        <div class="stat-label">Total Emprestado</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">R$ <?= number_format($totalReceber, 2, ',', '.') ?></div>
        <div class="stat-label">A Receber</div>
    </div>
</div>

<?php if (empty($loans)): ?>
    <div class="empty-state">
        <div class="empty-icon">💵</div>
        <h3>Nenhum empréstimo registrado</h3>
        <p>Comece criando seu primeiro empréstimo.</p>
        <a href="<?= BASE_URL ?>/loans/create" class="btn btn-primary">
            + Criar Primeiro Empréstimo
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Lista de Empréstimos</h2>
            <input type="text" id="searchInput" placeholder="🔍 Buscar..." style="max-width: 300px;" oninput="filterLoans()">
        </div>

        <div class="table-responsive">
            <table class="table" id="loansTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Carteira</th>
                        <th>Valor</th>
                        <th>Total + Juros</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($loan['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($loan['client_name']) ?></strong>
                                <?php if ($loan['overdue_installments'] > 0): ?>
                                    <br><span class="badge badge-danger"><?= $loan['overdue_installments'] ?> em atraso</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($loan['wallet_name']) ?></td>
                            <td>R$ <?= number_format($loan['amount'], 2, ',', '.') ?></td>
                            <td>
                                <strong>R$ <?= number_format($loan['total_amount'], 2, ',', '.') ?></strong>
                                <br><small class="text-muted"><?= number_format($loan['interest_rate'], 0) ?>% juros</small>
                            </td>
                            <td>
                                <?= $loan['paid_installments'] ?>/<?= $loan['total_installments'] ?>
                                <br><small class="text-muted">
                                    <?php
                                    $progress = $loan['total_installments'] > 0 ? ($loan['paid_installments'] / $loan['total_installments']) * 100 : 0;
                                    echo number_format($progress, 0) . '%';
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($loan['status'] === 'active'): ?>
                                    <?php if ($loan['overdue_installments'] > 0): ?>
                                        <span class="badge badge-danger">Com Atraso</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Ativo</span>
                                    <?php endif; ?>
                                <?php elseif ($loan['status'] === 'paid'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/loans/<?= $loan['id'] ?>" class="btn btn-sm btn-outline">
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function filterLoans() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('loansTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
