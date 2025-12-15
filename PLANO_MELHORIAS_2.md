# Plano de Melhorias 2 - FacilitaCred

Segunda leva de melhorias focada em clareza, usabilidade e consistência.

---

## 🟢 FASE 1: Tela de Login Profissional (30min)

### Objetivo
Modernizar tela de login com identidade visual do sistema

**Arquivo:** `public/login.php`

**Mudanças:**
- Adicionar logo do sistema
- Adicionar nome "FacilitaCred"
- Design mais sóbrio e profissional
- Remover elementos desnecessários

---

## 🟢 FASE 2: Dashboard - Repensar Cards (1h)

### Análise dos Cards Atuais
**Problema:** Confusão entre dados mensais vs atemporais

**Cards atuais:**
1. Saldo em Carteiras - ✅ ATEMPORAL (OK)
2. Emprestado no Mês - ❓ Mensal (repensar)
3. A Receber (Ativos) - ✅ ATEMPORAL (OK)
4. Lucro do Mês - ❓ Mensal (confuso)

**Proposta:**
- **Manter atemporais:** Saldo, Total Emprestado (histórico), Total a Receber
- **Remover/Substituir:** Lucro do mês (mover só para relatórios)
- **Adicionar:** Parcelas vencendo esta semana, Taxa de inadimplência, etc

**Arquivo:** `features/reports/dashboard-view.php`

---

## 🟡 FASE 3: Carteiras - Paginação e Filtros (1h)

### 3.1 Paginação no Detalhamento
**Arquivo:** `features/wallets/details-view.php`
- Implementar paginação de 20 itens
- Manter cards com totais gerais (SUM total, não filtrado)

### 3.2 Filtros no Detalhamento
**Arquivo:** `features/wallets/details-view.php`
- Filtro por tipo (retirada, pagamento, transferência, empréstimo, depósito)
- Campo de busca (descrição/cliente)
- Filtro por período (data inicial/final)

---

## 🟡 FASE 4: Empréstimos - Melhorias Críticas (1-2h)

### 4.1 ID Único nos Empréstimos
**Problema:** Vários empréstimos mostram "#48" - falta unicidade
**Solução:** Usar formato "#ID" real do banco, não número da parcela

**Arquivos afetados:**
- `features/loans/list-view.php`
- `features/loans/details-view.php`
- Descrições de transações
- Templates WhatsApp

### 4.2 Select de Clientes com Autocomplete
**Problema:** Select com muitos nomes é impraticável
**Solução:** Implementar input com datalist ou select2-like

**Arquivo:** `features/loans/list-view.php`

### 4.3 Clarificar Cards de Empréstimos
**Problema:** Cards zerados e confusos
- "Empréstimos Ativos" vs "Total Emprestado" vs "A Receber" - não está claro

**Proposta de Cards:**
1. **Total de Empréstimos** - Quantidade total (todos status)
2. **Capital Emprestado (Ativos)** - Soma do valor PRINCIPAL dos ativos
3. **A Receber (Ativos)** - Valor total ainda pendente de receber
4. **Em Atraso** - Valor atrasado com multas

**Arquivo:** `features/loans/loan-service.php` e `list-view.php`

---

## 🟠 FASE 5: Clientes - Ordenação (30min)

### Objetivo
Ordenar clientes por dívida ativa (maior para menor)

**Arquivo:** `features/clients/client-service.php`
- Modificar query do `getAllClients()`
- Adicionar `ORDER BY active_debt DESC`

---

## 🟠 FASE 6: Relatórios - Clarificar Relacionamento dos Cards (1h)

### Problema
Cards não mostram visualmente que são soma/relacionados

**Cards atuais (Grid 2):**
1. Clientes com Empréstimos Ativos - OK
2. Valor Total Atrasado - ❓
3. Valor Pendente Em Dia - ❓
4. Total a Receber - ❓ (soma dos anteriores, não fica claro)

**Solução:**
- Adicionar indicadores visuais de soma
- Usar símbolos: "🔴 Atrasado + 🟡 Em Dia = 🔵 Total"
- Ou reorganizar visualmente com linhas/agrupamento
- Adicionar tooltips explicativos

**Arquivo:** `features/reports/cash-flow-view.php`

---

## 📋 Resumo de Prioridades

| Fase | Descrição | Tempo | Prioridade |
|------|-----------|-------|------------|
| 1 | Login Profissional | 30min | Alta |
| 2 | Dashboard - Cards | 1h | Alta |
| 3 | Carteiras - Filtros | 1h | Média |
| 4 | Empréstimos - Crítico | 2h | **CRÍTICA** |
| 5 | Clientes - Ordem | 30min | Baixa |
| 6 | Relatórios - Clareza | 1h | Média |

**Total estimado: 5-6 horas / 2-3 sessões**

---

## 🎯 Ordem de Execução Sugerida

1. **Sessão 1:** Fase 4 (Empréstimos - crítico) + Fase 1 (Login)
2. **Sessão 2:** Fase 2 (Dashboard) + Fase 6 (Relatórios)
3. **Sessão 3:** Fase 3 (Carteiras) + Fase 5 (Clientes)

---

**Criado:** <?= date('Y-m-d H:i') ?>
**Status:** Planejamento
