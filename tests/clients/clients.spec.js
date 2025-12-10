const { test, expect } = require('../fixtures/auth');

test.describe('Clientes - Listagem', () => {
  test('deve exibir página de clientes', async ({ authenticatedPage: page }) => {
    await page.goto('clients');
    await expect(page.locator('h1')).toContainText('Clientes');
  });

  test('deve ter link de clientes no menu', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await expect(page.locator('nav a[href*="clients"]')).toBeVisible();
  });
});
