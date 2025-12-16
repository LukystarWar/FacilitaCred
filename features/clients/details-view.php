<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/client-service.php';

Session::requireAuth();

$clientService = new ClientService();
$userId = Session::get('user_id');

$clientId = intval($_GET['id'] ?? 0);

if ($clientId <= 0) {
    Session::setFlash('error', 'Cliente inválido');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$client = $clientService->getClientById($clientId, $userId);

if (!$client) {
    Session::setFlash('error', 'Cliente não encontrado');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$loans = $clientService->getClientLoans($clientId, $userId);

// Calcular dívida ativa total (apenas parcelas pendentes/atrasadas)
$db = Database::getInstance()->getConnection();
$stmtDebt = $db->prepare("
    SELECT COALESCE(SUM(i.amount), 0) as active_debt
    FROM loan_installments i
    INNER JOIN loans l ON i.loan_id = l.id
    WHERE l.client_id = :client_id
      AND l.status = 'active'
      AND i.status IN ('pending', 'overdue')
");
$stmtDebt->execute(['client_id' => $clientId]);
$debtResult = $stmtDebt->fetch(PDO::FETCH_ASSOC);
$activeTotalDebt = $debtResult['active_debt'];

$pageTitle = 'Detalhes do Cliente';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/clients" class="btn-back">← Voltar</a>
        <h1><?= htmlspecialchars($client['name']) ?></h1>
        <?php if ($client['cpf']): ?>
            <p class="page-subtitle">CPF: <?= $clientService->formatCPF($client['cpf']) ?></p>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/clients/edit?id=<?= $client['id'] ?>" class="btn btn-secondary">
            ✏️ Editar
        </a>
        <a href="<?= BASE_URL ?>/loans/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">
            + Novo Empréstimo
        </a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count($loans) ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Empréstimos</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count(array_filter($loans, fn($l) => $l['status'] === 'active')) ?></div>
        <div class="stat-label" style="color: #6b7280;">Empréstimos Ativos</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($activeTotalDebt, 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Dívida Ativa Total</div>
        <small style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Apenas parcelas pendentes</small>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h2>Informações do Cliente</h2>
        </div>
        <div style="display: grid; gap: 1rem;">
            <div>
                <strong>Nome Completo:</strong><br>
                <?= htmlspecialchars($client['name']) ?>
            </div>

            <?php if ($client['cpf']): ?>
                <div>
                    <strong>CPF:</strong><br>
                    <?= $clientService->formatCPF($client['cpf']) ?>
                </div>
            <?php endif; ?>

            <?php if ($client['phone']): ?>
                <div>
                    <strong>Telefone:</strong><br>
                    <?= $clientService->formatPhone($client['phone']) ?>
                </div>
            <?php endif; ?>

            <?php if ($client['address']): ?>
                <div>
                    <strong>Endereço:</strong><br>
                    <?= nl2br(htmlspecialchars($client['address'])) ?>
                </div>
            <?php endif; ?>

            <div>
                <strong>Cadastrado em:</strong><br>
                <?= date('d/m/Y H:i', strtotime($client['created_at'])) ?>
            </div>

            <?php if ($client['updated_at'] != $client['created_at']): ?>
                <div>
                    <strong>Última atualização:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($client['updated_at'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Resumo Financeiro</h2>
        </div>
        <div style="display: grid; gap: 1rem;">
            <?php
            $totalEmprestado = array_sum(array_column($loans, 'amount'));
            $totalComJuros = array_sum(array_column($loans, 'total_amount'));
            $totalJuros = $totalComJuros - $totalEmprestado;
            $totalPago = 0;
            foreach ($loans as $loan) {
                $totalPago += $loan['paid_installments'] * ($loan['total_amount'] / $loan['total_installments']);
            }
            $totalPendente = $totalComJuros - $totalPago;
            ?>

            <div>
                <strong>Total Emprestado:</strong><br>
                <span style="font-size: 1.25rem; color: #2563eb;">R$ <?= number_format($totalEmprestado, 2, ',', '.') ?></span>
            </div>

            <div>
                <strong>Total em Juros:</strong><br>
                <span style="font-size: 1.25rem; color: #f59e0b;">R$ <?= number_format($totalJuros, 2, ',', '.') ?></span>
            </div>

            <div>
                <strong>Total Pago:</strong><br>
                <span style="font-size: 1.25rem; color: #10b981;">R$ <?= number_format($totalPago, 2, ',', '.') ?></span>
            </div>

            <div>
                <strong>Total Pendente:</strong><br>
                <span style="font-size: 1.25rem; color: #ef4444;">R$ <?= number_format($totalPendente, 2, ',', '.') ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Histórico de Empréstimos</h2>
    </div>

    <?php if (empty($loans)): ?>
        <div class="empty-state">
            <div class="empty-icon">💵</div>
            <h3>Nenhum empréstimo registrado</h3>
            <p>Este cliente ainda não possui empréstimos.</p>
            <a href="<?= BASE_URL ?>/loans/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">
                + Criar Primeiro Empréstimo
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Juros</th>
                        <th>Total</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($loan['created_at'])) ?></td>
                            <td>R$ <?= number_format($loan['amount'], 2, ',', '.') ?></td>
                            <td><?= number_format($loan['interest_rate'], 0) ?>% (R$ <?= number_format($loan['interest_amount'], 2, ',', '.') ?>)</td>
                            <td><strong>R$ <?= number_format($loan['total_amount'], 2, ',', '.') ?></strong></td>
                            <td>
                                <?= $loan['paid_installments'] ?>/<?= $loan['total_installments'] ?>
                                <?php if ($loan['overdue_installments'] > 0): ?>
                                    <br><span class="badge badge-danger"><?= $loan['overdue_installments'] ?> em atraso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($loan['status'] === 'active'): ?>
                                    <span class="badge badge-info">Ativo</span>
                                <?php elseif ($loan['status'] === 'paid'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Atrasado</span>
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
    <?php endif; ?>
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
    margin-bottom: 2rem;
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
