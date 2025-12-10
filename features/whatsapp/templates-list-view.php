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
    <a href="<?= BASE_URL ?>/whatsapp/templates/create" class="btn btn-primary">
        + Novo Template
    </a>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">🔍 Filtros</h3>
    </div>
    <form method="GET" action="<?= BASE_URL ?>/whatsapp/templates" style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Categoria</label>
                <select name="category" class="form-control">
                    <option value="">Todas</option>
                    <option value="cobranca" <?= $filters['category'] === 'cobranca' ? 'selected' : '' ?>>Cobrança</option>
                    <option value="lembrete" <?= $filters['category'] === 'lembrete' ? 'selected' : '' ?>>Lembrete</option>
                    <option value="confirmacao" <?= $filters['category'] === 'confirmacao' ? 'selected' : '' ?>>Confirmação</option>
                    <option value="outros" <?= $filters['category'] === 'outros' ? 'selected' : '' ?>>Outros</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Status</label>
                <select name="is_active" class="form-control">
                    <option value="">Todos</option>
                    <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Ativos</option>
                    <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inativos</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= BASE_URL ?>/whatsapp/templates" class="btn btn-secondary">Limpar</a>
        </div>
    </form>
</div>

<?php if (empty($templates)): ?>
    <div class="empty-state">
        <div class="empty-icon">💬</div>
        <h3>Nenhum template encontrado</h3>
        <p>Crie seu primeiro template de WhatsApp.</p>
        <a href="<?= BASE_URL ?>/whatsapp/templates/create" class="btn btn-primary">
            + Criar Primeiro Template
        </a>
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
                            'lembrete' => 'Lembrete',
                            'confirmacao' => 'Confirmação',
                            'outros' => 'Outros'
                        ];
                        $categoryColors = [
                            'cobranca' => '#dc3545',
                            'lembrete' => '#ffc107',
                            'confirmacao' => '#28a745',
                            'outros' => '#6c757d'
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
                    <a href="<?= BASE_URL ?>/whatsapp/templates/delete?id=<?= $template['id'] ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Tem certeza que deseja excluir este template?')">
                        🗑️ Excluir
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
