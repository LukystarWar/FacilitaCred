<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/client-service.php';

Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$clientService = new ClientService();
$userId = Session::get('user_id');

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($id <= 0) {
    Session::setFlash('error', 'Cliente inválido');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

if (empty($name)) {
    Session::setFlash('error', 'O nome do cliente é obrigatório');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$client = $clientService->getClientById($id, $userId);
if (!$client) {
    Session::setFlash('error', 'Cliente não encontrado');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$cpf = preg_replace('/[^0-9]/', '', $cpf);
$phone = preg_replace('/[^0-9]/', '', $phone);

$result = $clientService->updateClient(
    $id,
    $userId,
    $name,
    $cpf ?: null,
    $phone ?: null,
    $address ?: null
);

if ($result['success']) {
    Session::setFlash('success', 'Cliente atualizado com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao atualizar cliente');
}

header('Location: ' . BASE_URL . '/clients');
exit;
