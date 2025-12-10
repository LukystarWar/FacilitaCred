<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/settings-service.php';

Session::requireAuth();

$settingsService = new SettingsService();
$userId = Session::get('user_id');

// Obter configurações por categoria
$interestSettings = $settingsService->getSettingsByCategory('interest');
$penaltySettings = $settingsService->getSettingsByCategory('penalty');
$gracePeriodSettings = $settingsService->getSettingsByCategory('grace_period');
$loanRulesSettings = $settingsService->getSettingsByCategory('loan_rules');

$pageTitle = 'Configurações do Sistema';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>⚙️ Configurações do Sistema</h1>
    <p style="color: #6b7280; margin-top: 0.5rem;">
        Gerencie as regras de negócio e parâmetros do sistema
    </p>
</div>

<!-- Abas de Navegação -->
<div class="tabs">
    <button class="tab-button active" onclick="openTab(event, 'interest')">
        💰 Juros
    </button>
    <button class="tab-button" onclick="openTab(event, 'penalty')">
        ⏰ Multas e Carência
    </button>
    <button class="tab-button" onclick="openTab(event, 'loan-rules')">
        📋 Regras de Empréstimos
    </button>
</div>

<!-- Aba: Juros -->
<div id="interest" class="tab-content active">
    <div class="card">
        <div class="card-header">
            <h2>💰 Configurações de Juros</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/update-interest">
                <div class="form-group">
                    <label for="interest_rate_single_payment">
                        <strong>Taxa de Juros à Vista</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input
                            type="number"
                            id="interest_rate_single_payment"
                            name="interest_rate_single_payment"
                            value="<?= $interestSettings['interest_rate_single_payment']['value'] ?>"
                            step="0.01"
                            min="0"
                            max="100"
                            required
                            style="max-width: 150px;">
                        <span>% ao total</span>
                    </div>
                    <small>Aplicada quando o empréstimo é pago em uma única parcela.</small>
                </div>

                <div class="form-group">
                    <label for="interest_rate_installment">
                        <strong>Taxa de Juros ao Mês (Parcelado)</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input
                            type="number"
                            id="interest_rate_installment"
                            name="interest_rate_installment"
                            value="<?= $interestSettings['interest_rate_installment']['value'] ?>"
                            step="0.01"
                            min="0"
                            max="100"
                            required
                            style="max-width: 150px;">
                        <span>% ao mês</span>
                    </div>
                    <small>Aplicada cumulativamente em cada parcela do empréstimo.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Aba: Multas e Carência -->
<div id="penalty" class="tab-content">
    <div class="card">
        <div class="card-header">
            <h2>⏰ Carência e Multas por Atraso</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/update-penalty">
                <div class="form-group">
                    <label for="grace_period_days">
                        <strong>Período de Carência</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input
                            type="number"
                            id="grace_period_days"
                            name="grace_period_days"
                            value="<?= $gracePeriodSettings['grace_period_days']['value'] ?>"
                            min="0"
                            max="30"
                            required
                            style="max-width: 150px;">
                        <span>dias</span>
                    </div>
                    <small>Dias de tolerância após o vencimento antes de aplicar multa por atraso.</small>
                </div>

                <div class="form-group">
                    <label for="late_fee_percentage">
                        <strong>Juros por Atraso (após carência)</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input
                            type="number"
                            id="late_fee_percentage"
                            name="late_fee_percentage"
                            value="<?= $penaltySettings['late_fee_percentage']['value'] ?>"
                            step="0.01"
                            min="0"
                            max="100"
                            required
                            style="max-width: 150px;">
                        <span>% ao dia</span>
                    </div>
                    <small>Taxa aplicada diariamente sobre o valor da parcela em atraso, após o período de carência.</small>
                </div>

                <div class="alert alert-info" style="margin-top: 1.5rem;">
                    <strong>📌 Exemplo de Cálculo:</strong><br>
                    Parcela: R$ 1.000,00 | Vencimento: 01/12 | Hoje: 10/12<br>
                    Atraso: 9 dias | Carência: <?= $gracePeriodSettings['grace_period_days']['value'] ?> dias | Dias com multa: <?= max(0, 9 - $gracePeriodSettings['grace_period_days']['value']) ?> dias<br>
                    Multa: R$ 1.000 × <?= $penaltySettings['late_fee_percentage']['value'] ?>% × <?= max(0, 9 - $gracePeriodSettings['grace_period_days']['value']) ?> = R$ <?= number_format(1000 * ($penaltySettings['late_fee_percentage']['value'] / 100) * max(0, 9 - $gracePeriodSettings['grace_period_days']['value']), 2, ',', '.') ?><br>
                    <strong>Total: R$ <?= number_format(1000 + (1000 * ($penaltySettings['late_fee_percentage']['value'] / 100) * max(0, 9 - $gracePeriodSettings['grace_period_days']['value'])), 2, ',', '.') ?></strong>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Aba: Regras de Empréstimos -->
<div id="loan-rules" class="tab-content">
    <div class="card">
        <div class="card-header">
            <h2>📋 Regras de Empréstimos</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/settings/update-loan-rules">
                <div class="form-group">
                    <label for="min_loan_amount">
                        <strong>Valor Mínimo de Empréstimo</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span>R$</span>
                        <input
                            type="number"
                            id="min_loan_amount"
                            name="min_loan_amount"
                            value="<?= $loanRulesSettings['min_loan_amount']['value'] ?>"
                            step="0.01"
                            min="0"
                            required
                            style="max-width: 200px;">
                    </div>
                    <small>Valor mínimo permitido para criar um novo empréstimo.</small>
                </div>

                <div class="form-group">
                    <label for="max_loan_amount">
                        <strong>Valor Máximo de Empréstimo</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span>R$</span>
                        <input
                            type="number"
                            id="max_loan_amount"
                            name="max_loan_amount"
                            value="<?= $loanRulesSettings['max_loan_amount']['value'] ?>"
                            step="0.01"
                            min="0"
                            required
                            style="max-width: 200px;">
                    </div>
                    <small>Valor máximo permitido para criar um novo empréstimo.</small>
                </div>

                <div class="form-group">
                    <label for="max_installments">
                        <strong>Número Máximo de Parcelas</strong>
                    </label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input
                            type="number"
                            id="max_installments"
                            name="max_installments"
                            value="<?= $loanRulesSettings['max_installments']['value'] ?>"
                            min="1"
                            max="100"
                            required
                            style="max-width: 150px;">
                        <span>parcelas</span>
                    </div>
                    <small>Número máximo de parcelas permitidas em um empréstimo.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.tab-button {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s;
}

.tab-button:hover {
    color: #11C76F;
    background: #f9fafb;
}

.tab-button.active {
    color: #11C76F;
    border-bottom-color: #11C76F;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}
</style>

<script>
function openTab(event, tabName) {
    // Esconder todas as abas
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }

    // Remover active de todos os botões
    const tabButtons = document.getElementsByClassName('tab-button');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }

    // Mostrar aba selecionada
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
