# 📦 Instruções de Instalação - FacilitaCred

## Passo 1: Instalar o Banco de Dados

Execute o script de migração:

```bash
"c:\xampp\mysql\bin\mysql.exe" -u root -e "SOURCE c:/xampp/htdocs/FacilitaCred/database/migrations_v2.sql"
```

## Passo 2: Configurar a Senha do Admin

⚠️ **IMPORTANTE:** O hash da senha no SQL pode não funcionar devido a problemas de escape.

Execute o script PHP para atualizar a senha:

```bash
php c:\xampp\htdocs\FacilitaCred\update_admin_password.php
```

Você verá uma mensagem de sucesso:
```
✅ Senha atualizada com sucesso!
✅ TESTE DE LOGIN: SUCESSO!
```

## Passo 3: Acessar o Sistema

1. Abra o navegador
2. Acesse: `http://localhost/FacilitaCred/public`
3. Faça login com:
   - **Usuário:** `admin`
   - **Senha:** `admin123`

## Verificação Rápida

Se você ainda não conseguir logar, execute este comando para verificar:

```bash
php c:\xampp\htdocs\FacilitaCred\test_password.php
```

## Resetar o Banco (Se Necessário)

```bash
# Apagar banco
"c:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS facilita_cred;"

# Recriar banco
"c:\xampp\mysql\bin\mysql.exe" -u root -e "SOURCE c:/xampp/htdocs/FacilitaCred/database/migrations_v2.sql"

# Atualizar senha
php c:\xampp\htdocs\FacilitaCred\update_admin_password.php
```

## Problemas Comuns

### "Usuário ou senha incorretos"

**Solução:** Execute o script `update_admin_password.php`

```bash
php c:\xampp\htdocs\FacilitaCred\update_admin_password.php
```

### "Database not found"

**Solução:** Execute a migração novamente

```bash
"c:\xampp\mysql\bin\mysql.exe" -u root -e "SOURCE c:/xampp/htdocs/FacilitaCred/database/migrations_v2.sql"
```

### "XAMPP não está rodando"

**Solução:**
1. Abra o XAMPP Control Panel
2. Inicie os serviços Apache e MySQL
3. Tente novamente

## Estrutura Criada

Após a instalação, você terá:

**Banco:** `facilita_cred`

**Tabelas:**
- ✅ users (usuários)
- ✅ wallets (carteiras)
- ✅ transactions (transações)
- ✅ clients (clientes)
- ✅ loans (empréstimos)
- ✅ installments (parcelas)

**Usuário padrão:**
- Username: `admin`
- Senha: `admin123`

## ⚠️ Segurança

**IMPORTANTE:** Após o primeiro acesso, altere a senha padrão!

1. Faça login
2. Acesse Configurações (quando disponível na Fase 6)
3. Altere a senha

---

**Pronto!** Seu sistema está instalado e pronto para uso! 🎉
