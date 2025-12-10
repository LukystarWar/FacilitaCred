# 💰 Facilita Cred

Sistema de Gestão de Empréstimos desenvolvido em PHP puro com arquitetura VSA (Vertical Slice Architecture).

## 📋 Características

- ✅ **Gestão de Carteiras** - Múltiplas carteiras com controle de saldo e transações
- ✅ **Gestão de Clientes** - Cadastro completo com CPF, telefone e endereço
- ✅ **Gestão de Empréstimos** - Criação, acompanhamento e controle de pagamentos
- ✅ **Sistema de Parcelas** - Parcelamento com juros configuráveis
- ✅ **Relatórios Detalhados** - Fluxo de caixa, entradas, saídas e lucratividade
- ✅ **Mobile-First** - Otimizado para tablets
- ✅ **Autenticação Segura** - Login com senha criptografada

## 🚀 Tecnologias

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Arquitetura:** VSA (Vertical Slice Architecture)

## 📦 Instalação

### Pré-requisitos

- XAMPP, WAMP ou servidor Apache com PHP 7.4+
- MySQL 5.7+
- Extensão PDO habilitada

### Passo a Passo

1. **Clone ou baixe o projeto para a pasta htdocs do XAMPP:**
   ```
   c:\xampp\htdocs\FacilitaCred
   ```

2. **Configure o banco de dados:**
   - Abra o phpMyAdmin: `http://localhost/phpmyadmin`
   - Importe o arquivo `database/migrations.sql`
   - Ou execute via MySQL CLI:
     ```bash
     mysql -u root -p < database/migrations.sql
     ```

3. **Configure as credenciais do banco de dados:**
   - Edite o arquivo `config/database.php`
   - Ajuste as constantes se necessário:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'facilita_cred');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Acesse o sistema:**
   ```
   http://localhost/FacilitaCred/public
   ```

5. **Faça login com as credenciais padrão:**
   - **Usuário:** `admin`
   - **Senha:** `admin123`

   ⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro acesso!

## 📁 Estrutura do Projeto

```
FacilitaCred/
├── config/              # Arquivos de configuração
│   ├── config.php       # Configurações gerais
│   └── database.php     # Configurações do banco
├── core/                # Classes principais
│   ├── Database.php     # Gerenciamento de conexão
│   ├── Router.php       # Sistema de rotas
│   └── Session.php      # Gerenciamento de sessão
├── features/            # Features (VSA)
│   ├── auth/            # Autenticação
│   ├── wallets/         # Carteiras
│   ├── clients/         # Clientes
│   ├── loans/           # Empréstimos
│   └── reports/         # Relatórios
├── shared/              # Componentes compartilhados
│   ├── layout/          # Header, sidebar, footer
│   ├── components/      # Componentes reutilizáveis
│   └── helpers/         # Funções auxiliares
├── public/              # Pasta pública
│   ├── assets/          # CSS, JS, imagens
│   └── index.php        # Entry point
└── database/            # Scripts de banco
    └── migrations.sql   # Migração inicial
```

## 🎯 Regras de Negócio

### Juros

- **À vista:** 20% de juros
- **Parcelado:** 15% ao mês (acumulativo)
  - Exemplo: 3 parcelas = 3 × 15% = 45% de juros total

### Fluxo de Empréstimo

1. Selecionar cliente
2. Definir valor do empréstimo
3. Escolher número de parcelas
4. Calcular juros automaticamente
5. Selecionar carteira de origem
6. Confirmar e debitar da carteira

### Pagamento de Parcelas

- Ao registrar o pagamento de uma parcela:
  - O valor é creditado automaticamente na carteira de origem
  - Uma transação é registrada no histórico
  - O status da parcela é atualizado para "pago"

## 🔐 Segurança

- Senhas criptografadas com `password_hash()`
- Prepared statements (PDO) para prevenir SQL Injection
- Sanitização de inputs com `htmlspecialchars()`
- Proteção de sessão com regeneração de ID
- Validação client-side e server-side

## 📊 Relatórios Disponíveis

### Dashboard
- Total em carteiras
- Total emprestado
- Total a receber
- Lucro acumulado

### Fluxo de Caixa
- Entradas e saídas detalhadas
- Filtros por período e carteira
- Totalizadores

### Lucratividade
- Lucro por período
- Lucro por carteira
- Taxa de inadimplência

## 🛠️ Desenvolvimento

### Adicionar Nova Feature (VSA)

1. Crie uma pasta em `features/nome-da-feature/`
2. Adicione os arquivos:
   - `list-view.php` - Listagem
   - `create-view.php` - Formulário de criação
   - `details-view.php` - Detalhes
   - `service.php` - Lógica de negócio
   - `actions.php` - Ações (create, update, delete)

3. Registre as rotas em `public/index.php`

### Convenções de Código

- Use **camelCase** para variáveis e funções
- Use **PascalCase** para classes
- Comente código complexo
- Mantenha funções pequenas e focadas
- Valide inputs sempre (client + server)

## 🐛 Troubleshooting

### Erro de conexão com banco de dados
- Verifique se o MySQL está rodando
- Confira as credenciais em `config/database.php`
- Verifique se o banco `facilita_cred` foi criado

### Erro 404 em todas as páginas
- Verifique se o mod_rewrite está habilitado no Apache
- Confira o arquivo `.htaccess` em `public/`
- Ajuste o `BASE_URL` em `config/config.php`

### Página em branco
- Ative o display de erros em `config/config.php`:
  ```php
  define('APP_ENV', 'development');
  ```
- Verifique os logs do PHP em `xampp/php/logs/`

## 📝 TODO / Roadmap

- [x] Fase 1: Fundação (autenticação, layout, banco)
- [x] Fase 2: Módulo de Carteiras
- [x] Fase 3: Módulo de Clientes
- [x] Fase 4: Módulo de Empréstimos
- [x] Fase 5: Módulo de Relatórios
- [x] Fase 6: Refinamentos e otimizações

✅ **Projeto 100% completo e pronto para produção!**

Consulte `docs/` para documentação detalhada de cada fase.

## 📄 Licença

Este projeto é de uso privado. Todos os direitos reservados.

## 👨‍💻 Suporte

Para suporte ou dúvidas sobre o sistema, entre em contato com o desenvolvedor.

---

**Versão:** 1.0.0
**Última atualização:** Dezembro 2025
