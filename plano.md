# 📋 Plano de Desenvolvimento - Facilita Cred

**Repo-Git**:https://github.com/LukystarWar/FacilitaCred.git

**Sistema de Gestão de Empréstimos**
**Arquitetura**: Vertical Slice Architecture (VSA)
**Stack**: PHP Puro + MySQL + HTML/CSS + JS Mínimo
**Foco**: Tablets (Mobile-First)

---

## 🎯 Objetivo

Construir um sistema limpo e eficiente para gerenciamento de empréstimos com controle total sobre:
- Carteiras (múltiplas)
- Empréstimos e parcelas
- Clientes
- Histórico completo de transações
- Relatórios de entradas/saídas
- Lucratividade

---

## 📐 Arquitetura VSA

Cada funcionalidade será implementada como uma **fatia vertical completa**:
```
feature/
├── view.php          (UI - HTML)
├── service.php       (Lógica de negócio)
├── action.php        (Handlers de ações)
└── styles.css        (Estilos específicos - opcional)
```

**Estrutura do Projeto**:
```
FacilitaCred/
├── config/
│   ├── database.php
│   └── config.php
├── core/
│   ├── Router.php
│   ├── Database.php
│   └── Session.php
├── features/
│   ├── auth/
│   ├── wallets/
│   ├── clients/
│   ├── loans/
│   └── reports/
├── shared/
│   ├── layout/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── components/
│   └── helpers/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php
└── database/
    └── migrations.sql
```

---

## 🚀 Fases de Desenvolvimento

### **FASE 1: Fundação do Projeto**
**Objetivo**: Estrutura base + banco de dados + autenticação

#### 1.1 Setup Inicial
- [ ] Criar estrutura de diretórios VSA
- [ ] Configurar autoloader ou includes
- [ ] Criar arquivo de configuração (database, constants)
- [ ] Implementar classe Database (PDO)
- [ ] Implementar Router básico
- [ ] Implementar Session Manager

#### 1.2 Banco de Dados
Criar tabelas:
- [ ] `users` (id, username, password_hash, created_at)
- [ ] `wallets` (id, name, balance, is_active, created_at, updated_at)
- [ ] `wallet_transactions` (id, wallet_id, type, amount, description, reference_type, reference_id, created_at)
- [ ] `clients` (id, name, cpf, phone, address, is_active, created_at, updated_at)
- [ ] `loans` (id, client_id, wallet_id, amount, interest_rate, total_amount, installments_count, created_at, status)
- [ ] `loan_installments` (id, loan_id, installment_number, amount, due_date, paid_date, status)

#### 1.3 Layout Base
- [ ] Criar layout responsivo com sidebar
- [ ] Implementar menu lateral (mobile-first)
- [ ] Definir paleta de cores e tipografia
- [ ] Criar sistema de modais reutilizável
- [ ] Implementar alerts/notifications

#### 1.4 Feature: Autenticação
```
features/auth/
├── login-view.php
├── auth-service.php
├── login-action.php
└── logout-action.php
```
- [ ] Tela de login (limpa, tablet-friendly)
- [ ] Validação de credenciais
- [ ] Gerenciamento de sessão
- [ ] Logout
- [ ] Proteção de rotas (middleware)

**Entrega**: Sistema funcional com login e estrutura pronta

---

### **FASE 2: Módulo de Carteiras (Wallets)**
**Objetivo**: CRUD completo + sistema de transações

```
features/wallets/
├── list-view.php
├── create-view.php (modal)
├── edit-view.php (modal)
├── details-view.php
├── wallet-service.php
├── wallet-actions.php
└── transaction-service.php
```

#### 2.1 CRUD de Carteiras
- [ ] Listar todas as carteiras (tabela responsiva)
- [ ] Criar nova carteira (modal)
- [ ] Editar carteira (modal)
- [ ] Desativar carteira (soft delete)
- [ ] Exibir saldo atual

#### 2.2 Sistema de Transações
**Tipos de transação**:
- Depósito (entrada manual)
- Saque (saída manual)
- Transferência entre carteiras
- Empréstimo concedido (saída automática)
- Pagamento de parcela (entrada automática)

**Implementações**:
- [ ] Depósito em carteira
- [ ] Saque de carteira
- [ ] Transferência entre carteiras
- [ ] Histórico de transações por carteira
- [ ] Cálculo de lucro (receitas - custos)

#### 2.3 Visão Detalhada da Carteira
- [ ] Informações gerais
- [ ] Saldo atual
- [ ] Histórico completo de transações
- [ ] Filtros de data/tipo
- [ ] Lucro acumulado

**Entrega**: Sistema completo de gestão de carteiras

---

### **FASE 3: Módulo de Clientes**
**Objetivo**: Gestão completa de clientes

```
features/clients/
├── list-view.php
├── create-view.php (modal)
├── edit-view.php (modal)
├── details-view.php
├── client-service.php
└── client-actions.php
```

#### 3.1 CRUD de Clientes
- [ ] Listar clientes (tabela com busca)
- [ ] Criar novo cliente (modal com validação de CPF)
- [ ] Editar cliente (modal)
- [ ] Desativar cliente
- [ ] Busca por nome/CPF/telefone
- [ ] Ordenação por colunas

#### 3.2 Visão Detalhada do Cliente
- [ ] Dados cadastrais
- [ ] Empréstimos ativos
- [ ] Histórico de empréstimos
- [ ] Total emprestado
- [ ] Total pago
- [ ] Parcelas em atraso
- [ ] Indicadores visuais (badges de status)

**Entrega**: Módulo completo de clientes

---

### **FASE 4: Módulo de Empréstimos**
**Objetivo**: Sistema completo de empréstimos e pagamentos

```
features/loans/
├── list-view.php
├── create-view.php
├── details-view.php
├── installment-payment-view.php (modal)
├── loan-service.php
├── loan-actions.php
└── installment-service.php
```

#### 4.1 Criação de Empréstimo
**Fluxo**:
1. Selecionar cliente existente
2. Informar valor do empréstimo
3. Escolher tipo de pagamento:
   - À vista: 20% juros
   - Parcelado: 15% ao mês
4. Definir número de parcelas
5. Calcular automaticamente valores das parcelas
6. Permitir edição manual de cada parcela
7. Selecionar carteira de origem
8. Confirmar e debitar da carteira

**Implementações**:
- [ ] Formulário de criação (passo a passo ou modal grande)
- [ ] Seletor de cliente (autocomplete/dropdown)
- [ ] Cálculo automático de juros
- [ ] Cálculo automático de parcelas
- [ ] Edição manual de parcelas
- [ ] Validação: saldo suficiente na carteira
- [ ] Debitar valor da carteira selecionada
- [ ] Criar registro de transação na carteira
- [ ] Gerar parcelas no banco

#### 4.2 Listagem de Empréstimos
- [ ] Tabela com todos os empréstimos
- [ ] Filtros: status (ativo, pago, atrasado), cliente, carteira
- [ ] Busca por cliente
- [ ] Indicadores visuais de status
- [ ] Ações rápidas (ver detalhes, registrar pagamento)

#### 4.3 Detalhes do Empréstimo
- [ ] Informações do cliente
- [ ] Carteira utilizada
- [ ] Valor original + juros
- [ ] Valor total
- [ ] Lista de parcelas (número, valor, vencimento, status)
- [ ] Timeline visual
- [ ] Lucro gerado (parcelas pagas - valor original)
- [ ] Histórico completo
- [ ] Ações: registrar pagamento, editar parcela

#### 4.4 Sistema de Pagamentos
- [ ] Registrar pagamento de parcela (modal)
- [ ] Creditar valor automaticamente na carteira de origem
- [ ] Criar registro de transação na carteira
- [ ] Atualizar status da parcela
- [ ] Atualizar status do empréstimo (se última parcela)
- [ ] Permitir pagamento parcial (opcional)
- [ ] Registrar data de pagamento

#### 4.5 Regras de Negócio
**Juros**:
- À vista: 20%
- Parcelado: 15% ao mês (acumulativo)
  - Exemplo: 3 meses = 3 × 15% = 45%

**Status de parcela**:
- Pendente
- Paga
- Atrasada (vencimento < data atual e não paga)

**Status de empréstimo**:
- Ativo
- Pago (todas parcelas pagas)
- Atrasado (alguma parcela atrasada)

**Entrega**: Sistema completo de empréstimos

---

### **FASE 5: Módulo de Relatórios**
**Objetivo**: Visão clara de entradas, saídas e lucratividade

```
features/reports/
├── dashboard-view.php
├── cash-flow-view.php
├── profit-view.php
├── report-service.php
└── export-actions.php
```

#### 5.1 Dashboard Principal
- [ ] Cards com métricas principais:
  - Total em carteiras
  - Total emprestado (ativo)
  - Total a receber
  - Lucro total acumulado
  - Parcelas vencidas hoje
  - Parcelas em atraso
- [ ] Gráfico de evolução (últimos 30/90 dias)
- [ ] Tabela de empréstimos recentes
- [ ] Tabela de pagamentos recentes

#### 5.2 Relatório de Fluxo de Caixa (Entradas/Saídas)
**Mais importante e detalhado**

**Filtros**:
- [ ] Período (data inicial e final)
- [ ] Carteira específica ou todas
- [ ] Tipo de transação

**Visualização**:
- [ ] Tabela detalhada:
  - Data
  - Tipo (entrada/saída)
  - Categoria (empréstimo, pagamento, depósito, saque, transferência)
  - Carteira
  - Cliente (se aplicável)
  - Valor
  - Descrição
- [ ] Totalizadores:
  - Total de entradas
  - Total de saídas
  - Saldo do período
- [ ] Gráfico visual (barras ou linha)
- [ ] Exportar para PDF/Excel (opcional)

#### 5.3 Relatório de Lucratividade
- [ ] Lucro por período
- [ ] Lucro por carteira
- [ ] Lucro por cliente
- [ ] Taxa de inadimplência
- [ ] Comparativo mensal

#### 5.4 Relatório de Inadimplência
- [ ] Lista de parcelas em atraso
- [ ] Valor total em atraso
- [ ] Clientes inadimplentes
- [ ] Dias de atraso

**Entrega**: Sistema completo de relatórios

---

### **FASE 6: Refinamentos e Otimizações**

#### 6.1 Otimização para Tablets
- [ ] Testar em dispositivos reais
- [ ] Ajustar tamanhos de toque
- [ ] Otimizar modais para telas médias
- [ ] Garantir performance em redes lentas
- [ ] Lazy loading se necessário

#### 6.2 Validações e Segurança
- [ ] Validação de inputs (client + server)
- [ ] Proteção contra SQL Injection (prepared statements)
- [ ] Proteção CSRF
- [ ] Sanitização de dados
- [ ] Logs de ações críticas

#### 6.3 Experiência do Usuário
- [ ] Mensagens de sucesso/erro claras
- [ ] Loading states
- [ ] Confirmações para ações destrutivas
- [ ] Tooltips e hints
- [ ] Atalhos de teclado (opcional)

#### 6.4 Performance
- [ ] Indexação de banco de dados
- [ ] Paginação de listagens
- [ ] Cache de queries frequentes (se necessário)
- [ ] Minificação de CSS/JS
- [ ] Compressão de assets

#### 6.5 Documentação
- [ ] README com instruções de instalação
- [ ] Documentação de regras de negócio
- [ ] Scripts de migração/seed do banco
- [ ] Comentários em código complexo

**Entrega**: Sistema completo, otimizado e pronto para produção

---

## 📊 Regras de Negócio Principais

### Juros
- **À vista**: 20% de juros
- **Parcelado**: 15% ao mês (acumulativo)

### Transações de Carteira
Toda operação deve gerar histórico:
- **Empréstimo concedido**: Saída da carteira
- **Pagamento recebido**: Entrada na carteira
- **Depósito**: Entrada manual
- **Saque**: Saída manual
- **Transferência**: Saída + Entrada

### Status de Empréstimo
- **Ativo**: Possui parcelas pendentes
- **Pago**: Todas parcelas pagas
- **Atrasado**: Pelo menos uma parcela vencida e não paga

### Cálculo de Lucro
```
Lucro = Total recebido (pagamentos) - Valor original emprestado
```

---

## 🎨 Guidelines de UI/UX

### Princípios
- Mobile-first (tablets como prioridade)
- Modais para forms (evitar navegação excessiva)
- Feedback visual claro
- Mínimo de JavaScript
- Performance acima de animações

### Componentes Padrão
- Tabelas responsivas
- Modais (criar, editar, confirmar)
- Cards de métricas
- Badges de status
- Botões de ação
- Formulários com validação inline

### Paleta de Cores (sugestão)
- Primary: Azul (#2563eb)
- Success: Verde (#10b981)
- Warning: Amarelo (#f59e0b)
- Danger: Vermelho (#ef4444)
- Neutral: Cinza (#6b7280)

---

## ✅ Critérios de Conclusão por Fase

**Cada fase só será considerada completa quando**:
1. Todas as funcionalidades estiverem implementadas
2. Validações client + server funcionando
3. Integração com banco de dados testada
4. UI responsiva e funcional em tablets
5. Nenhum bug crítico identificado
6. Código comentado onde necessário

---

## 🚦 Status Atual

**Fase Atual**: Fase 1 - Fundação do Projeto
**Progresso**: 100% ✅ COMPLETA

### ✅ Fase 1 Concluída (09/12/2025)

**Estrutura criada:**
- ✅ Diretórios VSA completos
- ✅ Classes core (Database, Router, Session)
- ✅ Arquivos de configuração
- ✅ Layout base (header, sidebar, footer)
- ✅ Sistema de modais reutilizável
- ✅ CSS moderno e responsivo (mobile-first)
- ✅ JavaScript com funções auxiliares

**Banco de dados:**
- ✅ Script de migração completo
- ✅ 6 tabelas criadas
- ✅ 3 views úteis
- ✅ Triggers automáticos
- ✅ Stored procedure para pagamentos
- ✅ Dados iniciais (usuário admin)

**Autenticação:**
- ✅ Tela de login responsiva
- ✅ Service de autenticação
- ✅ Login/logout funcionais
- ✅ Proteção de rotas
- ✅ Gerenciamento de sessão

**Dashboard:**
- ✅ Tela inicial básica
- ✅ Cards de métricas (placeholder)
- ✅ Mensagens de boas-vindas

**Documentação:**
- ✅ README completo
- ✅ INSTALL.md com guia de instalação
- ✅ Instalador automático (install.php)
- ✅ Plano de desenvolvimento

**Próxima fase:** Fase 2 - Módulo de Carteiras

---

## 📝 Notas de Desenvolvimento

- Implementar **uma feature por vez, completamente**
- Testar cada funcionalidade antes de avançar
- Commitar frequentemente
- Manter código limpo e comentado
- Priorizar clareza sobre otimização prematura
- Documentar decisões arquiteturais importantes

---

**Última atualização**: 2025-12-09
