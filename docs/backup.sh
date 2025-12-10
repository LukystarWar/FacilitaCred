#!/bin/bash
# Script de Backup - FacilitaCred
# Execute diariamente via cron

BACKUP_DIR="/backups/facilita_cred"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="root"
DB_PASS=""
DB_NAME="facilita_cred"

mkdir -p $BACKUP_DIR

# Backup do banco de dados
echo "Iniciando backup do banco de dados..."
mysqldump -u $DB_USER -p"$DB_PASS" $DB_NAME > $BACKUP_DIR/facilita_cred_$DATE.sql

if [ $? -eq 0 ]; then
    echo "Backup do banco concluído: facilita_cred_$DATE.sql"
    gzip $BACKUP_DIR/facilita_cred_$DATE.sql
else
    echo "ERRO: Falha no backup do banco de dados"
    exit 1
fi

# Backup dos arquivos (opcional)
# tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/facilita_cred

# Remover backups com mais de 30 dias
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
echo "Backups antigos removidos"

echo "Backup completo!"
