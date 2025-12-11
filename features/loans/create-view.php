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

        <div id="calculationFields" style="display: none;">
            <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #10b981;">
                <small style="color: #059669; font-weight: 500;">💡 Dica: Você pode editar o Valor Total ou Valor da Parcela para ajustar os juros automaticamente!</small>
            </div>

            <div class="form-group">
                <label for="total_amount">Valor Total a Receber *</label>
                <input type="number" id="total_amount" step="0.01" min="0" placeholder="0,00" oninput="calculateFromTotal()">
                <small>Total = Valor do empréstimo + Juros. Edite para definir um valor total personalizado.</small>
            </div>

            <div class="form-group">
                <label for="installment_value">Valor de Cada Parcela *</label>
                <input type="number" id="installment_value" step="0.01" min="0" placeholder="0,00" oninput="calculateFromInstallment()">
                <small>Edite para definir um valor de parcela personalizado. O total será recalculado automaticamente.</small>
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
let calculationSource = 'amount'; // 'amount', 'total', or 'installment'

function calculateLoan() {
    if (calculationSource !== 'amount') return;

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    if (amount <= 0) {
        document.getElementById('calculationFields').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    document.getElementById('calculationFields').style.display = 'block';

    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    // Calculate using standard interest rate
    const interestRate = installmentsCount === 1 ? 20 : installmentsCount * 15;
    const interestAmount = (amount * interestRate) / 100;
    const totalAmount = amount + interestAmount;
    const installmentAmount = totalAmount / installmentsCount;

    // Update fields
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
    document.getElementById('installment_value').value = installmentAmount.toFixed(2);

    // Validate wallet balance
    if (amount > walletBalance) {
        document.getElementById('walletWarning').style.display = 'block';
    } else {
        document.getElementById('walletWarning').style.display = 'none';
    }

    updateSummary(amount, interestRate, interestAmount, totalAmount, installmentAmount);
}

function calculateFromTotal() {
    calculationSource = 'total';

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    if (totalAmount <= 0 || amount <= 0) {
        calculationSource = 'amount';
        calculateLoan();
        return;
    }

    // Calculate interest and installment from total
    const interestAmount = totalAmount - amount;
    const interestRate = (interestAmount / amount) * 100;
    const installmentAmount = totalAmount / installmentsCount;

    // Update installment field
    document.getElementById('installment_value').value = installmentAmount.toFixed(2);

    // Validate wallet balance
    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (amount > walletBalance) {
        document.getElementById('walletWarning').style.display = 'block';
    } else {
        document.getElementById('walletWarning').style.display = 'none';
    }

    updateSummary(amount, interestRate, interestAmount, totalAmount, installmentAmount);

    setTimeout(() => { calculationSource = 'total'; }, 100);
}

function calculateFromInstallment() {
    calculationSource = 'installment';

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installmentValue = parseFloat(document.getElementById('installment_value').value) || 0;
    const installmentsCount = parseInt(document.getElementById('installments_count').value) || 1;

    if (installmentValue <= 0 || amount <= 0) {
        calculationSource = 'amount';
        calculateLoan();
        return;
    }

    // Calculate total and interest from installment value
    const totalAmount = installmentValue * installmentsCount;
    const interestAmount = totalAmount - amount;
    const interestRate = (interestAmount / amount) * 100;

    // Update total field
    document.getElementById('total_amount').value = totalAmount.toFixed(2);

    // Validate wallet balance
    const walletSelect = document.getElementById('wallet_id');
    const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
    const walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;

    if (amount > walletBalance) {
        document.getElementById('walletWarning').style.display = 'block';
    } else {
        document.getElementById('walletWarning').style.display = 'none';
    }

    updateSummary(amount, interestRate, interestAmount, totalAmount, installmentValue);

    setTimeout(() => { calculationSource = 'installment'; }, 100);
}

function updateSummary(amount, interestRate, interestAmount, totalAmount, installmentAmount) {
    const clientId = document.getElementById('client_id').value;
    const walletId = document.getElementById('wallet_id').value;

    // Get wallet balance
    let walletBalance = 0;
    if (walletId) {
        const walletSelect = document.getElementById('wallet_id');
        const selectedWallet = walletSelect.options[walletSelect.selectedIndex];
        walletBalance = parseFloat(selectedWallet.getAttribute('data-balance')) || 0;
    }

    // Enable submit button if all conditions are met
    if (clientId && walletId && amount > 0 && amount <= walletBalance && totalAmount > 0 && installmentAmount > 0) {
        document.getElementById('submitBtn').disabled = false;
    } else {
        document.getElementById('submitBtn').disabled = true;
    }
}

function updateWalletInfo() {
    calculationSource = 'amount';
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
