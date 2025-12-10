<?php
/**
 * Database Seeder
 * Popula o banco com dados de exemplo para testes
 *
 * Uso: php database/seed.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

echo "🌱 Iniciando população do banco de dados...\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Limpar dados existentes (exceto usuário admin)
    echo "🗑️  Limpando dados existentes...\n";
    $db->exec("DELETE FROM loan_installments");
    $db->exec("DELETE FROM loans");
    $db->exec("DELETE FROM wallet_transactions");
    $db->exec("DELETE FROM wallets");
    $db->exec("DELETE FROM clients");
    echo "✅ Dados limpos!\n\n";

    // ====================================
    // CLIENTES
    // ====================================
    echo "👥 Gerando 50 clientes...\n";

    $firstNames = ['João', 'Maria', 'José', 'Ana', 'Pedro', 'Mariana', 'Carlos', 'Juliana', 'Paulo', 'Fernanda',
                   'Lucas', 'Camila', 'Rafael', 'Beatriz', 'Felipe', 'Larissa', 'Marcos', 'Patricia', 'Bruno', 'Carla',
                   'Ricardo', 'Amanda', 'Diego', 'Gabriela', 'Thiago', 'Letícia', 'Rodrigo', 'Aline', 'Gustavo', 'Renata',
                   'André', 'Vanessa', 'Leandro', 'Cristina', 'Marcelo', 'Sandra', 'Fábio', 'Simone', 'Roberto', 'Adriana',
                   'Fernando', 'Mônica', 'Vinícius', 'Tatiana', 'Alexandre', 'Daniela', 'Henrique', 'Priscila', 'Sérgio', 'Claudia'];

    $lastNames = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Lima', 'Pereira', 'Costa', 'Rodrigues', 'Almeida', 'Nascimento',
                  'Ferreira', 'Carvalho', 'Gomes', 'Martins', 'Rocha', 'Ribeiro', 'Alves', 'Monteiro', 'Mendes', 'Cardoso',
                  'Araújo', 'Barbosa', 'Dias', 'Cavalcanti', 'Fernandes', 'Freitas', 'Pinto', 'Castro', 'Teixeira', 'Moreira'];

    $streets = ['Rua das Flores', 'Av. Paulista', 'Rua XV de Novembro', 'Av. Brasil', 'Rua do Comércio',
                'Av. Getúlio Vargas', 'Rua Santos Dumont', 'Av. Tiradentes', 'Rua Sete de Setembro', 'Av. Presidente Vargas'];

    $cities = ['São Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Curitiba', 'Porto Alegre',
               'Salvador', 'Brasília', 'Fortaleza', 'Recife', 'Manaus'];

    $clientIds = [];

    for ($i = 1; $i <= 50; $i++) {
        $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        $cpf = sprintf('%03d.%03d.%03d-%02d', rand(100, 999), rand(100, 999), rand(100, 999), rand(10, 99));
        $phone = sprintf('(%02d) %d%04d-%04d', rand(11, 99), rand(9, 9), rand(1000, 9999), rand(1000, 9999));
        $street = $streets[array_rand($streets)];
        $number = rand(10, 9999);
        $city = $cities[array_rand($cities)];
        $address = "$street, $number - $city/SP";

        $stmt = $db->prepare("INSERT INTO clients (name, cpf, phone, address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $cpf, $phone, $address]);
        $clientIds[] = $db->lastInsertId();
    }

    echo "✅ 50 clientes criados!\n\n";

    // ====================================
    // CARTEIRAS
    // ====================================
    echo "💰 Gerando carteiras...\n";

    $walletNames = ['Caixa Principal', 'Banco Bradesco', 'Banco Itaú', 'Nubank', 'Caixa Econômica'];
    $walletIds = [];

    foreach ($walletNames as $walletName) {
        $initialBalance = rand(5000, 50000);

        $stmt = $db->prepare("INSERT INTO wallets (name, balance, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$walletName, $initialBalance]);
        $walletIds[] = $db->lastInsertId();
    }

    echo "✅ " . count($walletIds) . " carteiras criadas!\n\n";

    // ====================================
    // TRANSAÇÕES DE CARTEIRA
    // ====================================
    echo "💸 Gerando transações de carteira...\n";

    $transactionCount = 0;
    foreach ($walletIds as $walletId) {
        // Deposits
        for ($i = 0; $i < rand(5, 15); $i++) {
            $amount = rand(500, 10000);
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
            $descriptions = ['Depósito inicial', 'Recebimento de pagamento', 'Transferência recebida', 'Aporte de capital'];

            $stmt = $db->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, created_at) VALUES (?, 'deposit', ?, ?, ?)");
            $stmt->execute([$walletId, $amount, $descriptions[array_rand($descriptions)], $date]);
            $transactionCount++;
        }

        // Withdrawals
        for ($i = 0; $i < rand(3, 10); $i++) {
            $amount = rand(200, 5000);
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
            $descriptions = ['Saque para despesas', 'Pagamento de fornecedor', 'Retirada para investimento', 'Transferência externa'];

            $stmt = $db->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, created_at) VALUES (?, 'withdrawal', ?, ?, ?)");
            $stmt->execute([$walletId, $amount, $descriptions[array_rand($descriptions)], $date]);
            $transactionCount++;
        }
    }

    echo "✅ $transactionCount transações de carteira criadas!\n\n";

    // ====================================
    // EMPRÉSTIMOS
    // ====================================
    echo "💵 Gerando 60 empréstimos...\n";

    $loanCount = 0;
    $installmentCount = 0;

    for ($i = 0; $i < 60; $i++) {
        $clientId = $clientIds[array_rand($clientIds)];
        $walletId = $walletIds[array_rand($walletIds)];

        $amount = rand(1000, 50000);
        $interestRate = rand(5, 30); // 5% a 30%
        $installments = [6, 10, 12, 18, 24][array_rand([6, 10, 12, 18, 24])];

        $interestAmount = ($amount * $interestRate) / 100;
        $totalAmount = $amount + $interestAmount;
        $installmentAmount = $totalAmount / $installments;

        $daysAgo = rand(10, 180);
        $loanDate = date('Y-m-d', strtotime("-$daysAgo days"));
        $firstDueDate = date('Y-m-d', strtotime($loanDate . ' +30 days'));

        // Status: 70% ativos, 30% pagos
        $status = (rand(1, 100) <= 70) ? 'active' : 'paid';

        $stmt = $db->prepare("
            INSERT INTO loans (client_id, wallet_id, amount, interest_rate, interest_amount, total_amount,
                               installments_count, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId, $walletId, $amount, $interestRate, $interestAmount,
            $totalAmount, $installments, $status, $loanDate
        ]);

        $loanId = $db->lastInsertId();
        $loanCount++;

        // Criar parcelas
        for ($j = 1; $j <= $installments; $j++) {
            $dueDate = date('Y-m-d', strtotime($firstDueDate . ' +' . ($j - 1) . ' months'));

            // Determinar status da parcela
            $today = date('Y-m-d');
            if ($status === 'paid') {
                $installmentStatus = 'paid';
                $paymentDate = date('Y-m-d', strtotime($dueDate . ' +' . rand(-5, 5) . ' days'));
            } else {
                // Empréstimo ativo
                if ($dueDate < $today) {
                    // Vencida - 60% paga, 40% atrasada
                    if (rand(1, 100) <= 60) {
                        $installmentStatus = 'paid';
                        $paymentDate = date('Y-m-d', strtotime($dueDate . ' +' . rand(0, 15) . ' days'));
                    } else {
                        $installmentStatus = 'overdue';
                        $paymentDate = null;
                    }
                } else {
                    $installmentStatus = 'pending';
                    $paymentDate = null;
                }
            }

            $stmt = $db->prepare("
                INSERT INTO loan_installments (loan_id, installment_number, amount, due_date, status, paid_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$loanId, $j, $installmentAmount, $dueDate, $installmentStatus, $paymentDate]);
            $installmentCount++;

            // Registrar transação de empréstimo desembolsado (apenas na primeira parcela)
            if ($j === 1) {
                $stmt = $db->prepare("
                    INSERT INTO wallet_transactions (wallet_id, type, amount, description, created_at)
                    VALUES (?, 'loan_out', ?, ?, ?)
                ");
                $stmt->execute([
                    $walletId,
                    $amount,
                    "Empréstimo #$loanId desembolsado",
                    $loanDate
                ]);
            }

            // Registrar transação de pagamento
            if ($installmentStatus === 'paid' && $paymentDate) {
                $stmt = $db->prepare("
                    INSERT INTO wallet_transactions (wallet_id, type, amount, description, created_at)
                    VALUES (?, 'loan_payment', ?, ?, ?)
                ");
                $stmt->execute([
                    $walletId,
                    $installmentAmount,
                    "Pagamento parcela #$j - Empréstimo #$loanId",
                    $paymentDate
                ]);
            }
        }
    }

    echo "✅ $loanCount empréstimos criados!\n";
    echo "✅ $installmentCount parcelas criadas!\n\n";

    // ====================================
    // ATUALIZAR STATUS DOS EMPRÉSTIMOS
    // ====================================
    echo "🔄 Atualizando status dos empréstimos...\n";

    // Atualizar status dos empréstimos baseado nas parcelas
    $db->exec("
        UPDATE loans l
        SET status = 'paid'
        WHERE l.id IN (
            SELECT loan_id FROM (
                SELECT loan_id, COUNT(*) as total,
                       SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
                FROM loan_installments
                GROUP BY loan_id
                HAVING total = paid
            ) subquery
        )
        AND l.status = 'active'
    ");

    echo "✅ Status dos empréstimos atualizado!\n\n";

    // ====================================
    // RESUMO
    // ====================================
    echo "📊 RESUMO DA POPULAÇÃO:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $counts = [
        'Clientes' => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
        'Carteiras' => $db->query("SELECT COUNT(*) FROM wallets")->fetchColumn(),
        'Transações de Carteira' => $db->query("SELECT COUNT(*) FROM wallet_transactions")->fetchColumn(),
        'Empréstimos' => $db->query("SELECT COUNT(*) FROM loans")->fetchColumn(),
        'Empréstimos Ativos' => $db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
        'Empréstimos Pagos' => $db->query("SELECT COUNT(*) FROM loans WHERE status = 'paid'")->fetchColumn(),
        'Parcelas' => $db->query("SELECT COUNT(*) FROM loan_installments")->fetchColumn(),
        'Parcelas Pagas' => $db->query("SELECT COUNT(*) FROM loan_installments WHERE status = 'paid'")->fetchColumn(),
        'Parcelas Atrasadas' => $db->query("SELECT COUNT(*) FROM loan_installments WHERE status = 'overdue'")->fetchColumn(),
    ];

    foreach ($counts as $label => $count) {
        echo str_pad($label . ':', 30) . str_pad($count, 10, ' ', STR_PAD_LEFT) . "\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n✨ Banco de dados populado com sucesso!\n";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
