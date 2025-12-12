<?php
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/template-service.php';

Session::requireAuth();

$templateService = new WhatsAppTemplateService();

// Capturar filtros
$filters = [
    'category' => $_GET['category'] ?? '',
    'is_active' => $_GET['is_active'] ?? ''
];

$templates = $templateService->getAllTemplates($filters);

$pageTitle = 'Templates WhatsApp';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Templates WhatsApp</h1>
</div>

<?php if (empty($templates)): ?>
    <div class="empty-state">
        <div class="empty-icon">💬</div>
        <h3>Nenhum template encontrado</h3>
        <p>Configure os templates de WhatsApp do sistema.</p>
    </div>
<?php else: ?>
    <div style="display: grid; gap: 1.5rem;">
        <?php foreach ($templates as $template): ?>
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600;">
                            <?= htmlspecialchars($template['name']) ?>
                        </h3>
                        <?php if ($template['description']): ?>
                            <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                <?= htmlspecialchars($template['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php
                        $categoryLabels = [
                            'cobranca' => 'Cobrança',
                            'lembrete' => 'Lembrete'
                        ];
                        $categoryColors = [
                            'cobranca' => '#dc3545',
                            'lembrete' => '#ffc107'
                        ];
                        ?>
                        <span class="badge" style="background: <?= $categoryColors[$template['category']] ?>; color: white;">
                            <?= $categoryLabels[$template['category']] ?>
                        </span>
                        <?php if ($template['is_active']): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge" style="background: #6c757d; color: white;">Inativo</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="padding: 1.5rem; background: #f9fafb; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">
                    <div style="font-size: 0.875rem; color: #1f2937; white-space: pre-wrap; font-family: 'Courier New', monospace; background: white; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
<?= htmlspecialchars($template['message']) ?>
                    </div>
                </div>

                <div style="padding: 1rem 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <a href="<?= BASE_URL ?>/whatsapp/templates/edit?id=<?= $template['id'] ?>" class="btn btn-sm btn-secondary">
                        ✏️ Editar
                    </a>
                </div>

                <div style="padding: 0 1.5rem 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">
                        Variáveis disponíveis:
                    </p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{cliente}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{numero_parcela}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{total_parcelas}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{valor}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{vencimento}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{data_pagamento}</code>
                        <code style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #f3f4f6; border-radius: 0.25rem; color: #11C76F;">{total_pago}</code>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
