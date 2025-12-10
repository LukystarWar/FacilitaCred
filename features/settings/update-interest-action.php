<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/settings-service.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/settings');
    exit;
}

$settingsService = new SettingsService();
$userId = Session::get('user_id');

try {
    $settings = [
        'interest_rate_single_payment' => floatval($_POST['interest_rate_single_payment'] ?? 0),
        'interest_rate_installment' => floatval($_POST['interest_rate_installment'] ?? 0)
    ];

    // Validações
    if ($settings['interest_rate_single_payment'] < 0 || $settings['interest_rate_single_payment'] > 100) {
        Session::setFlash('error', 'Taxa de juros à vista deve estar entre 0% e 100%');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    if ($settings['interest_rate_installment'] < 0 || $settings['interest_rate_installment'] > 100) {
        Session::setFlash('error', 'Taxa de juros ao mês deve estar entre 0% e 100%');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    $success = $settingsService->updateMultiple($settings, $userId);

    if ($success) {
        Session::setFlash('success', '✅ Configurações de juros atualizadas com sucesso!');
    } else {
        Session::setFlash('error', 'Erro ao atualizar configurações de juros');
    }
} catch (Exception $e) {
    Session::setFlash('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/settings');
exit;
