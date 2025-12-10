<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/template-service.php';

Session::requireAuth();

$templateService = new WhatsAppTemplateService();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    Session::setFlash('error', 'Template inválido.');
    header('Location: ' . BASE_URL . '/whatsapp/templates');
    exit;
}

$success = $templateService->deleteTemplate($id);

if ($success) {
    Session::setFlash('success', '✅ Template excluído com sucesso!');
} else {
    Session::setFlash('error', 'Erro ao excluir template. Tente novamente.');
}

header('Location: ' . BASE_URL . '/whatsapp/templates');
exit;
