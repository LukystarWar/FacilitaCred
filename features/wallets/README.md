# Módulo de Carteiras

## Visão Geral

O módulo de Carteiras permite gerenciar múltiplas carteiras de dinheiro, controlar saldo e registrar todas as movimentações financeiras.

## Arquivos do Módulo

```
features/wallets/
├── wallet-service.php      # Lógica de negócio
├── list-view.php           # Página de listagem
├── details-view.php        # Página de detalhes
├── create-action.php       # Ação de criar
├── update-action.php       # Ação de editar
├── delete-action.php       # Ação de excluir
└── transaction-action.php  # Ação de movimentar
```

## Funcionalidades

### 1. Criar Carteira
- Nome obrigatório
- Saldo inicial opcional (padrão: 0)
- Descrição opcional
- Registro automático de transação se houver saldo inicial

### 2. Editar Carteira
- Alterar nome
- Alterar descrição
- Saldo não pode ser editado diretamente (use movimentações)

### 3. Excluir Carteira
- Apenas carteiras sem transações podem ser excluídas
- Validação automática

### 4. Movimentações
- **Adicionar Saldo (Credit)**: Aumenta o saldo
- **Retirar Saldo (Debit)**: Diminui o saldo (valida saldo suficiente)
- Descrição obrigatória para rastreabilidade

### 5. Histórico
- Lista todas as transações da carteira
- Ordenado por data (mais recente primeiro)
- Mostra tipo, valor e descrição
- Cores diferentes para entradas (verde) e saídas (vermelho)

## Uso da Service Layer

### Exemplo: Criar Carteira

```php
require_once __DIR__ . '/wallet-service.php';

$walletService = new WalletService();
$userId = Session::get('user_id');

$result = $walletService->createWallet(
    $userId,
    'Caixa Principal',      // nome
    5000.00,                 // saldo inicial
    'Carteira principal'     // descrição
);

if ($result['success']) {
    echo "Carteira criada com ID: " . $result['id'];
} else {
    echo "Erro: " . $result['error'];
}
```

### Exemplo: Adicionar Saldo

```php
$walletId = 1;
$amount = 1000.00;
$description = 'Depósito mensal';

$result = $walletService->addBalance($walletId, $userId, $amount, $description);

if ($result['success']) {
    echo "Saldo adicionado com sucesso!";
}
```

### Exemplo: Remover Saldo

```php
$result = $walletService->removeBalance($walletId, $userId, $amount, $description);

if ($result['success']) {
    echo "Saldo removido com sucesso!";
} else {
    echo "Erro: " . $result['error']; // Ex: "Saldo insuficiente"
}
```

### Exemplo: Buscar Transações

```php
$transactions = $walletService->getTransactions($walletId, $userId, 50);

foreach ($transactions as $transaction) {
    echo $transaction['type'] . ': R$ ' . $transaction['amount'];
    echo ' - ' . $transaction['description'] . "\n";
}
```

## Tipos de Transação

| Tipo | Descrição | Quando é usado |
|------|-----------|----------------|
| `credit` | Entrada de dinheiro | Adição manual de saldo |
| `debit` | Saída de dinheiro | Remoção manual de saldo |
| `loan_disbursement` | Desembolso de empréstimo | Quando um empréstimo é criado (Fase 4) |
| `payment_received` | Pagamento recebido | Quando uma parcela é paga (Fase 4) |

## Validações

### Server-Side
- Nome: obrigatório, não vazio
- Saldo inicial: >= 0
- Valor de transação: > 0
- Saldo suficiente para débitos
- Verificação de propriedade (user_id)
- Carteira não pode ser excluída se tiver transações

### Client-Side
- Campos required nos formulários
- Type="number" para valores monetários
- Confirmação antes de excluir

## Interface do Usuário

### Página de Listagem
- Grid responsivo de cards
- Cada card mostra:
  - Nome da carteira
  - Descrição (se houver)
  - Saldo atual (destaque visual)
  - Número de transações
  - Data de criação
  - Botões de ação (editar, excluir, movimentar, detalhes)

### Página de Detalhes
- Estatísticas da carteira
- Tabela de transações com:
  - Data/hora
  - Tipo (badge colorido)
  - Descrição
  - Valor (+ verde / - vermelho)

### Modais
- **Criar Carteira**: Nome, saldo inicial, descrição
- **Editar Carteira**: Nome, descrição
- **Movimentar**: Tipo (crédito/débito), valor, descrição

## Integração com Outros Módulos

### Dashboard (Fase 1)
```php
// Buscar total em carteiras para o dashboard
$totalBalance = $walletService->getTotalBalance($userId);
```

### Empréstimos (Fase 4)
Quando um empréstimo é criado:
1. O valor é debitado da carteira selecionada
2. Uma transação `loan_disbursement` é registrada

Quando um pagamento é recebido:
1. O valor é creditado na carteira de origem
2. Uma transação `payment_received` é registrada

## Segurança

- Todas as queries usam prepared statements
- Outputs sanitizados com `htmlspecialchars()`
- Validação de propriedade em todas as operações
- Transações de banco de dados para operações críticas

## Performance

- Limit de 100 transações por consulta
- Índices em id e user_id
- Queries otimizadas com COUNT()

## Exemplo de Fluxo Completo

```php
// 1. Usuário faz login
Session::setUser(1, 'admin');

// 2. Cria uma carteira
$service = new WalletService();
$result = $service->createWallet(1, 'Minha Carteira', 1000.00);
$walletId = $result['id'];

// 3. Adiciona mais saldo
$service->addBalance($walletId, 1, 500.00, 'Depósito extra');

// 4. Retira um valor
$service->removeBalance($walletId, 1, 200.00, 'Saque para despesas');

// 5. Consulta o saldo atual
$wallet = $service->getWalletById($walletId, 1);
echo $wallet['balance']; // 1300.00

// 6. Consulta o histórico
$transactions = $service->getTransactions($walletId, 1);
// 3 transações registradas:
// - Saldo inicial: +1000.00
// - Depósito extra: +500.00
// - Saque para despesas: -200.00
```

## Troubleshooting

### Erro: "Carteira não encontrada"
- Verifique se o ID é válido
- Verifique se a carteira pertence ao usuário logado

### Erro: "Saldo insuficiente"
- Verifique o saldo atual antes de debitar
- Use `getWalletById()` para consultar

### Erro: "Não é possível excluir uma carteira com transações"
- Esta é uma proteção de dados
- Se necessário, implemente uma funcionalidade de arquivar ao invés de excluir

---

**Desenvolvido na Fase 2**
**Versão:** 1.0.0
