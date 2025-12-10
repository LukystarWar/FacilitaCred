<?php
require_once __DIR__ . '/../../core/Session.php';

Session::requireAuth();

$pageTitle = 'Novo Cliente';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/clients" class="btn-back">← Voltar</a>
        <h1>Novo Cliente</h1>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/clients/create">
        <div class="form-group">
            <label for="name">Nome Completo *</label>
            <input type="text" id="name" name="name" required placeholder="Ex: João da Silva" autofocus>
        </div>

        <div class="form-group">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14">
            <small>Opcional - Formato: 000.000.000-00</small>
        </div>

        <div class="form-group">
            <label for="phone">Telefone</label>
            <input type="text" id="phone" name="phone" placeholder="(00) 00000-0000" maxlength="15">
            <small>Opcional - Formato: (00) 00000-0000</small>
        </div>

        <div class="form-group">
            <label for="address">Endereço</label>
            <textarea id="address" name="address" rows="3" placeholder="Rua, número, bairro, cidade..."></textarea>
            <small>Opcional</small>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/clients" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Cadastrar Cliente</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
