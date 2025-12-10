const { test, expect } = require('../fixtures/auth');

test.describe('Empréstimos - Listagem', () => {
  test('deve exibir página de empréstimos', async ({ authenticatedPage: page }) => {
    await page.goto('loans');
    await expect(page.locator('h1')).toContainText('Empréstimos');
  });

  test('deve exibir link de novo empréstimo', async ({ authenticatedPage: page }) => {
    await page.goto('loans');
    await expect(page.locator('a:has-text("Novo Empréstimo")')).toBeVisible();
  });

  test('deve ter link de empréstimos no menu', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await expect(page.locator('nav a[href*="loans"]')).toBeVisible();
  });
});
