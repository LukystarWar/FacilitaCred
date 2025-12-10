const { test: base } = require('@playwright/test');

exports.test = base.extend({
  authenticatedPage: async ({ page }, use) => {
    // Login automático
    await page.goto('login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    await use(page);
  },
});

exports.expect = require('@playwright/test').expect;
