<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';

Session::requireAuth();

$userId = Session::get('user_id');
$db = Database::getInstance()->getConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $db->prepare("
    SELECT
        l.*,
        c.name as client_name,
        w.name as wallet_name,
        COUNT(DISTINCT i.id) as total_installments,
        COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.id END) as paid_installments
    FROM loans l
    INNER JOIN clients c ON l.client_id = c.id
    INNER JOIN wallets w ON l.wallet_id = w.id
    LEFT JOIN installments i ON l.id = i.loan_id
    WHERE l.user_id = :user_id
      AND DATE(l.created_at) BETWEEN :start_date AND :end_date
    GROUP BY l.id
    ORDER BY l.created_at DESC
");

$stmt->execute([
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate
]);

$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEmprestado = array_sum(array_column($loans, 'amount'));
$totalJuros = array_sum(array_column($loans, 'interest_amount'));
$totalComJuros = array_sum(array_column($loans, 'total_amount'));

$jurosRecebidos = 0;
foreach ($loans as $loan) {
    if ($loan['total_installments'] > 0) {
        $percentPaid = $loan['paid_installments'] / $loan['total_installments'];
        $jurosRecebidos += $loan['interest_amount'] * $percentPaid;
    }
}

$jurosPendentes = $totalJuros - $jurosRecebidos;

$stmt = $db->prepare("
    SELECT
        w.id,
        w.name,
        COUNT(DISTINCT l.id) as loan_count,
        COALESCE(SUM(l.amount), 0) as total_loaned,
        COALESCE(SUM(l.interest_amount), 0) as total_interest
    FROM wallets w
    LEFT JOIN loans l ON w.id = l.wallet_id AND DATE(l.created_at) BETWEEN :start_date AND :end_date
    WHERE w.user_id = :user_id
    GROUP BY w.id
    HAVING loan_count > 0
    ORDER BY total_interest DESC
");

$stmt->execute([
    'user_id' => $userId,
    'start_date' => $startDate,
    'end_date' => $endDate
]);

$walletStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Lucratividade';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Relatório de Lucratividade</h1>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <form method="GET" action="<?= BASE_URL ?>/reports/profit">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
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

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">R$ <?= number_format($totalEmprestado, 2, ',', '.') ?></div>
        <div class="stat-label">Total Emprestado</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <div class="stat-value">R$ <?= number_format($totalJuros, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Lucro Total (Juros)</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="stat-value">R$ <?= number_format($jurosRecebidos, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Lucro Recebido</div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div class="stat-value">R$ <?= number_format($jurosPendentes, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Lucro Pendente</div>
    </div>
</div>

<div class="grid-2" style="margin-top: 2rem;">
    <div class="card">
        <div class="card-header">
            <h2>Lucratividade por Carteira</h2>
        </div>

        <?php if (empty($walletStats)): ?>
            <div style="padding: 3rem; text-align: center; color: #95a5a6;">
                Nenhum empréstimo no período selecionado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Carteira</th>
                            <th>Empréstimos</th>
                            <th>Valor Emprestado</th>
                            <th>Lucro (Juros)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($walletStats as $stat): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($stat['name']) ?></strong></td>
                                <td><?= $stat['loan_count'] ?></td>
                                <td>R$ <?= number_format($stat['total_loaned'], 2, ',', '.') ?></td>
                                <td><strong style="color: #10b981;">R$ <?= number_format($stat['total_interest'], 2, ',', '.') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Análise de Rentabilidade</h2>
        </div>
        <div style="padding: 1.5rem;">
            <?php
            $roi = $totalEmprestado > 0 ? ($totalJuros / $totalEmprestado) * 100 : 0;
            $avgInterest = count($loans) > 0 ? array_sum(array_column($loans, 'interest_rate')) / count($loans) : 0;
            ?>

            <div style="display: grid; gap: 1.5rem;">
                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">ROI (Retorno sobre Investimento)</div>
                    <div style="font-size: 2rem; font-weight: bold; color: #10b981;">
                        <?= number_format($roi, 2, ',', '.') ?>%
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Taxa Média de Juros</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;">
                        <?= number_format($avgInterest, 2, ',', '.') ?>%
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Total de Empréstimos</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
                        <?= count($loans) ?>
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Ticket Médio</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
                        R$ <?= count($loans) > 0 ? number_format($totalEmprestado / count($loans), 2, ',', '.') : '0,00' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h2>Detalhamento de Empréstimos</h2>
    </div>

    <?php if (empty($loans)): ?>
        <div style="padding: 3rem; text-align: center; color: #95a5a6;">
            Nenhum empréstimo encontrado no período selecionado.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Carteira</th>
                        <th>Valor</th>
                        <th>Taxa</th>
                        <th>Lucro</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($loan['created_at'])) ?></td>
                            <td><?= htmlspecialchars($loan['client_name']) ?></td>
                            <td><?= htmlspecialchars($loan['wallet_name']) ?></td>
                            <td>R$ <?= number_format($loan['amount'], 2, ',', '.') ?></td>
                            <td><?= number_format($loan['interest_rate'], 0) ?>%</td>
                            <td><strong style="color: #10b981;">R$ <?= number_format($loan['interest_amount'], 2, ',', '.') ?></strong></td>
                            <td>
                                <?php if ($loan['status'] === 'paid'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><?= $loan['paid_installments'] ?>/<?= $loan['total_installments'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
