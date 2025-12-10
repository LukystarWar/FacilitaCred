const { test, expect } = require('@playwright/test');

test.describe('Autenticação - Login', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('login');
  });

  test('deve exibir formulário de login', async ({ page }) => {
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('deve fazer login com credenciais válidas', async ({ page }) => {
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('deve mostrar erro com credenciais inválidas', async ({ page }) => {
    await page.fill('input[name="username"]', 'usuario_invalido');
    await page.fill('input[name="password"]', 'senha_errada');
    await page.click('button[type="submit"]');

    await expect(page.locator('.alert-error')).toBeVisible();
  });

  test('deve validar campos obrigatórios', async ({ page }) => {
    await page.click('button[type="submit"]');

    const usernameInput = page.locator('input[name="username"]');
    await expect(usernameInput).toHaveAttribute('required', '');
  });

  test('deve redirecionar para dashboard se já autenticado', async ({ page }) => {
    // Login
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    // Tentar acessar login novamente
    await page.goto('login');
    await expect(page).toHaveURL(/.*dashboard/);
  });
});

test.describe('Autenticação - Logout', () => {
  test('deve fazer logout com sucesso', async ({ page }) => {
    // Login
    await page.goto('login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    // Logout
    await page.click('a[href*="logout"]');
    await expect(page).toHaveURL(/.*login/);
  });

  test('deve bloquear acesso a páginas protegidas após logout', async ({ page }) => {
    // Login
    await page.goto('login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    // Logout
    await page.click('a[href*="logout"]');

    // Tentar acessar página protegida
    await page.goto('wallets');
    await expect(page).toHaveURL(/.*login/);
  });
});
