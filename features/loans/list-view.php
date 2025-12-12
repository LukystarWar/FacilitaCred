<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/loan-service.php';
require_once __DIR__ . '/../clients/client-service.php';
require_once __DIR__ . '/../wallets/wallet-service.php';

Session::requireAuth();

$loanService = new LoanService();
$loanService->updateOverdueInstallments();

// Capturar filtros
$filters = [
    'status' => $_GET['status'] ?? '',
    'client_id' => $_GET['client_id'] ?? '',
    'wallet_id' => $_GET['wallet_id'] ?? '',
    'search' => $_GET['search'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? ''
];

// Capturar página atual
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

// Buscar empréstimos com filtros e paginação
$result = $loanService->getAllLoans(Session::get('user_id'), $filters, $page, $perPage);
$loans = $result['data'];
$pagination = $result['pagination'];

// Buscar estatísticas com os mesmos filtros
$stats = $loanService->getLoansStats(Session::get('user_id'), $filters);

// Buscar clientes e carteiras para os filtros
$clientService = new ClientService();
$walletService = new WalletService();
$clientsResult = $clientService->getAllClients(Session::get('user_id'), '', 1, 1000);
$clients = $clientsResult['data'];
$wallets = $walletService->getAllWallets(Session::get('user_id'));

$pageTitle = 'Empréstimos';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Empréstimos</h1>
    <a href="<?= BASE_URL ?>/loans/create" class="btn btn-primary">
        + Novo Empréstimo
    </a>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">🔍 Filtros</h3>
    </div>
    <form method="GET" action="<?= BASE_URL ?>/loans" style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                       placeholder="Nome ou CPF do cliente" class="form-control">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Ativos</option>
                    <option value="overdue" <?= $filters['status'] === 'overdue' ? 'selected' : '' ?>>Atrasados</option>
                    <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Pagos</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Cliente</label>
                <select name="client_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $filters['client_id'] == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Carteira</label>
                <select name="wallet_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($wallets as $wallet): ?>
                        <option value="<?= $wallet['id'] ?>" <?= $filters['wallet_id'] == $wallet['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wallet['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Data Inicial</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>" class="form-control">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Data Final</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>" class="form-control">
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= BASE_URL ?>/loans" class="btn btn-secondary">Limpar</a>
        </div>
    </form>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= $stats['total_loans'] ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Empréstimos</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;"><?= $stats['active_loans'] ?></div>
        <div class="stat-label" style="color: #6b7280;">Empréstimos Ativos</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($stats['total_emprestado'], 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Total Emprestado</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #0D9488;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format($stats['total_a_receber'], 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">A Receber</div>
    </div>
</div>

<?php if (empty($loans)): ?>
    <div class="empty-state">
        <div class="empty-icon">💵</div>
        <h3>Nenhum empréstimo registrado</h3>
        <p>Comece criando seu primeiro empréstimo.</p>
        <a href="<?= BASE_URL ?>/loans/create" class="btn btn-primary">
            + Criar Primeiro Empréstimo
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Lista de Empréstimos</h2>
            <span style="color: #6b7280; font-size: 0.875rem;">
                Mostrando <?= count($loans) ?> de <?= $pagination['total'] ?> empréstimos
            </span>
        </div>

        <div class="table-responsive">
            <table class="table" id="loansTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Carteira</th>
                        <th>Valor</th>
                        <th>Total + Juros</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($loan['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($loan['client_name']) ?></strong>
                                <?php if ($loan['overdue_installments'] > 0): ?>
                                    <br><span class="badge badge-danger"><?= $loan['overdue_installments'] ?> em atraso</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($loan['wallet_name']) ?></td>
                            <td>R$ <?= number_format($loan['amount'], 2, ',', '.') ?></td>
                            <td>
                                <strong>R$ <?= number_format($loan['total_amount'], 2, ',', '.') ?></strong>
                                <br><small class="text-muted"><?= number_format($loan['interest_rate'], 0) ?>% juros</small>
                            </td>
                            <td>
                                <?= $loan['paid_installments'] ?>/<?= $loan['total_installments'] ?>
                                <br><small class="text-muted">
                                    <?php
                                    $progress = $loan['total_installments'] > 0 ? ($loan['paid_installments'] / $loan['total_installments']) * 100 : 0;
                                    echo number_format($progress, 0) . '%';
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($loan['status'] === 'active'): ?>
                                    <?php if ($loan['overdue_installments'] > 0): ?>
                                        <span class="badge badge-danger">Com Atraso</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Ativo</span>
                                    <?php endif; ?>
                                <?php elseif ($loan['status'] === 'paid'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <a href="<?= BASE_URL ?>/loans/<?= $loan['id'] ?>" title="Ver detalhes">
                                        <img src="<?= ASSETS_URL ?>/images/ver.png" alt="Ver" style="width: 20px; height: 20px; cursor: pointer;">
                                    </a>
                                    <?php if ($loan['status'] === 'active'): ?>
                                        <a href="<?= BASE_URL ?>/loans/whatsapp?loan_id=<?= $loan['id'] ?>&template=<?= $loan['overdue_installments'] > 0 ? 'cobranca' : 'lembrete' ?>" title="Enviar WhatsApp" target="_blank">
                                            <img src="<?= ASSETS_URL ?>/images/whatsapp.png" alt="WhatsApp" style="width: 20px; height: 20px; cursor: pointer;">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
        <!-- Paginação -->
        <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #6b7280; font-size: 0.875rem;">
                Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php
                // Construir URL com filtros
                $queryParams = array_filter($filters);
                $buildUrl = function($page) use ($queryParams) {
                    $params = array_merge($queryParams, ['page' => $page]);
                    return BASE_URL . '/loans?' . http_build_query($params);
                };
                ?>

                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= $buildUrl(1) ?>" class="btn btn-sm btn-outline">« Primeira</a>
                    <a href="<?= $buildUrl($pagination['current_page'] - 1) ?>" class="btn btn-sm btn-outline">‹ Anterior</a>
                <?php endif; ?>

                <?php
                // Mostrar páginas próximas
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                    if ($i == $pagination['current_page']):
                ?>
                    <span class="btn btn-sm btn-primary"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $buildUrl($i) ?>" class="btn btn-sm btn-outline"><?= $i ?></a>
                <?php
                    endif;
                endfor;
                ?>

                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="<?= $buildUrl($pagination['current_page'] + 1) ?>" class="btn btn-sm btn-outline">Próxima ›</a>
                    <a href="<?= $buildUrl($pagination['total_pages']) ?>" class="btn btn-sm btn-outline">Última »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
