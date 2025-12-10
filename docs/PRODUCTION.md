# Guia de Deploy para Produção

## Pré-requisitos

- Servidor Apache 2.4+
- PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.3+
- mod_rewrite habilitado
- HTTPS configurado (recomendado)

## Passo 1: Configurar Ambiente

1. Clone/copie o projeto para o servidor:
   ```bash
   /var/www/facilita_cred/
   ```

2. Configure permissões:
   ```bash
   chown -R www-data:www-data /var/www/facilita_cred
   chmod -R 755 /var/www/facilita_cred
   chmod -R 775 /var/www/facilita_cred/logs
   ```

## Passo 2: Configurar Banco de Dados

1. Crie o banco de dados:
   ```bash
   mysql -u root -p -e "CREATE DATABASE facilita_cred CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. Importe a estrutura:
   ```bash
   mysql -u root -p facilita_cred < database/migrations_v2.sql
   ```

3. Crie usuário dedicado:
   ```sql
   CREATE USER 'facilita_user'@'localhost' IDENTIFIED BY 'SENHA_FORTE_AQUI';
   GRANT ALL PRIVILEGES ON facilita_cred.* TO 'facilita_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

## Passo 3: Configurar Aplicação

1. Edite `config/config.php`:
   ```php
   define('APP_ENV', 'production');
   define('BASE_URL', 'https://seudominio.com');
   ```

2. Edite `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'facilita_cred');
   define('DB_USER', 'facilita_user');
   define('DB_PASS', 'SENHA_FORTE_AQUI');
   ```

3. Configure senha do admin:
   ```bash
   php docs/update_admin_password.php
   ```

## Passo 4: Configurar Apache

1. Crie VirtualHost (`/etc/apache2/sites-available/facilita_cred.conf`):
   ```apache
   <VirtualHost *:80>
       ServerName seudominio.com
       ServerAdmin admin@seudominio.com
       DocumentRoot /var/www/facilita_cred/public

       <Directory /var/www/facilita_cred/public>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/facilita_cred_error.log
       CustomLog ${APACHE_LOG_DIR}/facilita_cred_access.log combined

       # Redirecionar para HTTPS
       RewriteEngine on
       RewriteCond %{SERVER_NAME} =seudominio.com
       RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
   </VirtualHost>
   ```

2. Habilite o site:
   ```bash
   a2ensite facilita_cred
   a2enmod rewrite headers deflate expires
   systemctl reload apache2
   ```

## Passo 5: Configurar HTTPS (Let's Encrypt)

```bash
apt install certbot python3-certbot-apache
certbot --apache -d seudominio.com
```

## Passo 6: Segurança Adicional

### 1. Proteger Pastas Sensíveis

Crie `.htaccess` em `/var/www/facilita_cred/`:
```apache
Options -Indexes
<FilesMatch "\.">
    Require all denied
</FilesMatch>
```

### 2. Configurar Firewall

```bash
ufw allow 'Apache Full'
ufw enable
```

### 3. Desabilitar Funções PHP Perigosas

No `php.ini`:
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

### 4. Limitar Taxa de Requisições (mod_evasive)

```bash
apt install libapache2-mod-evasive
a2enmod evasive
systemctl restart apache2
```

## Passo 7: Backup Automático

1. Crie script de backup (`/usr/local/bin/backup_facilita_cred.sh`):
   ```bash
   #!/bin/bash
   BACKUP_DIR="/backups/facilita_cred"
   DATE=$(date +%Y%m%d_%H%M%S)

   mkdir -p $BACKUP_DIR

   # Backup do banco
   mysqldump -u facilita_user -p'SENHA' facilita_cred > $BACKUP_DIR/db_$DATE.sql

   # Backup dos arquivos
   tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/facilita_cred

   # Manter apenas últimos 30 dias
   find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
   find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
   ```

2. Torne executável:
   ```bash
   chmod +x /usr/local/bin/backup_facilita_cred.sh
   ```

3. Adicione ao cron (backup diário às 2h):
   ```bash
   crontab -e
   0 2 * * * /usr/local/bin/backup_facilita_cred.sh
   ```

## Passo 8: Monitoramento

### Logs de Erro

Monitore regularmente:
```bash
tail -f /var/www/facilita_cred/logs/error.log
tail -f /var/log/apache2/facilita_cred_error.log
```

### Espaço em Disco

```bash
df -h
```

### Processos MySQL

```bash
mysqladmin -u root -p processlist
```

## Checklist de Segurança

- [ ] Alterar senha padrão do admin
- [ ] Usar HTTPS (SSL/TLS)
- [ ] Configurar firewall
- [ ] Restringir acesso ao banco de dados
- [ ] Desabilitar listagem de diretórios
- [ ] Configurar backup automático
- [ ] Definir APP_ENV como 'production'
- [ ] Desabilitar display_errors no PHP
- [ ] Atualizar regularmente o sistema
- [ ] Monitorar logs de erro
- [ ] Implementar limite de taxa de requisições
- [ ] Usar senhas fortes
- [ ] Manter PHP e MySQL atualizados

## Manutenção Regular

### Diária
- Verificar logs de erro
- Verificar backups

### Semanal
- Atualizar sistema operacional
- Verificar espaço em disco

### Mensal
- Atualizar dependências PHP
- Revisar permissões de arquivos
- Testar restauração de backup

## Troubleshooting

### Erro 500
1. Verificar logs: `tail -f logs/error.log`
2. Verificar permissões
3. Verificar configuração do banco

### Erro de Conexão com Banco
1. Verificar credenciais em `config/database.php`
2. Verificar se MySQL está rodando: `systemctl status mysql`
3. Verificar firewall: `ufw status`

### Página em Branco
1. Verificar `APP_ENV` em `config/config.php`
2. Ativar temporariamente display_errors para debug
3. Verificar logs do PHP

## Suporte

Para problemas ou dúvidas:
- Verificar documentação em `docs/`
- Consultar logs de erro
- Verificar permissões e configurações

---

**IMPORTANTE:** Sempre teste em ambiente de homologação antes de aplicar em produção!
