<?php
/**
 * Dashboard View
 * Tela principal do sistema
 */

$pageTitle = 'Dashboard';
require_once SHARED_PATH . '/layout/header.php';

// Por enquanto, vamos criar um dashboard básico
// Será expandido na Fase 5 com métricas reais
?>

<div class="content-header">
    <h1 class="content-title">Dashboard</h1>
    <p class="content-subtitle">Visão geral do sistema</p>
</div>

<?php if (Session::hasFlash('success')): ?>
    <div class="alert alert-success">
        <?= sanitize(Session::getFlash('success')) ?>
    </div>
<?php endif; ?>

<?php if (Session::hasFlash('error')): ?>
    <div class="alert alert-error">
        <?= sanitize(Session::getFlash('error')) ?>
    </div>
<?php endif; ?>

<div class="grid grid-4">
    <div class="metric-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="metric-value">R$ 0,00</div>
        <div class="metric-label">Total em Carteiras</div>
    </div>

    <div class="metric-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <div class="metric-value">R$ 0,00</div>
        <div class="metric-label">Total Emprestado</div>
    </div>

    <div class="metric-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <div class="metric-value">R$ 0,00</div>
        <div class="metric-label">Total a Receber</div>
    </div>

    <div class="metric-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <div class="metric-value">R$ 0,00</div>
        <div class="metric-label">Lucro Total</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Bem-vindo ao <?= APP_NAME ?>!</h2>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 1rem; color: var(--gray-700);">
            Sistema de gestão de empréstimos está pronto para uso.
        </p>

        <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem; margin-top: 1.5rem;">🚀 Próximos passos:</h3>
        <ul style="color: var(--gray-700); line-height: 1.8;">
            <li><strong>1. Criar Carteiras:</strong> Acesse o menu "Carteiras" para criar suas carteiras de gestão</li>
            <li><strong>2. Cadastrar Clientes:</strong> Vá em "Clientes" para adicionar seus clientes</li>
            <li><strong>3. Registrar Empréstimos:</strong> Em "Empréstimos" você pode criar novos empréstimos</li>
            <li><strong>4. Acompanhar Relatórios:</strong> Visualize entradas, saídas e lucros em "Relatórios"</li>
        </ul>

        <div style="margin-top: 2rem; padding: 1rem; background: var(--gray-50); border-radius: var(--radius-md);">
            <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                💡 <strong>Dica:</strong> Este dashboard será preenchido automaticamente com métricas reais conforme você utilizar o sistema.
            </p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Atividade Recente</h3>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-500); text-align: center; padding: 2rem;">
                Nenhuma atividade registrada ainda.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Parcelas Próximas do Vencimento</h3>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-500); text-align: center; padding: 2rem;">
                Nenhuma parcela próxima do vencimento.
            </p>
        </div>
    </div>
</div>

<?php require_once SHARED_PATH . '/layout/footer.php'; ?>
