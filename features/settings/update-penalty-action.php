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
        'grace_period_days' => intval($_POST['grace_period_days'] ?? 0),
        'late_fee_percentage' => floatval($_POST['late_fee_percentage'] ?? 0)
    ];

    // Validações
    if ($settings['grace_period_days'] < 0 || $settings['grace_period_days'] > 30) {
        Session::setFlash('error', 'Período de carência deve estar entre 0 e 30 dias');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    if ($settings['late_fee_percentage'] < 0 || $settings['late_fee_percentage'] > 100) {
        Session::setFlash('error', 'Taxa de juros por atraso deve estar entre 0% e 100%');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    $success = $settingsService->updateMultiple($settings, $userId);

    if ($success) {
        Session::setFlash('success', '✅ Configurações de carência e multas atualizadas com sucesso!');
    } else {
        Session::setFlash('error', 'Erro ao atualizar configurações de carência e multas');
    }
} catch (Exception $e) {
    Session::setFlash('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/settings');
exit;
