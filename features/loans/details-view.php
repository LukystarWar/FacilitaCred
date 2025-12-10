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
                    <th class="text-center">Ações</th>
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

                            if (in_array($installment['status'], ['pending', 'overdue']) && $dueDate < $today):
                                $daysLate = $diff->days;
                            ?>
                                <br><small style="color: #e74c3c; font-weight: 600;"><?= $daysLate ?> dia<?= $daysLate > 1 ? 's' : '' ?> de atraso</small>
                            <?php elseif ($installment['status'] === 'pending' && $dueDate > $today): ?>
                                <br><small class="text-muted">Faltam <?= $diff->days ?> dia<?= $diff->days > 1 ? 's' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>R$ <?= number_format($installment['amount'], 2, ',', '.') ?></strong>
                            <?php
                            // Calcular multa se estiver atrasada
                            if ($installment['status'] === 'overdue'):
                                $lateFeeInfo = $loanService->calculateLateFee($installment['amount'], $installment['due_date']);
                                if (!$lateFeeInfo['in_grace_period'] && $lateFeeInfo['late_fee_amount'] > 0):
                            ?>
                                <br><small style="color: #dc2626; font-weight: 600;">
                                    + R$ <?= number_format($lateFeeInfo['late_fee_amount'], 2, ',', '.') ?> (multa)
                                </small>
                                <br><small style="color: #059669; font-weight: 600;">
                                    = R$ <?= number_format($lateFeeInfo['total_amount'], 2, ',', '.') ?>
                                </small>
                            <?php elseif ($lateFeeInfo['in_grace_period']): ?>
                                <br><small style="color: #f59e0b;">
                                    Carência: <?= $lateFeeInfo['grace_period_days'] - $lateFeeInfo['days_late'] ?> dia(s)
                                </small>
                            <?php endif; endif; ?>
                        </td>
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
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <form method="POST" action="<?= BASE_URL ?>/loans/pay" style="display: inline;" onsubmit="return confirm('Confirma o pagamento desta parcela?')">
                                        <input type="hidden" name="installment_id" value="<?= $installment['id'] ?>">
                                        <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            ✓ Pagar
                                        </button>
                                    </form>
                                    <?php
                                    // Preparar dados para WhatsApp
                                    $whatsappData = [
                                        'phone' => preg_replace('/\D/', '', $loan['client_phone']),
                                        'client_name' => $loan['client_name'],
                                        'installment_number' => $installment['installment_number'],
                                        'total_installments' => $loan['installments_count'],
                                        'amount' => $installment['amount'],
                                        'due_date' => date('d/m/Y', strtotime($installment['due_date'])),
                                        'status' => $installment['status']
                                    ];
                                    $template = $installment['status'] === 'overdue' ? 'cobranca' : 'lembrete';
                                    ?>
                                    <a href="<?= BASE_URL ?>/loans/whatsapp?loan_id=<?= $loan['id'] ?>&installment_id=<?= $installment['id'] ?>&template=<?= $template ?>"
                                       class="btn btn-sm"
                                       style="background: #25D366; color: white;"
                                       title="Enviar cobrança via WhatsApp">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                        </svg>
                                        WhatsApp
                                    </a>
                                </div>
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
