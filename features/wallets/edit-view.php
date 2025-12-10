<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/wallet-service.php';

Session::requireAuth();

$walletService = new WalletService();
$userId = Session::get('user_id');
$walletId = intval($_GET['id'] ?? 0);

if ($walletId <= 0) {
    Session::setFlash('error', 'Carteira inválida');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

$wallet = $walletService->getWalletById($walletId, $userId);

if (!$wallet) {
    Session::setFlash('error', 'Carteira não encontrada');
    header('Location: ' . BASE_URL . '/wallets');
    exit;
}

$pageTitle = 'Editar Carteira';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <div>
        <a href="<?= BASE_URL ?>/wallets" class="btn-back">← Voltar</a>
        <h1>Editar Carteira</h1>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?= BASE_URL ?>/wallets/update">
        <input type="hidden" name="id" value="<?= $wallet['id'] ?>">

        <div class="form-group">
            <label for="name">Nome da Carteira *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($wallet['name']) ?>" required autofocus>
        </div>

        <div class="alert alert-info">
            <strong>Saldo atual:</strong> R$ <?= number_format($wallet['balance'], 2, ',', '.') ?>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/wallets" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
