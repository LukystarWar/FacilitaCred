# Plano de Melhorias - FacilitaCred

Este documento contém o plano detalhado de melhorias do sistema, organizado por prioridade e complexidade.

---

## 🟢 FASE 1: Ajustes Simples e Rápidos (1-2 sessões)

### 1.1 Clientes - Centralizar Colunas
**Arquivo:** `features/clients/list-view.php`
- Centralizar colunas 'Empréstimos' e 'Ações' com seus headers (th)
- Adicionar `text-align: center` nas células correspondentes
- **Tempo estimado:** 5 minutos
- **Complexidade:** Baixa

### 1.2 Empréstimos - Paginação Padrão
**Arquivo:** `features/loans/list-view.php` ou controller
- Verificar paginação atual
- Ajustar para 20 itens por página (padrão do sistema)
- **Tempo estimado:** 10 minutos
- **Complexidade:** Baixa

### 1.3 Empréstimos - WhatsApp em Nova Aba
**Arquivos:**
- `features/loans/list-view.php`
- `features/loans/details-view.php`
- Qualquer outro local com botões WhatsApp
- Adicionar `target="_blank"` em todos os links WhatsApp
- **Tempo estimado:** 10 minutos
- **Complexidade:** Baixa

### 1.4 Relatórios - Paginação Padrão
**Arquivo:** `features/reports/cash-flow-view.php` ou controller
- Ajustar paginação para 20 itens por página
- **Tempo estimado:** 10 minutos
- **Complexidade:** Baixa

---

## 🟡 FASE 2: Melhorias de Descrição e Labels (1 sessão)

### 2.1 Relatórios Cash-Flow - Nome do Cliente
**Arquivos:**
- `features/reports/cash-flow-view.php`
- `features/reports/reports-service.php` (possivelmente)
- Adicionar nome do dono do empréstimo na coluna Descrição
- Formato sugerido: "Pagamento - Cliente: [Nome] (Parcela X/Y)"
- **Tempo estimado:** 20 minutos
- **Complexidade:** Média

### 2.2 Carteiras - Nome do Cliente
**Arquivos:**
- `features/wallets/list-view.php`
- `features/wallets/wallets-service.php` (possivelmente)
- Adicionar nome do dono do empréstimo na descrição
- Formato sugerido: "Empréstimo - Cliente: [Nome]"
- **Tempo estimado:** 20 minutos
- **Complexidade:** Média

---

## 🟡 FASE 3: Cards de Empréstimos - Lógica de Filtros (1-2 sessões)

### 3.1 Análise do Sistema Atual
- Verificar como os cards estão sendo calculados atualmente
- Entender quais filtros existem (status, busca, data, etc)
- Mapear onde acontece o cálculo dos totais
- **Tempo estimado:** 30 minutos
- **Complexidade:** Média

### 3.2 Refatoração dos Cards
**Arquivos:**
- `features/loans/list-view.php`
- `features/loans/loan-service.php`
- Controllers/Actions relevantes

**Objetivo:**
- Cards devem mostrar SEMPRE o total geral (não por página)
- Quando filtros são aplicados (status, data, busca), os cards devem refletir apenas os dados filtrados
- Exemplos:
  - Sem filtro: Cards mostram totais gerais do sistema
  - Filtro "Atrasados": Cards mostram apenas valores de empréstimos atrasados
  - Busca por cliente: Cards mostram apenas valores daquele cliente

**Implementação sugerida:**
1. Criar métodos no service para calcular totais com filtros opcionais
2. Passar os mesmos filtros da listagem para o cálculo dos cards
3. Atualizar a view para usar os novos métodos

- **Tempo estimado:** 1-2 horas
- **Complexidade:** Média-Alta

---

## 🟠 FASE 4: Modal de Pagamento - Exibir Multa (1 sessão)

### 4.1 Modal de Pagamento de Parcela
**Arquivos:**
- `features/loans/details-view.php` (modal)
- `features/loans/pay-installment-action.php`
- JavaScript que controla o modal

**Objetivo:**
- Quando parcela está atrasada, exibir valor da multa no modal
- Mostrar claramente: Valor original + Multa = Total
- O campo de ajuste (desconto/acréscimo) deve ser baseado no valor COM multa
- Não precisa colocar multa como opção no select de ajuste

**Estrutura sugerida:**
```
Valor da Parcela: R$ 100,00
Multa por Atraso: R$ 5,00
─────────────────────────────
Total a Pagar: R$ 105,00

[x] Aplicar ajuste
    Tipo: [Desconto ▼]
    Valor: R$ [____]
    Motivo: [_______]

Valor Final: R$ 105,00 (atualiza com ajuste)
```

- **Tempo estimado:** 1 hora
- **Complexidade:** Média

### 4.2 Modal de Quitação
**Arquivos:**
- `features/loans/details-view.php` (modal de quitar)
- `features/loans/settle-loan-action.php`

**Objetivo:**
- Verificar se também exibe multas de parcelas atrasadas
- Aplicar mesma lógica do modal de pagamento
- **Tempo estimado:** 30 minutos
- **Complexidade:** Média

---

## 🟠 FASE 5: Salvar Valor PAGO Correto (1 sessão)

### 5.1 Pagamento de Parcela
**Arquivos:**
- `features/loans/pay-installment-action.php`
- Tabela `loan_installments` - campo `amount_paid`

**Objetivo:**
- Após aplicar ajustes (desconto/acréscimo), salvar o valor que REALMENTE foi pago
- Não salvar o valor original da parcela
- Isso garante cálculos corretos de lucro, histórico, etc.

**Exemplo:**
- Valor parcela: R$ 100,00
- Multa: R$ 5,00
- Total: R$ 105,00
- Desconto aplicado: R$ 10,00
- **Valor a salvar em amount_paid: R$ 95,00**

- **Tempo estimado:** 30 minutos
- **Complexidade:** Média

### 5.2 Quitação de Empréstimo
**Arquivos:**
- `features/loans/settle-loan-action.php`

**Objetivo:**
- Verificar se a quitação também salva os valores corretos
- Aplicar mesma lógica de salvar valor PAGO após ajustes
- **Tempo estimado:** 30 minutos
- **Complexidade:** Média

---

## 🔴 FASE 6: Relatórios - Filtros e Lucro (2-3 sessões)

### 6.1 Adicionar Filtros no Cash-Flow
**Arquivos:**
- `features/reports/cash-flow-view.php`
- `features/reports/reports-service.php`
- Possível criação de `cash-flow-action.php`

**Filtros sugeridos:**
- Período (data início e fim)
- Tipo de transação (entrada/saída)
- Cliente (busca por nome)
- Status (pago/pendente)

- **Tempo estimado:** 1-2 horas
- **Complexidade:** Alta

### 6.2 Calcular e Exibir Lucro (Juros)
**Arquivos:**
- `features/reports/cash-flow-view.php`
- `features/reports/reports-service.php`

**Objetivo:**
- Calcular lucro total do período selecionado
- Lucro = Total de juros dos empréstimos no período
- Exibir em card destacado
- Considerar apenas pagamentos efetivamente realizados

**Cálculo:**
- Para cada empréstimo quitado/parcialmente pago no período:
  - Lucro = (Valor total pago) - (Valor principal emprestado)

- **Tempo estimado:** 1-2 horas
- **Complexidade:** Alta

---

## 🔴 FASE 7: Dashboard - Período dos Cards (1 sessão)

### 7.1 Análise do Dashboard Atual
**Arquivos:**
- `features/dashboard/index.php`
- `features/dashboard/dashboard-service.php`

**Objetivo:**
- Verificar quanto tempo o sistema acumula dados nos cards
- Identificar se é total geral, mensal, anual, etc.

- **Tempo estimado:** 20 minutos
- **Complexidade:** Baixa

### 7.2 Ajustar para Mês Atual
**Objetivo:**
- Modificar cards para mostrar apenas dados do mês atual
- Dashboard é visualização rápida, não precisa acumular histórico
- Manter clareza: "Estatísticas de [Mês/Ano Atual]"

**Cards a ajustar:**
- Total de empréstimos ativos
- Valor emprestado (mês atual)
- Lucro/Receita (mês atual)
- Parcelas vencendo
- Etc.

- **Tempo estimado:** 1 hora
- **Complexidade:** Média

---

## 🔴 FASE 8: Relatórios - Novos Cards Informativos (2-3 sessões)

### 8.1 Análise dos Cards Atuais
- Identificar quais cards existem
- Entender o que cada um mostra
- Listar confusões (ex: "Saldo" - não fica claro se é lucro ou disponível)

- **Tempo estimado:** 20 minutos
- **Complexidade:** Baixa

### 8.2 Criar Novos Cards
**Arquivo:** `features/reports/cash-flow-view.php` ou nova página de relatórios

**Cards sugeridos:**
1. **Clientes com Empréstimos Ativos**
   - Quantidade de clientes únicos com pelo menos 1 empréstimo ativo

2. **Valor Total Atrasado**
   - Soma de todas as parcelas vencidas e não pagas
   - Incluir multas

3. **Valor Total em Dia**
   - Soma de todas as parcelas pendentes ainda dentro do prazo

4. **Lucro Total do Período**
   - Total de juros recebidos no período selecionado
   - (já contemplado na Fase 6.2)

5. **Taxa de Inadimplência**
   - Percentual de parcelas atrasadas vs total de parcelas

- **Tempo estimado:** 2-3 horas
- **Complexidade:** Alta

### 8.3 Renomear e Clarificar Cards Existentes
**Objetivo:**
- Revisar todos os cards atuais
- Renomear para maior clareza
- Adicionar tooltips/descrições quando necessário

**Exemplos de melhorias:**
- "Saldo" → "Saldo Disponível" ou "Capital em Circulação"
- Adicionar legendas explicativas
- Usar ícones consistentes

- **Tempo estimado:** 1 hora
- **Complexidade:** Média

---

## 🔴 FASE 9: Cobrança em Massa (2-3 sessões)

### 9.1 Botão de Cobrança em Massa
**Arquivos:**
- `features/loans/list-view.php`
- Novo arquivo JavaScript para controlar múltiplas abas

**Objetivo:**
- Adicionar botão "Cobrar Todos Atrasados" visível quando filtro de atrasados está ativo
- Ao clicar, abrir uma aba para cada empréstimo atrasado da página atual
- Exemplo: Página com 20 atrasados = 20 abas
- Na página 2 com 4 atrasados = 4 abas ao clicar lá

**Implementação sugerida:**
1. Adicionar botão no topo da listagem (visível apenas com filtro ativo)
2. JavaScript que coleta todos os links WhatsApp da página
3. Loop que abre cada link em nova aba com delay (para não travar navegador)

**Considerações:**
- Navegadores bloqueiam múltiplas pop-ups
- Solicitar permissão do usuário antes
- Adicionar delay entre aberturas (100-200ms)
- Mostrar progresso (Abrindo 5/20...)

- **Tempo estimado:** 2-3 horas
- **Complexidade:** Alta

---

## 📋 Resumo de Estimativas

| Fase | Descrição | Tempo Estimado | Sessões |
|------|-----------|----------------|---------|
| 1 | Ajustes Simples | 35 min | 1 |
| 2 | Descrições e Labels | 40 min | 1 |
| 3 | Cards com Filtros | 1-2h | 1-2 |
| 4 | Modal com Multa | 1.5h | 1 |
| 5 | Salvar Valor Pago | 1h | 1 |
| 6 | Filtros e Lucro | 2-4h | 2-3 |
| 7 | Dashboard Mês Atual | 1.5h | 1 |
| 8 | Novos Cards | 3-4h | 2-3 |
| 9 | Cobrança em Massa | 2-3h | 2-3 |

**Total estimado: 6-10 sessões**

---

## 🎯 Ordem de Execução Recomendada

1. **Sessão 1:** Fase 1 (ajustes simples) + Fase 2 (descrições)
2. **Sessão 2:** Fase 3 (cards com filtros)
3. **Sessão 3:** Fase 4 (modal multa) + Fase 5 (valor pago)
4. **Sessão 4:** Fase 7 (dashboard mês atual)
5. **Sessão 5-6:** Fase 6 (filtros relatórios + lucro)
6. **Sessão 7-8:** Fase 8 (novos cards relatórios)
7. **Sessão 9:** Fase 9 (cobrança em massa)

---

## 📝 Notas Importantes

- Sempre fazer backup antes de mudanças grandes
- Testar cada funcionalidade após implementação
- Fazer commits separados por feature
- Documentar mudanças em queries SQL
- Considerar impacto em performance (especialmente cálculos de totais)
- Validar comportamento com dados reais do sistema

---

**Última atualização:** 2025-12-12
**Status:** Planejamento inicial
