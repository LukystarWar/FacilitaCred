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

$pageTitle = 'Editar Cliente';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/clients" class="btn-back">← Voltar</a>
        <h1>Editar Cliente</h1>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/clients/update">
        <input type="hidden" name="id" value="<?= $client['id'] ?>">

        <div class="form-group">
            <label for="name">Nome Completo *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($client['name']) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($client['cpf'] ?? '') ?>" placeholder="000.000.000-00" maxlength="14">
            <small>Opcional - Formato: 000.000.000-00</small>
        </div>

        <div class="form-group">
            <label for="phone">Telefone</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($client['phone'] ?? '') ?>" placeholder="(00) 00000-0000" maxlength="15">
            <small>Opcional - Formato: (00) 00000-0000</small>
        </div>

        <div class="form-group">
            <label for="address">Endereço</label>
            <textarea id="address" name="address" rows="3" placeholder="Rua, número, bairro, cidade..."><?= htmlspecialchars($client['address'] ?? '') ?></textarea>
            <small>Opcional</small>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/clients" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
