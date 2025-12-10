<?php
/**
 * Login View
 * Tela de login
 */

$pageTitle = 'Login';
require_once SHARED_PATH . '/layout/header.php';
?>

<style>
    /* Estilos específicos para a página de login */
    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #11C76F 0%, #0E9F59 100%);
        padding: var(--spacing-lg);
    }

    .login-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: var(--spacing-2xl);
        width: 100%;
        max-width: 420px;
    }

    .login-header {
        text-align: center;
        margin-bottom: var(--spacing-2xl);
    }

    .login-logo {
        font-size: 4rem;
        margin-bottom: var(--spacing-md);
    }

    .login-title {
        font-size: var(--font-size-2xl);
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: var(--spacing-xs);
    }

    .login-subtitle {
        color: var(--gray-600);
        font-size: var(--font-size-sm);
    }

    .login-form {
        margin-bottom: var(--spacing-lg);
    }

    .login-footer {
        text-align: center;
        font-size: var(--font-size-sm);
        color: var(--gray-500);
    }

    @media (max-width: 480px) {
        .login-card {
            padding: var(--spacing-xl);
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">💰</div>
            <h1 class="login-title"><?= APP_NAME ?></h1>
            <p class="login-subtitle">Sistema de Gestão de Empréstimos</p>
        </div>

        <?php if (Session::hasFlash('error')): ?>
            <div class="alert alert-error">
                <?= sanitize(Session::getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (Session::hasFlash('success')): ?>
            <div class="alert alert-success">
                <?= sanitize(Session::getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= Router::url('/login') ?>" class="login-form">
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Digite seu usuário"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Senha</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Digite sua senha"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">
                Entrar
            </button>
        </form>

        <div class="login-footer">
            <p>Desenvolvido para gestão eficiente de empréstimos</p>
            <p style="margin-top: 0.5rem; font-size: 0.75rem;">v<?= APP_VERSION ?></p>
        </div>
    </div>
</div>

<?php require_once SHARED_PATH . '/layout/footer.php'; ?>
