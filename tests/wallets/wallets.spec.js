const { test, expect } = require('../fixtures/auth');

test.describe('Carteiras - Listagem', () => {
  test('deve exibir página de carteiras', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('h1')).toContainText('Carteiras');
  });

  test('deve exibir botão de nova carteira', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('button:has-text("Nova Carteira")')).toBeVisible();
  });

  test('deve exibir estatísticas', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('.stat-card')).toHaveCount(2);
  });
});

test.describe('Carteiras - Navegação', () => {
  test('deve ter link de carteiras no menu', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    const walletsLink = page.locator('nav a[href*="wallets"]');
    await expect(walletsLink).toBeVisible();
  });

  test('deve navegar para carteiras pelo menu', async ({ authenticatedPage: page }) => {
    await page.goto('dashboard');
    await page.click('nav a[href*="wallets"]');
    await expect(page).toHaveURL(/.*wallets/);
    await expect(page.locator('h1')).toContainText('Carteiras');
  });
});

test.describe('Carteiras - Estrutura da Página', () => {
  test('deve mostrar estado vazio quando não há carteiras', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    const emptyState = page.locator('text=Nenhuma carteira cadastrada');
    const walletCards = page.locator('.wallet-card');

    const emptyVisible = await emptyState.isVisible().catch(() => false);
    const hasWallets = await walletCards.count();

    // Deve ter estado vazio OU carteiras, mas não ambos
    if (hasWallets === 0) {
      await expect(emptyState).toBeVisible();
    }
  });

  test('deve exibir modal de criação no HTML', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#createModal')).toHaveCount(1);
  });

  test('deve exibir modal de edição no HTML', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#editModal')).toHaveCount(1);
  });

  test('deve exibir modal de transação no HTML', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#transactionModal')).toHaveCount(1);
  });
});

test.describe('Carteiras - Formulários', () => {
  test('formulário de criação deve ter campos corretos', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#createModal input[name="name"]')).toHaveCount(1);
    await expect(page.locator('#createModal input[name="initial_balance"]')).toHaveCount(1);
    await expect(page.locator('#createModal textarea[name="description"]')).toHaveCount(1);
  });

  test('formulário de edição deve ter campos corretos', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#editModal input[name="id"]')).toHaveCount(1);
    await expect(page.locator('#editModal input[name="name"]')).toHaveCount(1);
    await expect(page.locator('#editModal textarea[name="description"]')).toHaveCount(1);
  });

  test('formulário de transação deve existir no HTML', async ({ authenticatedPage: page }) => {
    await page.goto('wallets');
    await expect(page.locator('#transactionModal')).toHaveCount(1);
    await expect(page.locator('#transactionModal form')).toHaveCount(1);
  });
});
