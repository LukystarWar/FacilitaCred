const { test, expect } = require('../fixtures/auth');

test.describe('Relatórios - Dashboard', () => {
  test('deve exibir dashboard', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await expect(page.locator('h1')).toContainText('Dashboard');
  });

  test('deve exibir estatísticas gerais', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await expect(page.locator('.stat-card')).toHaveCount(4);
  });

  test('deve ter links de navegação', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await expect(page.locator('nav a[href*="dashboard"]')).toBeVisible();
    await expect(page.locator('nav a[href*="wallets"]')).toBeVisible();
    await expect(page.locator('nav a[href*="clients"]')).toBeVisible();
    await expect(page.locator('nav a[href*="loans"]')).toBeVisible();
  });
});

test.describe('Relatórios - Fluxo de Caixa', () => {
  test('deve exibir página de fluxo de caixa', async ({ authenticatedPage: page }) => {
    await page.goto('reports/cash-flow');
    await expect(page.locator('h1')).toContainText('Fluxo de Caixa');
  });
});

test.describe('Relatórios - Lucro', () => {
  test('deve exibir página de relatório de lucro', async ({ authenticatedPage: page }) => {
    await page.goto('reports/profit');
    await expect(page.locator('h1')).toContainText('Relatório');
  });
});
