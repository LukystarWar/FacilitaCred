<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/client-service.php';

Session::requireAuth();

$clientService = new ClientService();
$clients = $clientService->getAllClients(Session::get('user_id'));

$pageTitle = 'Clientes';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Clientes</h1>
    <a href="<?= BASE_URL ?>/clients/create" class="btn btn-primary">
        + Novo Cliente
    </a>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="border-left: 4px solid #6b7280;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count($clients) ?></div>
        <div class="stat-label" style="color: #6b7280;">Total de Clientes</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #11C76F;">
        <div class="stat-value" style="color: #1C1C1C;"><?= count(array_filter($clients, fn($c) => ($c['loan_count'] ?? 0) > 0)) ?></div>
        <div class="stat-label" style="color: #6b7280;">Com Empréstimos</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #EA580C;">
        <div class="stat-value" style="color: #1C1C1C;">R$ <?= number_format(array_sum(array_column($clients, 'active_debt')), 2, ',', '.') ?></div>
        <div class="stat-label" style="color: #6b7280;">Dívida Total Ativa</div>
    </div>
</div>

<?php if (empty($clients)): ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>Nenhum cliente cadastrado</h3>
        <p>Comece adicionando seu primeiro cliente para gerenciar empréstimos.</p>
        <a href="<?= BASE_URL ?>/clients/create" class="btn btn-primary">
            + Adicionar Primeiro Cliente
        </a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Lista de Clientes</h2>
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
                            <td>
                                <strong><?= htmlspecialchars($client['name']) ?></strong>
                            </td>
                            <td><?= $client['cpf'] ? htmlspecialchars($client['cpf']) : '-' ?></td>
                            <td><?= $client['phone'] ? htmlspecialchars($client['phone']) : '-' ?></td>
                            <td>
                                <?php if (($client['loan_count'] ?? 0) > 0): ?>
                                    <span class="badge badge-info"><?= $client['loan_count'] ?> ativo(s)</span>
                                <?php else: ?>
                                    <span class="text-muted">Nenhum</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['active_debt'] > 0): ?>
                                    <strong style="color: #e74c3c;">R$ <?= number_format($client['active_debt'], 2, ',', '.') ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">R$ 0,00</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="action-buttons">
                                    <a href="<?= BASE_URL ?>/clients/<?= $client['id'] ?>" class="btn btn-sm btn-outline" title="Ver Detalhes">
                                        👁️ Ver
                                    </a>
                                    <a href="<?= BASE_URL ?>/clients/edit?id=<?= $client['id'] ?>" class="btn btn-sm btn-secondary" title="Editar">
                                        ✏️ Editar
                                    </a>
                                    <a href="<?= BASE_URL ?>/clients/delete?id=<?= $client['id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Tem certeza que deseja excluir o cliente \'<?= htmlspecialchars($client['name']) ?>\'?\n\nATENÇÃO: Só é possível excluir clientes sem empréstimos.')"
                                       title="Excluir">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.action-buttons .btn {
    min-width: 80px;
}

@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
