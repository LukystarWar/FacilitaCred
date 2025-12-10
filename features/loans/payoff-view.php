<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';
require_once __DIR__ . '/../wallets/wallet-service.php';

Session::requireAuth();

$loanService = new LoanService();
$walletService = new WalletService();
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

if ($loan['status'] === 'paid') {
    Session::setFlash('error', 'Este empréstimo já foi quitado');
    header('Location: ' . BASE_URL . '/loans/' . $loanId);
    exit;
}

$installments = $loanService->getInstallments($loanId, $userId);
$wallets = $walletService->getAllWallets($userId);

// Calcular valores pendentes
$pendingInstallments = array_filter($installments, fn($i) => $i['status'] !== 'paid');
$totalPending = array_sum(array_map(fn($i) => $i['amount'], $pendingInstallments));
$countPending = count($pendingInstallments);

$pageTitle = 'Quitar Empréstimo';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/loans/<?= $loan['id'] ?>" class="btn-back">← Voltar</a>
        <h1>Quitar Empréstimo #<?= $loan['id'] ?></h1>
        <p class="page-subtitle">Cliente: <?= htmlspecialchars($loan['client_name']) ?></p>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 2rem;">
    <!-- Resumo do Empréstimo -->
    <div class="card">
        <div class="card-header">
            <h2>Resumo do Empréstimo</h2>
        </div>
        <div style="display: grid; gap: 1rem; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280;">Total do Empréstimo:</span>
                <strong>R$ <?= number_format($loan['total_amount'], 2, ',', '.') ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280;">Parcelas Pendentes:</span>
                <strong style="color: #dc3545;"><?= $countPending ?> parcelas</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280;">Total Pendente:</span>
                <strong style="color: #dc3545; font-size: 1.25rem;">R$ <?= number_format($totalPending, 2, ',', '.') ?></strong>
            </div>
        </div>
    </div>

    <!-- Calculadora de Quitação -->
    <div class="card">
        <div class="card-header">
            <h2>Calculadora de Quitação</h2>
        </div>
        <div style="padding: 1.5rem;">
            <form id="payoffCalculator">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label class="form-label">Tipo de Ajuste</label>
                        <select id="adjustmentType" class="form-control" onchange="calculatePayoff()">
                            <option value="none">Sem ajuste</option>
                            <option value="discount">Desconto</option>
                            <option value="addition">Acréscimo (multa/juros)</option>
                        </select>
                    </div>

                    <div id="adjustmentInputGroup" style="display: none;">
                        <label class="form-label">Valor do Ajuste (R$)</label>
                        <input type="number" id="adjustmentValue" class="form-control"
                               step="0.01" min="0" value="0" oninput="calculatePayoff()">
                    </div>

                    <div style="background: #f3f4f6; padding: 1rem; border-radius: 0.5rem; margin-top: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #1f2937;">Valor Total a Receber:</span>
                            <span id="finalAmount" style="font-size: 1.5rem; font-weight: 700; color: #11C76F;">
                                R$ <?= number_format($totalPending, 2, ',', '.') ?>
                            </span>
                        </div>
                        <div id="adjustmentInfo" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; display: none;">
                            <!-- Info será preenchida pelo JavaScript -->
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulário de Quitação -->
<div class="card">
    <div class="card-header">
        <h2>Confirmar Quitação</h2>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/loans/payoff" style="padding: 1.5rem;">
        <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
        <input type="hidden" id="hiddenAdjustmentAmount" name="adjustment_amount" value="0">
        <input type="hidden" id="hiddenFinalAmount" name="final_amount" value="<?= $totalPending ?>">

        <div style="display: grid; gap: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="wallet_id" class="form-label">Carteira para Recebimento *</label>
                    <select id="wallet_id" name="wallet_id" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($wallets as $wallet): ?>
                            <option value="<?= $wallet['id'] ?>" <?= $wallet['id'] == $loan['wallet_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wallet['name']) ?> - R$ <?= number_format($wallet['balance'], 2, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="payment_method" class="form-label">Forma de Pagamento *</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="PIX">PIX</option>
                        <option value="Transferência">Transferência</option>
                        <option value="Cartão de Débito">Cartão de Débito</option>
                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="adjustment_reason" class="form-label">Motivo do Ajuste (se houver)</label>
                <textarea id="adjustment_reason" name="adjustment_reason" class="form-control" rows="3"
                          placeholder="Ex: Desconto por quitação antecipada, Multa por atraso, etc."></textarea>
            </div>

            <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 0.5rem; padding: 1rem;">
                <p style="margin: 0; color: #78350f; font-weight: 500;">
                    ⚠️ Atenção: Esta ação irá quitar TODAS as parcelas pendentes deste empréstimo e não poderá ser desfeita.
                </p>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <a href="<?= BASE_URL ?>/loans/<?= $loan['id'] ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Tem certeza que deseja quitar este empréstimo? Esta ação não pode ser desfeita.')">
                    Confirmar Quitação
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const totalPending = <?= $totalPending ?>;

function calculatePayoff() {
    const type = document.getElementById('adjustmentType').value;
    const adjustmentInput = document.getElementById('adjustmentValue');
    const adjustmentGroup = document.getElementById('adjustmentInputGroup');
    const finalAmountEl = document.getElementById('finalAmount');
    const adjustmentInfoEl = document.getElementById('adjustmentInfo');
    const hiddenAdjustment = document.getElementById('hiddenAdjustmentAmount');
    const hiddenFinal = document.getElementById('hiddenFinalAmount');

    if (type === 'none') {
        adjustmentGroup.style.display = 'none';
        adjustmentInfoEl.style.display = 'none';
        finalAmountEl.textContent = 'R$ ' + totalPending.toFixed(2).replace('.', ',');
        hiddenAdjustment.value = 0;
        hiddenFinal.value = totalPending.toFixed(2);
        return;
    }

    adjustmentGroup.style.display = 'block';
    const adjustmentValue = parseFloat(adjustmentInput.value) || 0;

    let finalAmount;
    let adjustmentAmount;
    let adjustmentText;

    if (type === 'discount') {
        finalAmount = totalPending - adjustmentValue;
        adjustmentAmount = -adjustmentValue;
        adjustmentText = `Desconto de R$ ${adjustmentValue.toFixed(2).replace('.', ',')}`;
        finalAmountEl.style.color = '#11C76F';
    } else { // addition
        finalAmount = totalPending + adjustmentValue;
        adjustmentAmount = adjustmentValue;
        adjustmentText = `Acréscimo de R$ ${adjustmentValue.toFixed(2).replace('.', ',')}`;
        finalAmountEl.style.color = '#dc3545';
    }

    finalAmountEl.textContent = 'R$ ' + finalAmount.toFixed(2).replace('.', ',');
    adjustmentInfoEl.textContent = adjustmentText;
    adjustmentInfoEl.style.display = 'block';
    hiddenAdjustment.value = adjustmentAmount.toFixed(2);
    hiddenFinal.value = finalAmount.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
