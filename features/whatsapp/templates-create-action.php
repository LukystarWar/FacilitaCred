<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/template-service.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/whatsapp/templates');
    exit;
}

$templateService = new WhatsAppTemplateService();

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$message = trim($_POST['message'] ?? '');
$category = $_POST['category'] ?? '';
$isActive = intval($_POST['is_active'] ?? 1);

// Validações
if (empty($name) || empty($message) || empty($category)) {
    Session::setFlash('error', 'Por favor, preencha todos os campos obrigatórios.');
    header('Location: ' . BASE_URL . '/whatsapp/templates/create');
    exit;
}

if (!in_array($category, ['cobranca', 'lembrete', 'confirmacao', 'outros'])) {
    Session::setFlash('error', 'Categoria inválida.');
    header('Location: ' . BASE_URL . '/whatsapp/templates/create');
    exit;
}

$success = $templateService->createTemplate($name, $description, $message, $category, $isActive);

if ($success) {
    Session::setFlash('success', '✅ Template criado com sucesso!');
} else {
    Session::setFlash('error', 'Erro ao criar template. Tente novamente.');
}

header('Location: ' . BASE_URL . '/whatsapp/templates');
exit;
