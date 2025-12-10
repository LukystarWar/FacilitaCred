<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';

Session::requireAuth();

$loanService = new LoanService();
$userId = Session::get('user_id');

$loanId = intval($_GET['id'] ?? 0);

if ($loanId <= 0) {
    Session::setFlash('error', 'Empréstimo inválido');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

$loan = $loanService->getLoanById($loanId, $userId);

if (!$loan) {
    Session::setFlash('error', 'Empréstimo não encontrado');
    header('Location: ' . BASE_URL . '/loans');
    exit;
}

$installments = $loanService->getInstallments($loanId, $userId);

$pageTitle = 'Detalhes do Empréstimo';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/loans" class="btn-back">← Voltar</a>
        <h1>Empréstimo #<?= $loan['id'] ?></h1>
        <p class="page-subtitle">Cliente: <?= htmlspecialchars($loan['client_name']) ?></p>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <?php if ($loan['status'] === 'active'): ?>
            <a href="<?= BASE_URL ?>/loans/payoff?id=<?= $loan['id'] ?>" class="btn btn-primary">
                💰 Quitar Empréstimo
            </a>
            <span class="badge badge-info" style="font-size: 1rem; padding: 0.5rem 1rem;">Ativo</span>
        <?php elseif ($loan['status'] === 'paid'): ?>
            <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">Pago</span>
        <?php endif; ?>
    </div>
</div>

<?php
$paidCount = count(array_filter($installments, fn($i) => $i['status'] === 'paid'));
$paidAmount = array_sum(array_map(fn($i) => $i['status'] === 'paid' ? $i['amount'] : 0, $installments));
$pendingAmount = $loan['total_amount'] - $paidAmount;
$overdueCount = count(array_filter($installments, fn($i) => $i['status'] === 'overdue'));
?>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($loan['amount'], 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Valor Emprestado</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #65A30D;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($loan['total_amount'], 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Total + Juros (<?= $loan['interest_rate'] ?>%)</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #11C76F;">R$ <?= number_format($paidAmount, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Já Recebido</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #DC2626;">
        <div class="stat-value" style="color: #DC2626;">R$ <?= number_format($pendingAmount, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">A Receber</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 2rem;">
    <div class="card">
        <div class="card-header">
            <h2>Informações do Empréstimo</h2>
        </div>
        <div style="display: grid; gap: 1rem;">
            <div>
                <strong>Cliente:</strong><br>
                <a href="<?= BASE_URL ?>/clients/<?= $loan['client_id'] ?>"><?= htmlspecialchars($loan['client_name']) ?></a>
                <?php if ($loan['client_phone']): ?>
                    <br><small><?= htmlspecialchars($loan['client_phone']) ?></small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Carteira de Origem:</strong><br>
                <a href="<?= BASE_URL ?>/wallets/<?= $loan['wallet_id'] ?>"><?= htmlspecialchars($loan['wallet_name']) ?></a>
            </div>
            <div>
                <strong>Data do Empréstimo:</strong><br>
                <?= date('d/m/Y H:i', strtotime($loan['created_at'])) ?>
            </div>
            <div>
                <strong>Parcelas:</strong><br>
                <?= $paidCount ?>/<?= $loan['installments_count'] ?> pagas
                (<?= number_format(($paidCount / $loan['installments_count']) * 100, 0) ?>%)
                <?php if ($overdueCount > 0): ?>
                    <br><span class="badge badge-danger"><?= $overdueCount ?> em atraso</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Resumo Financeiro</h2>
        </div>
        <div style="display: grid; gap: 1rem;">
            <div>
                <strong>Valor do Empréstimo:</strong><br>
                <span style="font-size: 1.25rem;">R$ <?= number_format($loan['amount'], 2, ',', '.') ?></span>
            </div>
            <div>
                <strong>Juros Aplicados:</strong><br>
                <span style="font-size: 1.25rem; color: #f59e0b;"><?= $loan['interest_rate'] ?>% (R$ <?= number_format($loan['interest_amount'], 2, ',', '.') ?>)</span>
            </div>
            <div>
                <strong>Valor de Cada Parcela:</strong><br>
                <span style="font-size: 1.25rem;">R$ <?= number_format($loan['total_amount'] / $loan['installments_count'], 2, ',', '.') ?></span>
            </div>
            <div style="padding-top: 1rem; border-top: 2px solid #e0e0e0;">
                <strong>Lucro Total (Juros):</strong><br>
                <span style="font-size: 1.5rem; color: #10b981;">R$ <?= number_format($loan['interest_amount'], 2, ',', '.') ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Parcelas</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data Pagamento</th>
                    <th class="text-center">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($installments as $installment): ?>
                    <tr>
                        <td><strong><?= $installment['installment_number'] ?></strong></td>
                        <td>
                            <?= date('d/m/Y', strtotime($installment['due_date'])) ?>
                            <?php
                            $dueDate = new DateTime($installment['due_date']);
                            $today = new DateTime();
                            $diff = $today->diff($dueDate);
                            if ($installment['status'] === 'pending' && $dueDate < $today):
                                $daysLate = $diff->days;
                            ?>
                                <br><small style="color: #e74c3c;"><?= $daysLate ?> dia<?= $daysLate > 1 ? 's' : '' ?> de atraso</small>
                            <?php elseif ($installment['status'] === 'pending' && $dueDate > $today): ?>
                                <br><small class="text-muted">Faltam <?= $diff->days ?> dia<?= $diff->days > 1 ? 's' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong>R$ <?= number_format($installment['amount'], 2, ',', '.') ?></strong></td>
                        <td>
                            <?php if ($installment['status'] === 'paid'): ?>
                                <span class="badge badge-success">Paga</span>
                            <?php elseif ($installment['status'] === 'overdue'): ?>
                                <span class="badge badge-danger">Atrasada</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($installment['paid_date']): ?>
                                <?= date('d/m/Y H:i', strtotime($installment['paid_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($installment['status'] !== 'paid'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/loans/pay" style="display: inline;" onsubmit="return confirm('Confirma o pagamento desta parcela?')">
                                    <input type="hidden" name="installment_id" value="<?= $installment['id'] ?>">
                                    <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        ✓ Pagar
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

.page-subtitle {
    color: #7f8c8d;
    margin: 0.5rem 0 0 0;
}

.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
