-- ============================================
-- Facilita Cred - Database Migration
-- Sistema de Gestão de Empréstimos
-- ============================================

-- Criar database
CREATE DATABASE IF NOT EXISTS facilita_cred CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE facilita_cred;

-- ============================================
-- Tabela de Usuários
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Carteiras
-- ============================================
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Transações de Carteira
-- ============================================
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'loan_out', 'loan_payment') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    reference_type VARCHAR(50) NULL COMMENT 'Tipo de referência: loan, installment, transfer',
    reference_id INT NULL COMMENT 'ID da referência (loan_id, installment_id, etc)',
    related_wallet_id INT NULL COMMENT 'ID da carteira relacionada (para transferências)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (related_wallet_id) REFERENCES wallets(id) ON DELETE SET NULL,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Clientes
-- ============================================
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_cpf (cpf),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Empréstimos
-- ============================================
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    wallet_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Valor original do empréstimo',
    interest_rate DECIMAL(5, 2) NOT NULL COMMENT 'Taxa de juros aplicada (%)',
    interest_amount DECIMAL(10, 2) NOT NULL COMMENT 'Valor dos juros em R$',
    total_amount DECIMAL(10, 2) NOT NULL COMMENT 'Valor total (amount + interest_amount)',
    installments_count INT NOT NULL DEFAULT 1,
    status ENUM('active', 'paid', 'overdue') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE RESTRICT,
    INDEX idx_client_id (client_id),
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de Parcelas de Empréstimos
-- ============================================
CREATE TABLE IF NOT EXISTS loan_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL COMMENT 'Número da parcela (1, 2, 3...)',
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Valor da parcela',
    due_date DATE NOT NULL COMMENT 'Data de vencimento',
    paid_date DATETIME NULL COMMENT 'Data do pagamento',
    status ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_paid_date (paid_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Inserir dados iniciais
-- ============================================

-- Usuário padrão: admin / admin123
-- Senha gerada com: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Carteira padrão
INSERT INTO wallets (name, balance) VALUES
('Carteira Principal', 0.00);

-- ============================================
-- Views úteis para relatórios
-- ============================================

-- View: Resumo de transações por carteira
CREATE OR REPLACE VIEW v_wallet_summary AS
SELECT
    w.id,
    w.name,
    w.balance,
    COUNT(DISTINCT l.id) as total_loans,
    COALESCE(SUM(CASE WHEN wt.type IN ('deposit', 'transfer_in', 'loan_payment') THEN wt.amount ELSE 0 END), 0) as total_income,
    COALESCE(SUM(CASE WHEN wt.type IN ('withdrawal', 'transfer_out', 'loan_out') THEN wt.amount ELSE 0 END), 0) as total_expenses
FROM wallets w
LEFT JOIN wallet_transactions wt ON w.id = wt.wallet_id
LEFT JOIN loans l ON w.id = l.wallet_id
WHERE w.is_active = 1
GROUP BY w.id, w.name, w.balance;

-- View: Resumo de clientes
CREATE OR REPLACE VIEW v_client_summary AS
SELECT
    c.id,
    c.name,
    c.cpf,
    c.phone,
    COUNT(DISTINCT l.id) as total_loans,
    COUNT(DISTINCT CASE WHEN l.status = 'active' THEN l.id END) as active_loans,
    COALESCE(SUM(CASE WHEN l.status = 'active' THEN l.total_amount ELSE 0 END), 0) as active_loan_amount,
    COALESCE(SUM(li.amount), 0) as total_paid,
    COUNT(CASE WHEN li.status = 'overdue' THEN 1 END) as overdue_installments
FROM clients c
LEFT JOIN loans l ON c.id = l.client_id
LEFT JOIN loan_installments li ON l.id = li.loan_id AND li.status = 'paid'
WHERE c.is_active = 1
GROUP BY c.id, c.name, c.cpf, c.phone;

-- View: Parcelas em atraso
CREATE OR REPLACE VIEW v_overdue_installments AS
SELECT
    li.id,
    li.loan_id,
    li.installment_number,
    li.amount,
    li.due_date,
    DATEDIFF(CURDATE(), li.due_date) as days_overdue,
    l.client_id,
    c.name as client_name,
    c.phone as client_phone,
    l.wallet_id,
    w.name as wallet_name
FROM loan_installments li
INNER JOIN loans l ON li.loan_id = l.id
INNER JOIN clients c ON l.client_id = c.id
INNER JOIN wallets w ON l.wallet_id = w.id
WHERE li.status = 'pending'
  AND li.due_date < CURDATE()
ORDER BY li.due_date ASC;

-- ============================================
-- Triggers para atualização automática
-- ============================================

-- Trigger: Atualizar status do empréstimo quando todas parcelas forem pagas
DELIMITER //
CREATE TRIGGER trg_update_loan_status_after_payment
AFTER UPDATE ON loan_installments
FOR EACH ROW
BEGIN
    DECLARE total_installments INT;
    DECLARE paid_installments INT;

    IF NEW.status = 'paid' AND OLD.status != 'paid' THEN
        SELECT COUNT(*) INTO total_installments FROM loan_installments WHERE loan_id = NEW.loan_id;
        SELECT COUNT(*) INTO paid_installments FROM loan_installments WHERE loan_id = NEW.loan_id AND status = 'paid';

        IF total_installments = paid_installments THEN
            UPDATE loans SET status = 'paid' WHERE id = NEW.loan_id;
        END IF;
    END IF;
END//
DELIMITER ;

-- ============================================
-- Procedure para processar pagamento de parcela
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_process_installment_payment(
    IN p_installment_id INT,
    IN p_payment_date DATETIME
)
BEGIN
    DECLARE v_loan_id INT;
    DECLARE v_wallet_id INT;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_client_id INT;

    -- Inicia transação
    START TRANSACTION;

    -- Busca informações da parcela e empréstimo
    SELECT li.loan_id, li.amount, l.wallet_id, l.client_id
    INTO v_loan_id, v_amount, v_wallet_id, v_client_id
    FROM loan_installments li
    INNER JOIN loans l ON li.loan_id = l.id
    WHERE li.id = p_installment_id;

    -- Atualiza status da parcela
    UPDATE loan_installments
    SET status = 'paid', paid_date = p_payment_date
    WHERE id = p_installment_id;

    -- Credita valor na carteira
    UPDATE wallets
    SET balance = balance + v_amount
    WHERE id = v_wallet_id;

    -- Registra transação na carteira
    INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id)
    VALUES (v_wallet_id, 'loan_payment', v_amount,
            CONCAT('Pagamento de parcela - Empréstimo #', v_loan_id),
            'installment', p_installment_id);

    COMMIT;
END//
DELIMITER ;

-- ============================================
-- Fim da migração
-- ============================================
