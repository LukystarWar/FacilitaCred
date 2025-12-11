<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/client-service.php';

Session::requireAuth();

$clientService = new ClientService();

// Capturar busca e página
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

$result = $clientService->getAllClients(Session::get('user_id'), $search, $page, $perPage);
$clients = $result['data'];
$pagination = $result['pagination'];

$pageTitle = 'Clientes';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Clientes</h1>
    <a href="<?= BASE_URL ?>/clients/create" class="btn btn-primary">
        + Novo Cliente
    </a>
</div>

<!-- Busca -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" action="<?= BASE_URL ?>/clients" style="padding: 1rem; display: flex; gap: 0.75rem; align-items: center;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="🔍 Buscar por nome, CPF ou telefone..."
               class="form-control" style="flex: 1;">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if($search): ?>
            <a href="<?= BASE_URL ?>/clients" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= $pagination['total'] ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Clientes</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count(array_filter($clients, fn($c) => ($c['loan_count'] ?? 0) > 0)) ?></div>
        <div class="stat-label" style="color: #6b7280;">Com Empréstimos (página)</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format(array_sum(array_column($clients, 'active_debt')), 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Dívida Total Ativa (página)</div>
    </div>
</div>

<?php if (empty($clients)): ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>Nenhum cliente encontrado</h3>
        <p><?= $search ? 'Tente buscar com outros termos.' : 'Comece adicionando seu primeiro cliente.' ?></p>
        <?php if (!$search): ?>
            <a href="<?= BASE_URL ?>/clients/create" class="btn btn-primary">
                + Adicionar Primeiro Cliente
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Lista de Clientes</h2>
            <span style="color: #6b7280; font-size: 0.875rem;">
                Mostrando <?= count($clients) ?> de <?= $pagination['total'] ?> clientes
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Empréstimos</th>
                        <th>Dívida Ativa</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($client['name']) ?></strong></td>
                            <td><?= $client['cpf'] ? $clientService->formatCPF($client['cpf']) : '-' ?></td>
                            <td><?= $client['phone'] ? $clientService->formatPhone($client['phone']) : '-' ?></td>
                            <td><?= $client['loan_count'] ?? 0 ?></td>
                            <td>
                                <?php if ($client['active_debt'] > 0): ?>
                                    <span style="color: #dc3545; font-weight: 600;">
                                        R$ <?= number_format($client['active_debt'], 2, ',', '.') ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <a href="<?= BASE_URL ?>/clients/<?= $client['id'] ?>"
                                       title="Ver detalhes">
                                        <img src="<?= ASSETS_URL ?>/images/ver.png" alt="Ver" style="width: 20px; height: 20px; cursor: pointer;">
                                    </a>
                                    <a href="<?= BASE_URL ?>/clients/edit?id=<?= $client['id'] ?>"
                                       title="Editar">
                                        <img src="<?= ASSETS_URL ?>/images/editar.png" alt="Editar" style="width: 20px; height: 20px; cursor: pointer;">
                                    </a>
                                    <a href="<?= BASE_URL ?>/clients/delete?id=<?= $client['id'] ?>"
                                       onclick="return confirm('Tem certeza que deseja excluir este cliente?')"
                                       title="Excluir">
                                        <img src="<?= ASSETS_URL ?>/images/excluir.png" alt="Excluir" style="width: 20px; height: 20px; cursor: pointer;">
                                    </a>
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
                $buildUrl = function($page) use ($search) {
                    $params = [];
                    if ($search) $params['search'] = $search;
                    $params['page'] = $page;
                    return BASE_URL . '/clients?' . http_build_query($params);
                };
                ?>

                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= $buildUrl(1) ?>" class="btn btn-sm btn-outline">« Primeira</a>
                    <a href="<?= $buildUrl($pagination['current_page'] - 1) ?>" class="btn btn-sm btn-outline">‹ Anterior</a>
                <?php endif; ?>

                <?php
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
