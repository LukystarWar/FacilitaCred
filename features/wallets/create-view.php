<?php
require_once __DIR__ . '/../../core/Session.php';

Session::requireAuth();

$pageTitle = 'Nova Carteira';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/wallets" class="btn-back">← Voltar</a>
        <h1>Nova Carteira</h1>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/wallets/create">
        <div class="form-group">
            <label for="name">Nome da Carteira *</label>
            <input type="text" id="name" name="name" required placeholder="Ex: Caixa Principal" autofocus>
        </div>

        <div class="form-group">
            <label for="initial_balance">Saldo Inicial</label>
            <input type="number" id="initial_balance" name="initial_balance" step="0.01" min="0" value="0" placeholder="0,00">
            <small>Deixe zero se não houver saldo inicial</small>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/wallets" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Criar Carteira</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
