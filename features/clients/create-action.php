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

$name = trim($_POST['name'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($name)) {
    Session::setFlash('error', 'O nome do cliente é obrigatório');
    header('Location: ' . BASE_URL . '/clients');
    exit;
}

$cpf = preg_replace('/[^0-9]/', '', $cpf);
$phone = preg_replace('/[^0-9]/', '', $phone);

$result = $clientService->createClient(
    $userId,
    $name,
    $cpf ?: null,
    $phone ?: null,
    $address ?: null
);

if ($result['success']) {
    Session::setFlash('success', 'Cliente cadastrado com sucesso!');
} else {
    Session::setFlash('error', $result['error'] ?? 'Erro ao cadastrar cliente');
}

header('Location: ' . BASE_URL . '/clients');
exit;
