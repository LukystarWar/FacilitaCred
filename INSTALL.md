# 🚀 Guia Rápido de Instalação - Facilita Cred

## Método 1: Instalação Automática (Recomendado)

1. **Certifique-se que o XAMPP está rodando** (Apache + MySQL)

2. **Acesse o instalador automático:**
   ```
   http://localhost/FacilitaCred/database/install.php
   ```

3. **Aguarde a instalação** (cria o banco, tabelas, views, triggers e dados iniciais)

4. **Delete o arquivo de instalação** por segurança:
   ```
   database/install.php
   ```

5. **Acesse o sistema:**
   ```
   http://localhost/FacilitaCred/public
   ```

6. **Faça login:**
   - Usuário: `admin`
   - Senha: `admin123`

---

## Método 2: Instalação Manual

### 1. Criar o Banco de Dados

Abra o phpMyAdmin em `http://localhost/phpmyadmin` e execute:

```sql
CREATE DATABASE facilita_cred CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Importar o Schema

Na aba "Importar" do phpMyAdmin, selecione o arquivo:
```
database/migrations.sql
```

### 3. Verificar a Instalação

Execute no SQL do phpMyAdmin:
```sql
USE facilita_cred;
SHOW TABLES;
```

Você deve ver 6 tabelas:
- users
- wallets
- wallet_transactions
- clients
- loans
- loan_installments

### 4. Acessar o Sistema

```
http://localhost/FacilitaCred/public
```

**Login:**
- Usuário: `admin`
- Senha: `admin123`

---

## ⚙️ Configurações Avançadas

### Alterar Credenciais do Banco

Edite o arquivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'facilita_cred');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Alterar URL Base

Se o projeto não estiver em `htdocs/FacilitaCred`, edite `config/config.php`:

```php
define('BASE_URL', 'http://localhost/SEU_CAMINHO/public');
```

E ajuste também em `public/index.php` a variável `$basePath`.

---

## 🔧 Troubleshooting

### Erro 404 em todas as páginas
✅ Verifique se o `mod_rewrite` está habilitado no Apache
✅ Confira se o arquivo `.htaccess` está em `public/`

### Erro de conexão com banco
✅ Verifique se o MySQL está rodando
✅ Confira as credenciais em `config/database.php`
✅ Verifique se o banco foi criado

### Página em branco
✅ Ative debug em `config/config.php` (set APP_ENV = 'development')
✅ Verifique os logs do PHP
✅ Confira permissões de arquivos

---

## 📱 Teste da Instalação

Após fazer login, você deve ver:
- ✅ Dashboard com métricas (zeradas inicialmente)
- ✅ Sidebar com menu de navegação
- ✅ 5 opções: Dashboard, Carteiras, Clientes, Empréstimos, Relatórios
- ✅ Opção de Sair

---

## 🎯 Próximos Passos

1. **Altere a senha padrão** (funcionalidade será implementada)
2. **Crie sua primeira carteira** (módulo será implementado na Fase 2)
3. **Cadastre seus clientes** (módulo será implementado na Fase 3)
4. **Registre empréstimos** (módulo será implementado na Fase 4)

---

## 📞 Suporte

Consulte o arquivo `README.md` para documentação completa.

**Status Atual:** Fase 1 Completa ✅
