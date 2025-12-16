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
                    <th>Parcela</th>
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
                        <td><strong><?= $installment['installment_number'] ?>/<?= $loan['installments_count'] ?></strong></td>
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
                                    <?php
                                    // Calcular multa para passar ao modal
                                    $modalLateFee = 0;
                                    $modalTotalAmount = $installment['amount'];
                                    $modalDaysLate = 0;
                                    $modalInGrace = false;
                                    if ($installment['status'] === 'overdue') {
                                        $lateFeeData = $loanService->calculateLateFee($installment['amount'], $installment['due_date']);
                                        $modalLateFee = $lateFeeData['late_fee_amount'];
                                        $modalTotalAmount = $lateFeeData['total_amount'];
                                        $modalDaysLate = $lateFeeData['days_late'];
                                        $modalInGrace = $lateFeeData['in_grace_period'];
                                    }
                                    ?>
                                    <button type="button" class="btn btn-sm btn-success"
                                        onclick="openPaymentModal(<?= $installment['id'] ?>, <?= $loan['id'] ?>, <?= $installment['amount'] ?>, <?= $installment['installment_number'] ?>, '<?= $installment['status'] ?>', <?= $modalLateFee ?>, <?= $modalTotalAmount ?>, <?= $modalDaysLate ?>, <?= $modalInGrace ? 'true' : 'false' ?>)">
                                        ✓ Pagar
                                    </button>
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
                                       title="Enviar cobrança via WhatsApp"
                                       target="_blank">
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

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-overlay.active {
    display: flex;
}
</style>

<!-- Modal de Pagamento -->
<div id="paymentModal" class="modal-overlay" onclick="if(event.target === this) closePaymentModal()">
    <div class="modal-content">
        <h2 style="margin-bottom: 1.5rem;">Registrar Pagamento</h2>

        <form method="POST" action="<?= BASE_URL ?>/loans/pay" id="paymentForm">
            <input type="hidden" name="installment_id" id="modal_installment_id">
            <input type="hidden" name="loan_id" id="modal_loan_id">
            <input type="hidden" name="adjustment_amount" id="modal_adjustment_amount" value="0">

            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f3f4f6; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Empréstimo:</span>
                    <strong id="modal_loan_info"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Parcela:</span>
                    <strong id="modal_installment_info"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Valor Original:</span>
                    <strong id="modal_original_amount"></strong>
                </div>
                <div id="modal_late_fee_info" style="display: none; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #d1d5db;"></div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Tipo de Ajuste</label>
                <select id="adjustment_type" class="form-control" onchange="toggleAdjustment()">
                    <option value="none">Sem ajuste</option>
                    <option value="discount">Desconto</option>
                    <option value="addition">Acréscimo</option>
                    <option value="late_fee">Usar Multa Calculada</option>
                </select>
            </div>

            <div id="adjustment_input_group" style="display: none; margin-bottom: 1rem;">
                <label class="form-label">Valor do Ajuste (R$)</label>
                <input type="number" id="adjustment_value" class="form-control" step="0.01" min="0" value="0" oninput="calculateTotal()">
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Motivo do Ajuste (opcional)</label>
                <input type="text" name="adjustment_reason" id="adjustment_reason" class="form-control" placeholder="Ex: Desconto por pagamento antecipado">
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Forma de Pagamento *</label>
                <select name="payment_method" class="form-control" required>
                    <option value="">Selecione...</option>
                    <option value="Dinheiro">Dinheiro</option>
                    <option value="PIX">PIX</option>
                    <option value="Transferência">Transferência</option>
                    <option value="Cartão de Débito">Cartão de Débito</option>
                    <option value="Cartão de Crédito">Cartão de Crédito</option>
                </select>
            </div>

            <div style="padding: 1rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Valor Total a Receber:</span>
                    <span id="final_amount" style="font-size: 1.5rem; font-weight: 700; color: #059669;">R$ 0,00</span>
                </div>
                <div id="adjustment_info" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; display: none;"></div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar Pagamento</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentInstallment = {};

function openPaymentModal(installmentId, loanId, amount, installmentNumber, status, lateFee = 0, totalAmount = 0, daysLate = 0, inGrace = false) {
    currentInstallment = {
        installmentId,
        loanId,
        amount,
        installmentNumber,
        status,
        lateFee,
        totalAmount: totalAmount || amount,
        daysLate,
        inGrace
    };

    document.getElementById('modal_installment_id').value = installmentId;
    document.getElementById('modal_loan_id').value = loanId;
    document.getElementById('modal_loan_info').textContent = '#' + loanId;
    document.getElementById('modal_installment_info').textContent = installmentNumber + ' de <?= $loan["installments_count"] ?>';
    document.getElementById('modal_original_amount').textContent = 'R$ ' + amount.toFixed(2).replace('.', ',');

    // Exibir informações de multa se atrasada
    const lateFeeInfo = document.getElementById('modal_late_fee_info');
    if (status === 'overdue' && lateFee > 0) {
        lateFeeInfo.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #dc2626;">Multa por Atraso (${daysLate} dia${daysLate > 1 ? 's' : ''}):</span>
                <strong style="color: #dc2626;">+ R$ ${lateFee.toFixed(2).replace('.', ',')}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 1px solid #d1d5db;">
                <span style="font-weight: 600;">Total com Multa:</span>
                <strong style="color: #059669; font-size: 1.1rem;">R$ ${totalAmount.toFixed(2).replace('.', ',')}</strong>
            </div>
        `;
        lateFeeInfo.style.display = 'block';
    } else if (status === 'overdue' && inGrace) {
        lateFeeInfo.innerHTML = `
            <div style="color: #f59e0b; font-size: 0.875rem;">
                ⏳ Parcela em período de carência (${daysLate} dia${daysLate > 1 ? 's' : ''} de atraso)
            </div>
        `;
        lateFeeInfo.style.display = 'block';
    } else {
        lateFeeInfo.style.display = 'none';
    }

    document.getElementById('adjustment_type').value = 'none';
    document.getElementById('adjustment_value').value = '0';
    document.getElementById('adjustment_reason').value = '';
    toggleAdjustment();
    calculateTotal();

    document.getElementById('paymentModal').classList.add('active');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
}

function toggleAdjustment() {
    const type = document.getElementById('adjustment_type').value;
    const inputGroup = document.getElementById('adjustment_input_group');
    const adjustmentValue = document.getElementById('adjustment_value');

    if (type === 'none') {
        inputGroup.style.display = 'none';
        adjustmentValue.value = '0';
    } else if (type === 'late_fee') {
        inputGroup.style.display = 'none';
        // Aqui você pode calcular a multa baseado nas configurações
        // Por simplicidade, vamos usar um valor exemplo
        adjustmentValue.value = '0';
    } else {
        inputGroup.style.display = 'block';
        adjustmentValue.value = '0';
    }

    calculateTotal();
}

function calculateTotal() {
    const type = document.getElementById('adjustment_type').value;
    const adjustmentValue = parseFloat(document.getElementById('adjustment_value').value) || 0;

    // Usar valor COM multa como base para cálculo (se houver multa)
    const baseAmount = currentInstallment.totalAmount || currentInstallment.amount;

    let finalAmount = baseAmount;
    let adjustmentAmount = 0;
    let adjustmentText = '';

    if (type === 'discount') {
        adjustmentAmount = -adjustmentValue;
        finalAmount = baseAmount - adjustmentValue;
        adjustmentText = `Desconto de R$ ${adjustmentValue.toFixed(2).replace('.', ',')}`;
    } else if (type === 'addition') {
        adjustmentAmount = adjustmentValue;
        finalAmount = baseAmount + adjustmentValue;
        adjustmentText = `Acréscimo de R$ ${adjustmentValue.toFixed(2).replace('.', ',')}`;
    }

    document.getElementById('modal_adjustment_amount').value = adjustmentAmount.toFixed(2);
    document.getElementById('final_amount').textContent = 'R$ ' + finalAmount.toFixed(2).replace('.', ',');

    const adjustmentInfo = document.getElementById('adjustment_info');
    if (adjustmentText) {
        adjustmentInfo.textContent = adjustmentText;
        adjustmentInfo.style.display = 'block';
    } else {
        adjustmentInfo.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
