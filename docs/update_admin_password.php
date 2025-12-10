<?php
/**
 * Script para atualizar a senha do usuário admin
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Gerar novo hash para 'admin123'
    $new_password = 'admin123';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    echo "Gerando novo hash para senha: $new_password\n";
    echo "Hash gerado: $new_hash\n\n";

    // Atualizar no banco
    $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
    $stmt->execute(['hash' => $new_hash]);

    echo "✅ Senha atualizada com sucesso!\n\n";

    // Verificar
    $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Verificando no banco:\n";
    echo "ID: {$user['id']}\n";
    echo "Username: {$user['username']}\n";
    echo "Hash: {$user['password_hash']}\n\n";

    // Testar login
    if (password_verify($new_password, $user['password_hash'])) {
        echo "✅ TESTE DE LOGIN: SUCESSO!\n";
        echo "\nCredenciais para login:\n";
        echo "Usuário: admin\n";
        echo "Senha: admin123\n";
    } else {
        echo "❌ TESTE DE LOGIN: FALHOU!\n";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
