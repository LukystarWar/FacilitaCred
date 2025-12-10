<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/client-service.php';

Session::requireAuth();

$clientService = new ClientService();
$userId = Session::get('user_id');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    Session::setFlash('error', 'Cliente inválido');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$client = $clientService->getClientById($id, $userId);
if (!$client) {
    Session::setFlash('error', 'Cliente não encontrado');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$result = $clientService->deleteClient($id, $userId);

if ($result['success']) {
    Session::setFlash('success', 'Cliente excluído com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao excluir cliente');
}

header('Location: ' . BASE_URL . '/clients');
exit;
