<?php
require_once __DIR__ . '/../../core/Session.php';

Session::requireAuth();

$pageTitle = 'Novo Template WhatsApp';
require_once __DIR__ . '/../../shared/layout/header.php';
?>

<div class="page-header">
    <h1>Novo Template WhatsApp</h1>
    <a href="<?= BASE_URL ?>/whatsapp/templates" class="btn btn-secondary">
        ← Voltar
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2>Criar Template</h2>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/whatsapp/templates/create" style="padding: 1.5rem;">
        <div style="display: grid; gap: 1.5rem;">
            <div>
                <label for="name" class="form-label">Nome do Template *</label>
                <input type="text" id="name" name="name" class="form-control" required
                       placeholder="Ex: Cobrança Amigável">
            </div>

            <div>
                <label for="description" class="form-label">Descrição</label>
                <textarea id="description" name="description" class="form-control" rows="2"
                          placeholder="Breve descrição do propósito deste template"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="category" class="form-label">Categoria *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="cobranca">Cobrança</option>
                        <option value="lembrete">Lembrete</option>
                    </select>
                </div>

                <div>
                    <label for="is_active" class="form-label">Status *</label>
                    <select id="is_active" name="is_active" class="form-control" required>
                        <option value="1" selected>Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="message" class="form-label">Mensagem do Template *</label>
                <textarea id="message" name="message" class="form-control" rows="10" required
                          placeholder="Digite a mensagem do template..."></textarea>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                    Use as variáveis abaixo para personalizar a mensagem:
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
                    <code class="variable-tag" onclick="insertVariable('{cliente}')">{cliente}</code>
                    <code class="variable-tag" onclick="insertVariable('{numero_parcela}')">{numero_parcela}</code>
                    <code class="variable-tag" onclick="insertVariable('{total_parcelas}')">{total_parcelas}</code>
                    <code class="variable-tag" onclick="insertVariable('{valor}')">{valor}</code>
                    <code class="variable-tag" onclick="insertVariable('{vencimento}')">{vencimento}</code>
                    <code class="variable-tag" onclick="insertVariable('{data_pagamento}')">{data_pagamento}</code>
                    <code class="variable-tag" onclick="insertVariable('{total_pago}')">{total_pago}</code>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <a href="<?= BASE_URL ?>/whatsapp/templates" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar Template</button>
            </div>
        </div>
    </form>
</div>

<style>
.variable-tag {
    cursor: pointer;
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    background: #f3f4f6;
    border-radius: 0.25rem;
    color: #11C76F;
    transition: all 0.2s;
    border: 1px solid #e5e7eb;
}
.variable-tag:hover {
    background: #11C76F;
    color: white;
    border-color: #0E9F59;
}
</style>

<script>
function insertVariable(variable) {
    const textarea = document.getElementById('message');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);
    textarea.value = before + variable + after;
    textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    textarea.focus();
}
</script>

<?php require_once __DIR__ . '/../../shared/layout/footer.php'; ?>
