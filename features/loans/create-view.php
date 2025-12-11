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

$clientsResult = $clientService->getAllClients($userId, '', 1, 1000);
$clients = $clientsResult['data'];
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
            <label for="client_search">Cliente *</label>
            <input type="text" id="client_search" placeholder="🔍 Digite o nome do cliente..." autocomplete="off" onkeyup="filterClients()" onfocus="showClientDropdown()">
            <input type="hidden" id="client_id" name="client_id" required>

            <div id="client_dropdown" class="autocomplete-dropdown" style="display: none;">
                <?php if (empty($clients)): ?>
                    <div class="autocomplete-item" style="color: #e74c3c;">
                        Nenhum cliente cadastrado. <a href="<?= BASE_URL ?>/clients">Cadastrar cliente</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <div class="autocomplete-item"
                             data-id="<?= $client['id'] ?>"
                             data-name="<?= htmlspecialchars($client['name']) ?>"
                             data-cpf="<?= htmlspecialchars($client['cpf'] ?? '') ?>"
                             onclick="selectClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                            <strong><?= htmlspecialchars($client['name']) ?></strong>
                            <?php if ($client['cpf']): ?>
                                <br><small style="color: #6b7280;">CPF: <?= $clientService->formatCPF($client['cpf']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="selected_client" style="display: none; margin-top: 0.5rem; padding: 0.75rem; background: #e8f5e9; border-radius: 4px; border: 1px solid #11C76F;">
                <strong id="selected_client_name"></strong>
                <button type="button" onclick="clearClientSelection()" style="float: right; background: none; border: none; color: #dc3545; cursor: pointer; font-weight: bold;">✕</button>
            </div>
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
                    <option value="<?= $i ?>"><?= $i ?>x - 15% a.m</option>
                <?php endfor; ?>
            </select>
            <small>À vista: 20% | Parcelado: 15% a.m (ao mês, acumulativo)</small>
        </div>

        <div class="form-group">
            <label for="installment_value">Valor da Parcela (Opcional)</label>
            <input type="number" id="installment_value" step="0.01" min="0" placeholder="Deixe em branco para calcular automaticamente" oninput="calculateFromInstallment()">
            <small>Se preenchido, o sistema calculará os juros automaticamente baseado neste valor</small>
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

.autocomplete-dropdown {
    position: absolute;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    width: calc(100% - 2rem);
    margin-top: 0.25rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.autocomplete-item {
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
}

.autocomplete-item:hover {
    background: #f9fafb;
}

.autocomplete-item:last-child {
    border-bottom: none;
}
</style>

<script>
// Client search and selection
let isCalculatingFromInstallment = false;

function filterClients() {
    const searchValue = document.getElementById('client_search').value.toLowerCase();
    const items = document.querySelectorAll('.autocomplete-item');

    items.forEach(item => {
        const name = item.getAttribute('data-name')?.toLowerCase() || '';
        const cpf = item.getAttribute('data-cpf')?.toLowerCase() || '';

        if (name.includes(searchValue) || cpf.includes(searchValue)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });

    showClientDropdown();
}

function showClientDropdown() {
    document.getElementById('client_dropdown').style.display = 'block';
}

function selectClient(id, name) {
    document.getElementById('client_id').value = id;
    document.getElementById('client_search').value = '';
    document.getElementById('client_dropdown').style.display = 'none';
    document.getElementById('selected_client').style.display = 'block';
    document.getElementById('selected_client_name').textContent = name;
    calculateLoan();
}

function clearClientSelection() {
    document.getElementById('client_id').value = '';
    document.getElementById('client_search').value = '';
    document.getElementById('selected_client').style.display = 'none';
    calculateLoan();
}

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.form-group')) {
        document.getElementById('client_dropdown').style.display = 'none';
    }
});

// Loan calculation functions
function calculateLoan() {
    if (isCalculatingFromInstallment) return;

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    // Clear installment value field when calculating normally
    document.getElementById('installment_value').value = '';

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

    updateSummary(amount, interestRate, interestAmount, totalAmount, installmentAmount);
}

function calculateFromInstallment() {
    const installmentValue = parseFloat(document.getElementById('installment_value').value) || 0;
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    if (installmentValue <= 0 || amount <= 0) {
        calculateLoan();
        return;
    }

    isCalculatingFromInstallment = true;

    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (amount > walletBalance) {
        document.getElementById('walletWarning').style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
    } else {
        document.getElementById('walletWarning').style.display = 'none';
    }

    // Calculate total and interest from installment value
    const totalAmount = installmentValue * installmentsCount;
    const interestAmount = totalAmount - amount;
    const interestRate = (interestAmount / amount) * 100;

    updateSummary(amount, interestRate, interestAmount, totalAmount, installmentValue);

    isCalculatingFromInstallment = false;
}

function updateSummary(amount, interestRate, interestAmount, totalAmount, installmentAmount) {
    document.getElementById('summary_amount').textContent = formatMoney(amount);
    document.getElementById('summary_interest_rate').textContent = interestRate.toFixed(2) + '%';
    document.getElementById('summary_interest_amount').textContent = formatMoney(interestAmount);
    document.getElementById('summary_total').textContent = formatMoney(totalAmount);
    document.getElementById('summary_installment').textContent = formatMoney(installmentAmount);

    const firstDue = new Date();
    firstDue.setMonth(firstDue.getMonth() + 1);
    document.getElementById('summary_first_due').textContent = firstDue.toLocaleDateString('pt-BR');

    document.getElementById('loanSummary').style.display = 'block';

    const clientId = document.getElementById('client_id').value;
    const walletId = document.getElementById('wallet_id').value;
    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (clientId && walletId && amount > 0 && amount <= walletBalance) {
        document.getElementById('submitBtn').disabled = false;
    } else {
        document.getElementById('submitBtn').disabled = true;
    }
}

function updateWalletInfo() {
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
