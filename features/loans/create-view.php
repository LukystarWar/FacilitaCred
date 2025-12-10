<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../features/clients/client-service.php';
require_once __DIR__ . '/../../features/wallets/wallet-service.php';
require_once __DIR__ . '/loan-service.php';

Session::requireAuth();

$userId = Session::get('user_id');
$clientService = new ClientService();
$walletService = new WalletService();
$loanService = new LoanService();

$clients = $clientService->getAllClients($userId);
$wallets = $walletService->getAllWallets($userId);

$selectedClientId = intval($_GET['client_id'] ?? 0);

$pageTitle = 'Novo Empréstimo';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/loans" class="btn-back">← Voltar</a>
        <h1>Novo Empréstimo</h1>
        <p class="page-subtitle">Preencha os dados do empréstimo</p>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/loans/create" id="loanForm">
        <div class="form-group">
            <label for="client_id">Cliente *</label>
            <select id="client_id" name="client_id" required onchange="updateClientInfo()">
                <option value="">Selecione um cliente</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= $client['id'] == $selectedClientId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($client['name']) ?>
                        <?php if ($client['cpf']): ?>
                            - CPF: <?= $clientService->formatCPF($client['cpf']) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($clients)): ?>
                <small style="color: #e74c3c;">
                    Nenhum cliente cadastrado. <a href="<?= BASE_URL ?>/clients">Cadastrar cliente</a>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="wallet_id">Carteira de Origem *</label>
            <select id="wallet_id" name="wallet_id" required onchange="updateWalletInfo()">
                <option value="">Selecione uma carteira</option>
                <?php foreach ($wallets as $wallet): ?>
                    <option value="<?= $wallet['id'] ?>" data-balance="<?= $wallet['balance'] ?>">
                        <?= htmlspecialchars($wallet['name']) ?> - Saldo: R$ <?= number_format($wallet['balance'], 2, ',', '.') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($wallets)): ?>
                <small style="color: #e74c3c;">
                    Nenhuma carteira cadastrada. <a href="<?= BASE_URL ?>/wallets">Cadastrar carteira</a>
                </small>
            <?php endif; ?>
            <small id="walletWarning" style="color: #e74c3c; display: none;">
                Saldo insuficiente na carteira!
            </small>
        </div>

        <div class="form-group">
            <label for="amount">Valor do Empréstimo *</label>
            <input type="number" id="amount" name="amount" step="0.01" min="1" required placeholder="0,00" oninput="calculateLoan()">
        </div>

        <div class="form-group">
            <label for="installments_count">Número de Parcelas *</label>
            <select id="installments_count" name="installments_count" required onchange="calculateLoan()">
                <option value="1">À vista (1x) - 20% de juros</option>
                <?php for ($i = 2; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?>x - <?= $i * 15 ?>% de juros</option>
                <?php endfor; ?>
            </select>
            <small>À vista: 20% | Parcelado: 15% ao mês (acumulativo)</small>
        </div>

        <div id="loanSummary" style="display: none; background: #f8f9ff; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
            <h3 style="margin-top: 0;">Resumo do Empréstimo</h3>
            <div style="display: grid; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Valor do empréstimo:</span>
                    <strong id="summary_amount">R$ 0,00</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Taxa de juros:</span>
                    <strong id="summary_interest_rate">0%</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Valor dos juros:</span>
                    <strong id="summary_interest_amount" style="color: #f59e0b;">R$ 0,00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 2px solid #ddd;">
                    <span>Total a receber:</span>
                    <strong id="summary_total" style="color: #10b981; font-size: 1.25rem;">R$ 0,00</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Valor de cada parcela:</span>
                    <strong id="summary_installment">R$ 0,00</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Primeira parcela vence em:</span>
                    <strong id="summary_first_due"></strong>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="border-top: none; padding: 0; margin-top: 2rem;">
            <a href="<?= BASE_URL ?>/loans" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                Confirmar Empréstimo
            </button>
        </div>
    </form>
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
</style>

<script>
function calculateLoan() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    if (amount <= 0) {
        document.getElementById('loanSummary').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (amount > walletBalance) {
        document.getElementById('walletWarning').style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
    } else {
        document.getElementById('walletWarning').style.display = 'none';
    }

    const interestRate = installmentsCount === 1 ? 20 : installmentsCount * 15;
    const interestAmount = (amount * interestRate) / 100;
    const totalAmount = amount + interestAmount;
    const installmentAmount = totalAmount / installmentsCount;

    document.getElementById('summary_amount').textContent = formatMoney(amount);
    document.getElementById('summary_interest_rate').textContent = interestRate + '%';
    document.getElementById('summary_interest_amount').textContent = formatMoney(interestAmount);
    document.getElementById('summary_total').textContent = formatMoney(totalAmount);
    document.getElementById('summary_installment').textContent = formatMoney(installmentAmount);

    const firstDue = new Date();
    firstDue.setMonth(firstDue.getMonth() + 1);
    document.getElementById('summary_first_due').textContent = firstDue.toLocaleDateString('pt-BR');

    document.getElementById('loanSummary').style.display = 'block';

    const clientId = document.getElementById('client_id').value;
    const walletId = document.getElementById('wallet_id').value;

    if (clientId && walletId && amount > 0 && amount <= walletBalance) {
        document.getElementById('submitBtn').disabled = false;
    } else {
        document.getElementById('submitBtn').disabled = true;
    }
}

function updateWalletInfo() {
    calculateLoan();
}

function updateClientInfo() {
    calculateLoan();
}

function formatMoney(value) {
    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.getElementById('loanForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount').value);
    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (amount > walletBalance) {
        e.preventDefault();
        alert('Saldo insuficiente na carteira!');
        return false;
    }

    return confirm('Confirma a criação deste empréstimo?');
});
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
