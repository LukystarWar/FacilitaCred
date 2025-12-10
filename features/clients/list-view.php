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
    <button class="btn btn-primary" onclick="openCreateModal()">
        + Novo Cliente
    </button>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-value"><?= count($clients) ?></div>
        <div class="stat-label">Total de Clientes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count(array_filter($clients, fn($c) => $c['loan_count'] > 0)) ?></div>
        <div class="stat-label">Com Empréstimos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">R$ <?= number_format(array_sum(array_column($clients, 'active_debt')), 2, ',', '.') ?></div>
        <div class="stat-label">Dívida Total Ativa</div>
    </div>
</div>

<?php if (empty($clients)): ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>Nenhum cliente cadastrado</h3>
        <p>Comece adicionando seu primeiro cliente para gerenciar empréstimos.</p>
        <button class="btn btn-primary" onclick="openCreateModal()">
            + Adicionar Primeiro Cliente
        </button>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Lista de Clientes</h2>
            <input type="text" id="searchInput" placeholder="🔍 Buscar por nome, CPF ou telefone..." style="max-width: 300px;" oninput="filterClients()">
        </div>

        <div class="table-responsive">
            <table class="table" id="clientsTable">
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
                                <?php if ($client['address']): ?>
                                    <br><small class="text-muted">📍 <?= htmlspecialchars(substr($client['address'], 0, 50)) ?><?= strlen($client['address']) > 50 ? '...' : '' ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $client['cpf'] ? $clientService->formatCPF($client['cpf']) : '-' ?></td>
                            <td><?= $client['phone'] ? $clientService->formatPhone($client['phone']) : '-' ?></td>
                            <td>
                                <?php if ($client['loan_count'] > 0): ?>
                                    <span class="badge badge-info"><?= $client['loan_count'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['active_debt'] > 0): ?>
                                    <strong style="color: #e74c3c;">R$ <?= number_format($client['active_debt'], 2, ',', '.') ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/clients/<?= $client['id'] ?>" class="btn btn-sm btn-outline" title="Ver detalhes">
                                        👁️
                                    </a>
                                    <button class="btn btn-sm btn-secondary" onclick="openEditModal(<?= $client['id'] ?>, <?= htmlspecialchars(json_encode($client)) ?>)" title="Editar">
                                        ✏️
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name']) ?>')" title="Excluir">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal: Criar Cliente -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Novo Cliente</h2>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/clients/create">
            <div class="form-group">
                <label for="name">Nome Completo *</label>
                <input type="text" id="name" name="name" required placeholder="Ex: João da Silva">
            </div>

            <div class="form-group">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" oninput="maskCPF(this)">
                <small>Opcional - será validado se preenchido</small>
            </div>

            <div class="form-group">
                <label for="phone">Telefone</label>
                <input type="text" id="phone" name="phone" maxlength="15" placeholder="(00) 00000-0000" oninput="maskPhone(this)">
            </div>

            <div class="form-group">
                <label for="address">Endereço</label>
                <textarea id="address" name="address" rows="3" placeholder="Rua, número, bairro, cidade"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Cliente -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Cliente</h2>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/clients/update">
            <input type="hidden" id="edit_id" name="id">

            <div class="form-group">
                <label for="edit_name">Nome Completo *</label>
                <input type="text" id="edit_name" name="name" required>
            </div>

            <div class="form-group">
                <label for="edit_cpf">CPF</label>
                <input type="text" id="edit_cpf" name="cpf" maxlength="14" oninput="maskCPF(this)">
            </div>

            <div class="form-group">
                <label for="edit_phone">Telefone</label>
                <input type="text" id="edit_phone" name="phone" maxlength="15" oninput="maskPhone(this)">
            </div>

            <div class="form-group">
                <label for="edit_address">Endereço</label>
                <textarea id="edit_address" name="address" rows="3"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').style.display = 'flex';
}

function openEditModal(id, client) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = client.name;
    document.getElementById('edit_cpf').value = client.cpf || '';
    document.getElementById('edit_phone').value = client.phone || '';
    document.getElementById('edit_address').value = client.address || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmDelete(id, name) {
    if (confirm(`Tem certeza que deseja excluir o cliente "${name}"?\n\nATENÇÃO: Só é possível excluir clientes sem empréstimos.`)) {
        window.location.href = '<?= BASE_URL ?>/clients/delete?id=' + id;
    }
}

function maskCPF(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substr(0, 11);

    if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }

    input.value = value;
}

function maskPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substr(0, 11);

    if (value.length > 10) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (value.length > 6) {
        value = value.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    }

    input.value = value;
}

function filterClients() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('clientsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
