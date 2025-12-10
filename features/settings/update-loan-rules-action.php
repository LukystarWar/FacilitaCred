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
        'min_loan_amount' => floatval($_POST['min_loan_amount'] ?? 0),
        'max_loan_amount' => floatval($_POST['max_loan_amount'] ?? 0),
        'max_installments' => intval($_POST['max_installments'] ?? 0)
    ];

    // Validações
    if ($settings['min_loan_amount'] < 0) {
        Session::setFlash('error', 'Valor mínimo de empréstimo não pode ser negativo');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    if ($settings['max_loan_amount'] < $settings['min_loan_amount']) {
        Session::setFlash('error', 'Valor máximo de empréstimo deve ser maior que o valor mínimo');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    if ($settings['max_installments'] < 1 || $settings['max_installments'] > 100) {
        Session::setFlash('error', 'Número máximo de parcelas deve estar entre 1 e 100');
        header('Location: ' . BASE_URL . '/settings');
        exit;
    }

    $success = $settingsService->updateMultiple($settings, $userId);

    if ($success) {
        Session::setFlash('success', '✅ Regras de empréstimos atualizadas com sucesso!');
    } else {
        Session::setFlash('error', 'Erro ao atualizar regras de empréstimos');
    }
} catch (Exception $e) {
    Session::setFlash('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/settings');
exit;
