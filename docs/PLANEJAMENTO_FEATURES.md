# 📋 Planejamento de Features - FacilitaCred

**Data:** 10/12/2024
**Status:** Em Planejamento
**Versão:** 2.0

---

## 📊 Resumo Executivo

### 🎯 Total de Features: 7
| # | Feature | Prioridade | Tempo Estimado | Complexidade |
|---|---------|-----------|----------------|--------------|
| 1 | Sistema de Configurações Globais | 🔴 ALTA | 4-5h | Alta |
| 2 | Sistema de Cobrança WhatsApp | 🟡 MÉDIA | 3-4h | Média |
| 3 | Fix: Edição de Clientes | 🟢 BAIXA | 15min | Baixa |
| 4 | Fix: Badges Bootstrap | 🟢 BAIXA | 30min | Baixa |
| 5 | Filtros e Paginação Empréstimos | 🟡 MÉDIA | 3-4h | Média |
| 6 | Quitação com Acréscimo/Desconto | 🔴 ALTA | 4-6h | Alta |
| 7 | Analysis: Lógica de Lucro | 🟡 MÉDIA | 2-3h | Média |

### 🗄️ Impacto no Banco de Dados
- **2 Novas Tabelas:** `system_settings`, `whatsapp_templates`
- **1 Tabela Alterada:** `loan_installments` (4 novas colunas)
- **Migrations Necessários:** Sim

### ⏱️ Tempo Total Estimado: 17-23 horas

### 🔑 Features Críticas (Bloqueantes)
1. **Sistema de Configurações** - Base para cálculos de juros e multas
2. **Quitação com Acréscimo/Desconto** - Essencial para operação diária

---

## ⚙️ 1. FEATURE: Sistema de Configurações Globais

### 📦 Escopo
Painel de configurações para gerenciar regras de negócio do sistema: taxas de juros, carência e multas.

### 🗄️ Database Changes
**Nova Tabela:** `system_settings`
```sql
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'Chave única da configuração',
    setting_value TEXT NOT NULL COMMENT 'Valor da configuração (pode ser JSON para valores complexos)',
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(255) NULL COMMENT 'Descrição da configuração',
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Categoria (interest, penalty, grace_period, etc)',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL COMMENT 'ID do usuário que atualizou',
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações iniciais
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category) VALUES
('interest_rate_single_payment', '20', 'number', 'Taxa de juros para pagamento à vista (%)', 'interest'),
('interest_rate_installment', '15', 'number', 'Taxa de juros ao mês para parcelamentos (%)', 'interest'),
('grace_period_days', '3', 'number', 'Dias de carência após vencimento', 'grace_period'),
('late_fee_percentage', '2', 'number', 'Juros por dia de atraso após carência (%)', 'penalty'),
('late_fee_type', 'daily', 'text', 'Tipo de cálculo de multa (daily, monthly, fixed)', 'penalty'),
('system_name', 'FacilitaCred', 'text', 'Nome do sistema', 'general'),
('min_loan_amount', '100', 'number', 'Valor mínimo de empréstimo (R$)', 'loan_rules'),
('max_loan_amount', '100000', 'number', 'Valor máximo de empréstimo (R$)', 'loan_rules'),
('max_installments', '24', 'number', 'Número máximo de parcelas', 'loan_rules');
```

### 📄 Arquivos Necessários

#### Views
- `features/settings/index.php` - Dashboard de configurações com abas
- `features/settings/interest-settings.php` - Configurações de Juros
- `features/settings/penalty-settings.php` - Configurações de Multas e Carência
- `features/settings/loan-rules-settings.php` - Regras de Empréstimos

#### Services
- `features/settings/settings-service.php` - CRUD e cache de configurações

```php
class SettingsService {
    private static $cache = [];

    public function getSetting($key, $default = null) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = $this->db->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $default;
        }

        $value = $this->castValue($result['setting_value'], $result['setting_type']);
        self::$cache[$key] = $value;
        return $value;
    }

    public function updateSetting($key, $value, $userId) {
        $stmt = $this->db->prepare("
            UPDATE system_settings
            SET setting_value = ?, updated_by = ?, updated_at = NOW()
            WHERE setting_key = ?
        ");
        $stmt->execute([$value, $userId, $key]);

        // Limpar cache
        unset(self::$cache[$key]);
    }

    private function castValue($value, $type) {
        switch ($type) {
            case 'number': return (float) $value;
            case 'boolean': return (bool) $value;
            case 'json': return json_decode($value, true);
            default: return $value;
        }
    }
}
```

#### Actions
- `features/settings/update-interest-action.php` - Atualizar configurações de juros
- `features/settings/update-penalty-action.php` - Atualizar multas/carência

### 🎨 Interface Proposta

**Aba 1: Juros**
```
┌──────────────────────────────────────────────┐
│ 💰 CONFIGURAÇÕES DE JUROS                   │
├──────────────────────────────────────────────┤
│                                              │
│  Taxa de Juros à Vista                      │
│  ┌────────┐ %                               │
│  │   20   │                                  │
│  └────────┘                                  │
│  Aplicada quando o empréstimo é pago em      │
│  uma única parcela.                          │
│                                              │
│  Taxa de Juros ao Mês (Parcelado)           │
│  ┌────────┐ % ao mês                        │
│  │   15   │                                  │
│  └────────┘                                  │
│  Aplicada cumulativamente em cada parcela.   │
│                                              │
│  [ Salvar Configurações ]                    │
└──────────────────────────────────────────────┘
```

**Aba 2: Multas e Carência**
```
┌──────────────────────────────────────────────┐
│ ⏰ CARÊNCIA E MULTAS POR ATRASO              │
├──────────────────────────────────────────────┤
│                                              │
│  Período de Carência                         │
│  ┌────────┐ dias                            │
│  │   3    │                                  │
│  └────────┘                                  │
│  Dias de tolerância após o vencimento antes  │
│  de aplicar multa.                           │
│                                              │
│  Juros por Atraso (após carência)            │
│  ┌────────┐ % ao dia                        │
│  │   2    │                                  │
│  └────────┘                                  │
│  Taxa aplicada diariamente sobre o valor     │
│  da parcela em atraso.                       │
│                                              │
│  [ Salvar Configurações ]                    │
└──────────────────────────────────────────────┘
```

### 🧮 Lógica de Cálculo de Multa por Atraso

**Service:** `features/loans/loan-service.php`
```php
public function calculateLateFee($installmentAmount, $dueDate) {
    $settingsService = new SettingsService();

    $gracePeriodDays = $settingsService->getSetting('grace_period_days', 3);
    $lateFeePercentage = $settingsService->getSetting('late_fee_percentage', 2);

    $today = new DateTime();
    $dueDateTime = new DateTime($dueDate);
    $interval = $today->diff($dueDateTime);
    $daysOverdue = $interval->days;

    // Se ainda está dentro do período de carência, sem multa
    if ($daysOverdue <= $gracePeriodDays) {
        return [
            'days_overdue' => $daysOverdue,
            'days_with_penalty' => 0,
            'late_fee_amount' => 0,
            'total_amount' => $installmentAmount,
            'in_grace_period' => true
        ];
    }

    // Calcular dias com multa (excluindo carência)
    $daysWithPenalty = $daysOverdue - $gracePeriodDays;

    // Juros diário sobre o valor da parcela
    $dailyRate = $lateFeePercentage / 100;
    $lateFeeAmount = $installmentAmount * $dailyRate * $daysWithPenalty;
    $totalAmount = $installmentAmount + $lateFeeAmount;

    return [
        'days_overdue' => $daysOverdue,
        'days_with_penalty' => $daysWithPenalty,
        'late_fee_amount' => $lateFeeAmount,
        'total_amount' => $totalAmount,
        'in_grace_period' => false
    ];
}
```

### 📄 Uso nas Views

**Exemplo em:** `features/loans/details-view.php`
```php
<?php foreach ($installments as $inst): ?>
    <?php if ($inst['status'] === 'overdue'): ?>
        <?php
        $lateFee = $loanService->calculateLateFee($inst['amount'], $inst['due_date']);
        ?>
        <tr>
            <td>Parcela #<?= $inst['installment_number'] ?></td>
            <td>R$ <?= number_format($inst['amount'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($inst['due_date'])) ?></td>
            <td>
                <span class="badge badge-danger">
                    <?= $lateFee['days_overdue'] ?> dias de atraso
                </span>
                <?php if ($lateFee['in_grace_period']): ?>
                    <small class="text-muted">Em carência</small>
                <?php else: ?>
                    <small class="text-danger">
                        + R$ <?= number_format($lateFee['late_fee_amount'], 2, ',', '.') ?>
                        (<?= $lateFee['days_with_penalty'] ?> dias c/ multa)
                    </small>
                <?php endif; ?>
            </td>
            <td>
                <strong>R$ <?= number_format($lateFee['total_amount'], 2, ',', '.') ?></strong>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>
```

### 🔗 Integração com Criação de Empréstimos

**Arquivo:** `features/loans/create-view.php`
```php
<?php
$settingsService = new SettingsService();
$singlePaymentRate = $settingsService->getSetting('interest_rate_single_payment', 20);
$installmentRate = $settingsService->getSetting('interest_rate_installment', 15);
$maxInstallments = $settingsService->getSetting('max_installments', 24);
?>

<script>
const SINGLE_PAYMENT_RATE = <?= $singlePaymentRate ?>;
const INSTALLMENT_RATE = <?= $installmentRate ?>;

function calculateInterest() {
    const amount = parseFloat(document.getElementById('amount').value);
    const installments = parseInt(document.getElementById('installments').value);

    let interestRate;
    if (installments === 1) {
        interestRate = SINGLE_PAYMENT_RATE;
    } else {
        interestRate = INSTALLMENT_RATE;
    }

    // Cálculo...
}
</script>
```

### 🔒 Permissões

- Apenas usuários admin podem acessar `/settings`
- Criar middleware de autorização
- Log de todas as alterações de configurações

### ⚠️ Considerações

- Cache de configurações em memória para performance
- Validação de valores mínimos/máximos
- Histórico de alterações (audit log)
- Backup automático antes de salvar configurações críticas

---

## 🎯 2. FEATURE: Sistema de Cobrança WhatsApp

### 📦 Escopo
Sistema para envio automatizado de mensagens de cobrança via WhatsApp com templates personalizáveis.

### 🗄️ Database Changes
**Nova Tabela:** `whatsapp_templates`
```sql
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nome do template (ex: Cobrança Padrão)',
    message TEXT NOT NULL COMMENT 'Mensagem com variáveis: {cliente}, {valor}, {vencimento}, {dias_atraso}',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template padrão inicial
INSERT INTO whatsapp_templates (name, message) VALUES (
    'Cobrança Padrão',
    'Olá {cliente}! 👋\n\nEste é um lembrete sobre a parcela do seu empréstimo:\n\n💰 Valor: R$ {valor}\n📅 Vencimento: {vencimento}\n⏰ Dias em atraso: {dias_atraso}\n\nPor favor, regularize sua situação.\n\nObrigado! ✅'
);
```

### 📄 Arquivos Necessários

#### Views
- `features/settings/whatsapp-templates-list.php` - Lista de templates
- `features/settings/whatsapp-template-form.php` - Criar/Editar template

#### Services
- `features/settings/whatsapp-template-service.php` - CRUD de templates

#### Actions
- `features/settings/whatsapp-template-create.php`
- `features/settings/whatsapp-template-update.php`
- `features/settings/whatsapp-template-delete.php`

### 🔗 Integração em Empréstimos

**Arquivo:** `features/loans/details-view.php` e `features/loans/list-view.php`

Adicionar botão:
```php
<?php
$template = $whatsappService->getActiveTemplate();
$message = str_replace(
    ['{cliente}', '{valor}', '{vencimento}', '{dias_atraso}'],
    [$clientName, $installmentValue, $dueDate, $daysOverdue],
    $template['message']
);
$whatsappLink = 'https://wa.me/55' . preg_replace('/\D/', '', $clientPhone) . '?text=' . urlencode($message);
?>

<a href="<?= $whatsappLink ?>" target="_blank" class="btn btn-success btn-sm">
    <img src="<?= ASSETS_URL ?>/images/whatsapp.png" alt="WhatsApp" style="width: 16px; height: 16px;">
    Cobrar via WhatsApp
</a>
```

### ⚠️ Considerações
- Precisa de ícone WhatsApp PNG (25x25px)
- Telefone deve estar no formato brasileiro (+55)
- URL encoding da mensagem
- Validação se cliente tem telefone cadastrado

---

## 🔧 2. FIX: Clientes - Edição via Modal

### 📍 Problema
Em `features/clients/details-view.php`, o botão "Editar" está abrindo modal ao invés de redirecionar para a página de edição.

### ✅ Solução
**Arquivo:** `features/clients/details-view.php`

Localizar e substituir:
```php
<!-- ANTES (com modal) -->
<button onclick="openEditModal()" class="btn btn-secondary">✏️ Editar</button>

<!-- DEPOIS (com página) -->
<a href="<?= BASE_URL ?>/clients/edit?id=<?= $client['id'] ?>" class="btn btn-secondary">
    ✏️ Editar
</a>
```

Remover JavaScript relacionado ao modal.

---

## 🎨 3. FIX: Relatórios - Badges Bootstrap Colors

### 📍 Problema
Badges com cores customizadas. Queremos cores padrão Bootstrap com texto branco.

### 🎨 Cores Bootstrap (Hexa)
- **Success (Verde):** `#28a745` - texto branco
- **Danger (Vermelho):** `#dc3545` - texto branco
- **Warning (Amarelo):** `#ffc107` - texto **preto** (contraste)
- **Info (Azul):** `#17a2b8` - texto branco
- **Primary (Azul Escuro):** `#007bff` - texto branco

### 📄 Arquivos para Atualizar

**CSS:** `public/assets/css/main.css`
```css
.badge-success,
.status-success {
    background: #28a745;
    color: white;
    border: none;
}

.badge-danger,
.status-danger {
    background: #dc3545;
    color: white;
    border: none;
}

.badge-warning,
.status-warning {
    background: #ffc107;
    color: #212529; /* Texto escuro para contraste */
    border: none;
}

.badge-info,
.status-info {
    background: #17a2b8;
    color: white;
    border: none;
}

.badge-primary {
    background: #007bff;
    color: white;
    border: none;
}
```

### 📍 Views que Usam Badges
- `features/reports/cash-flow-view.php` - Status de transações
- `features/reports/dashboard-view.php` - Parcelas atrasadas
- `features/loans/details-view.php` - Status de parcelas
- `features/loans/list-view.php` - Status de empréstimos

---

## 📊 4. FEATURE: Empréstimos - Filtros e Paginação

### 🎯 Funcionalidades

#### Filtros
- Status (Ativo, Pago, Atrasado)
- Cliente (busca por nome)
- Período (data inicial - data final)
- Valor (range mínimo-máximo)

#### Paginação
- 20 empréstimos por página
- Botão de cobrar todos, via whatsapp, em algum lugar no topo.
- Navegação anterior/próxima
- Indicador de página atual

### 📄 Arquivos para Modificar

**Service:** `features/loans/loan-service.php`
```php
public function getFilteredLoans($filters = [], $page = 1, $perPage = 20) {
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = "l.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['client_name'])) {
        $where[] = "c.name LIKE ?";
        $params[] = "%{$filters['client_name']}%";
    }

    if (!empty($filters['date_from'])) {
        $where[] = "l.created_at >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = "l.created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Query com paginação...
}
```

**View:** `features/loans/list-view.php`
- Adicionar formulário de filtros no topo
- Adicionar controles de paginação no rodapé
- Mostrar "Exibindo X-Y de Z resultados"

---

## 💰 5. FEATURE: Quitação com Acréscimo/Desconto

### 🎯 Funcionalidade
Permitir pagamento de parcelas com ajuste de valor (juros de atraso ou desconto).

### 🗄️ Database Changes

**Alterar Tabela:** `loan_installments`
```sql
ALTER TABLE loan_installments
ADD COLUMN amount_paid DECIMAL(10, 2) NULL COMMENT 'Valor efetivamente pago (pode ser diferente de amount)',
ADD COLUMN adjustment_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Valor de ajuste (positivo = acréscimo, negativo = desconto)',
ADD COLUMN adjustment_reason VARCHAR(255) NULL COMMENT 'Motivo do ajuste',
ADD COLUMN paid_by INT NULL COMMENT 'ID do usuário que registrou o pagamento',
ADD INDEX idx_amount_paid (amount_paid);
```

### 📄 Arquivos Necessários

**View:** `features/loans/installment-payment-view.php`
```php
<!-- Formulário de pagamento -->
<form method="POST" action="<?= BASE_URL ?>/loans/installment-payment">
    <input type="hidden" name="installment_id" value="<?= $installment['id'] ?>">

    <div class="form-group">
        <label>Valor Original da Parcela</label>
        <input type="text" value="R$ <?= number_format($installment['amount'], 2, ',', '.') ?>" readonly>
    </div>

    <div class="form-group">
        <label>Dias em Atraso</label>
        <input type="text" value="<?= $daysOverdue ?> dias" readonly>
    </div>

    <div class="form-group">
        <label>Tipo de Ajuste</label>
        <select name="adjustment_type" onchange="calculateTotal()">
            <option value="none">Sem ajuste</option>
            <option value="discount">Desconto</option>
            <option value="interest">Juros de atraso</option>
        </select>
    </div>

    <div class="form-group">
        <label>Valor do Ajuste (%)</label>
        <input type="number" name="adjustment_percent" step="0.01" oninput="calculateTotal()">
    </div>

    <div class="form-group">
        <label>Motivo do Ajuste</label>
        <textarea name="adjustment_reason"></textarea>
    </div>

    <div class="form-group">
        <label><strong>Valor Total a Pagar</strong></label>
        <input type="text" id="total_amount" readonly class="total-highlight">
    </div>

    <button type="submit" class="btn btn-primary">Registrar Pagamento</button>
</form>
```

**Action:** `features/loans/installment-payment-action.php`
- Calcular valor com ajuste
- Registrar pagamento na parcela
- Criar transação na carteira
- Atualizar status do empréstimo se totalmente pago

### 🧮 Lógica de Cálculo
```php
$baseAmount = $installment['amount'];
$adjustmentPercent = $_POST['adjustment_percent'] ?? 0;
$adjustmentType = $_POST['adjustment_type'] ?? 'none';

switch ($adjustmentType) {
    case 'discount':
        $adjustment = -($baseAmount * ($adjustmentPercent / 100));
        break;
    case 'interest':
        $adjustment = $baseAmount * ($adjustmentPercent / 100);
        break;
    default:
        $adjustment = 0;
}

$amountPaid = $baseAmount + $adjustment;
```

---

## 📈 6. ANALYSIS: Dashboard - Lógica de Lucro

### 🤔 Questões para Revisar

#### 1. Definição de Lucro
**Atualmente:** Lucro = Soma de todos os `interest_amount` dos empréstimos

**Questão:** Isso está correto?
- ✅ **SIM** - Lucro é apenas juros cobrados
- Empréstimos não pagos NÃO são prejuízo direto (ainda podem ser pagos)
- Empréstimos calotados devem ser marcados como `status = 'defaulted'`

#### 2. Lucro Realizado vs Projetado
**Proposta:**
```sql
-- Lucro Projetado (todos os empréstimos ativos)
SELECT SUM(interest_amount) FROM loans WHERE status = 'active'

-- Lucro Realizado (apenas parcelas pagas)
SELECT SUM(
    (SELECT SUM(amount) FROM loan_installments
     WHERE loan_id = l.id AND status = 'paid') - l.amount
) FROM loans l WHERE status IN ('active', 'paid')
```

#### 3. Carteiras e Fluxo de Caixa
**Conceito:**
- Carteiras são apenas CAIXA (não afetam lucro)
- Depósito em carteira = Aporte de capital (não é receita)
- Saque de carteira = Retirada de capital (não é despesa)
- **Lucro = Juros recebidos de empréstimos**
- **Prejuízo = Empréstimos calotados** (não pagos e irrecuperáveis)

#### 4. Prevenção de "Sangria"
**Alertas Necessários:**
- Warning se saldo total das carteiras < 20% do total emprestado ativo
- Indicador de "Liquidez" no dashboard
- Bloqueio de novos empréstimos se saldo insuficiente

**Dashboard Proposto:**
```
┌─────────────────────────────────────────┐
│ INDICADORES FINANCEIROS                 │
├─────────────────────────────────────────┤
│ Saldo em Carteiras: R$ 50.000          │
│ Total Emprestado (Ativo): R$ 200.000   │
│ Total a Receber: R$ 150.000            │
│                                          │
│ 💰 Liquidez: 25% ⚠️ (Baixa)            │
│ 📊 Lucro Realizado: R$ 15.000          │
│ 📈 Lucro Projetado: R$ 30.000          │
│ ❌ Prejuízo (Calotes): R$ 2.000        │
└─────────────────────────────────────────┘
```

### 📄 Arquivos para Atualizar
- `features/reports/dashboard-view.php`
- `features/loans/loan-service.php` - Adicionar método `getFinancialIndicators()`

---

## 🚀 Ordem de Implementação Sugerida

### Fase 1 - Fixes Rápidos (1-2h)
1. ✅ Fix: Clientes - Edição via página
2. ✅ Fix: Badges Bootstrap colors

### Fase 2 - Configurações e Base (4-5h) ⭐ PRIORITÁRIO
3. ✅ Feature: Sistema de Configurações Globais (tabela + CRUD + interface)
4. ✅ Feature: Lógica de Cálculo de Multa por Atraso (usando configurações)
5. ✅ Integrar configurações na criação de empréstimos

### Fase 3 - Features Médias (3-4h)
6. ✅ Feature: Filtros e Paginação em Empréstimos
7. ✅ Feature: WhatsApp Templates (CRUD)

### Fase 4 - Features Complexas (4-6h)
8. ✅ Feature: Quitação com Acréscimo/Desconto (já integrado com multa automática)
9. ✅ Feature: Integração WhatsApp em Empréstimos

### Fase 5 - Analysis e Refinamento (2-3h)
10. ✅ Analysis: Revisar lógica de lucro
11. ✅ Implementar indicadores financeiros no Dashboard

### Fase 6 - Database Migration
12. ✅ Atualizar `database/migrations.sql` com todas as alterações:
    - Tabela `system_settings`
    - Tabela `whatsapp_templates`
    - Alterações em `loan_installments` (amount_paid, adjustment_amount, etc)
13. ✅ Atualizar `database/seed.php` para incluir dados de teste

---

## 📝 Notas Importantes

### Ícones Necessários
- [ ] `whatsapp.png` (16x16px e 25x25px)
- [ ] `configuracoes.png` já existe na pasta images ✅

### Testes a Realizar
- [ ] Testar mensagem WhatsApp em diferentes navegadores
- [ ] Testar cálculo de acréscimo/desconto
- [ ] Testar paginação com muitos registros
- [ ] Validar lógica de lucro com diferentes cenários
- [ ] Testar cálculo de multa com diferentes períodos de carência
- [ ] Testar alteração de configurações e impacto em novos empréstimos

### Backup Antes de Migrar
```bash
# Antes de aplicar migrations
mysqldump -u root facilita_cred > backup_pre_migration.sql
```

---

**Status:** 📋 Documento criado para revisão e planejamento
**Próximo Passo:** Revisar com o time e priorizar implementação
