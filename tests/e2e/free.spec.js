// @ts-check
const { test, expect } = require('@playwright/test');

const WP_USERNAME = process.env.WP_USERNAME;
const WP_PASSWORD = process.env.WP_PASSWORD;

/**
 * Find the index of the first route in a namespace that matches a checkbox state.
 *
 * @param {import('@playwright/test').Locator} namespaceLocator
 * @param {boolean} checked - true = find a disabled endpoint, false = find an enabled one
 */
async function findRouteIndex(namespaceLocator, checked) {
  const routes = namespaceLocator.locator('.rest-api-route');
  const count = await routes.count();
  for (let i = 0; i < count; i++) {
    const isChecked = await routes.nth(i).locator('input[type="checkbox"]').isChecked();
    if (isChecked === checked) return i;
  }
  return -1;
}

test.describe('Endpoint Manager — Free', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', WP_USERNAME);
    await page.fill('#user_pass', WP_PASSWORD);
    await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');
    await page.goto('/wp-admin/admin.php?page=wpbyem');
  });

  // ---------------------------------------------------------------------------
  // TEST: View all REST API namespaces
  // ---------------------------------------------------------------------------
  test('shows namespaces collapsed, expands on click, collapses on second click, and shows static endpoint details when expanded', async ({ page }) => {
    const firstNamespace = page.locator('.rest-api-namespace').first();

    // Collapsed by default
    const toggle = firstNamespace.locator('.namespace-toggle');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(firstNamespace.locator('.rest-api-routes')).not.toBeVisible();

    // Expands on click
    await firstNamespace.locator('.namespace-header').click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(firstNamespace.locator('.rest-api-routes')).toBeVisible();

    // Each endpoint has a toggle switch, a status badge, and a preview button
    const firstEndpoint = firstNamespace.locator('.rest-api-route').first();
    await expect(firstEndpoint.locator('.toggle-switch')).toBeVisible();
    await expect(firstEndpoint.locator('.route-status')).toBeVisible();
    await expect(firstEndpoint.locator('.route-preview')).toBeVisible();

    // Collapses on second click
    await firstNamespace.locator('.namespace-header').click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(firstNamespace.locator('.rest-api-routes')).not.toBeVisible();
  });

  // ---------------------------------------------------------------------------
  // TEST: Disable an endpoint
  // ---------------------------------------------------------------------------
  test('toggling an enabled endpoint off shows a confirmation dialog on save', async ({ page }) => {
    const firstNamespace = page.locator('.rest-api-namespace').first();
    await firstNamespace.locator('.namespace-header').click();

    // Find first enabled endpoint by index (stable — avoids dynamic filter re-evaluation)
    const enabledIndex = await findRouteIndex(firstNamespace, false);
    expect(enabledIndex).toBeGreaterThan(-1);

    const route = firstNamespace.locator('.rest-api-route').nth(enabledIndex);
    const checkbox = route.locator('input[type="checkbox"]');

    await expect(route.locator('.route-status')).toHaveClass(/enabled/);

    // Toggle off
    await route.locator('.toggle-switch').click();
    await expect(checkbox).toBeChecked();

    // Save — confirmation dialog should appear mentioning the disabled count
    let dialogMessage = '';
    page.once('dialog', async (dialog) => {
      dialogMessage = dialog.message();
      await dialog.dismiss(); // Cancel — keep state clean
    });

    await page.locator('#wpbyem-form [type="submit"]').click();

    expect(dialogMessage).toMatch(/block 1 endpoint/i);
  });

  // ---------------------------------------------------------------------------
  // TEST: Enable an endpoint
  // ---------------------------------------------------------------------------
  test('toggling a disabled endpoint on saves without a confirmation dialog', async ({ page }) => {
    const firstNamespace = page.locator('.rest-api-namespace').first();
    await firstNamespace.locator('.namespace-header').click();

    // Setup: disable an endpoint so we have one to re-enable
    const enabledIndex = await findRouteIndex(firstNamespace, false);
    expect(enabledIndex).toBeGreaterThan(-1);

    await firstNamespace.locator('.rest-api-route').nth(enabledIndex).locator('.toggle-switch').click();

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#wpbyem-form [type="submit"]').click();
    await page.waitForURL('**/wp-admin/**');

    // Reload and find the now-disabled endpoint
    await page.goto('/wp-admin/admin.php?page=wpbyem');
    await firstNamespace.locator('.namespace-header').click();

    const disabledIndex = await findRouteIndex(firstNamespace, true);
    expect(disabledIndex).toBeGreaterThan(-1);

    const route = firstNamespace.locator('.rest-api-route').nth(disabledIndex);
    const checkbox = route.locator('input[type="checkbox"]');

    await expect(route.locator('.route-status')).toHaveClass(/disabled/);

    // Toggle on
    await route.locator('.toggle-switch').click();
    await expect(checkbox).not.toBeChecked();

    // Save — no confirmation dialog should appear
    let dialogShown = false;
    page.on('dialog', () => { dialogShown = true; });

    await page.locator('#wpbyem-form [type="submit"]').click();
    await page.waitForURL('**/wp-admin/**');

    expect(dialogShown).toBe(false);
  });

  // ---------------------------------------------------------------------------
  // TEST: Preview an endpoint
  // ---------------------------------------------------------------------------
  test('preview opens endpoint in a new tab and reflects its enabled or disabled state', async ({ page, context }) => {
    const firstNamespace = page.locator('.rest-api-namespace').first();
    await firstNamespace.locator('.namespace-header').click();

    // Preview an enabled endpoint — should return data, not a forbidden error
    const enabledIndex = await findRouteIndex(firstNamespace, false);
    expect(enabledIndex).toBeGreaterThan(-1);

    const [enabledTab] = await Promise.all([
      context.waitForEvent('page'),
      firstNamespace.locator('.rest-api-route').nth(enabledIndex).locator('.route-preview').click(),
    ]);

    await enabledTab.waitForLoadState('domcontentloaded');
    const enabledBody = await enabledTab.locator('body').innerText();
    expect(enabledBody).not.toMatch(/"code"\s*:\s*"rest_forbidden"/i);
    await enabledTab.close();

    // Preview a disabled endpoint — should return a forbidden error
    const disabledIndex = await findRouteIndex(firstNamespace, true);

    if (disabledIndex > -1) {
      const [disabledTab] = await Promise.all([
        context.waitForEvent('page'),
        firstNamespace.locator('.rest-api-route').nth(disabledIndex).locator('.route-preview').click(),
      ]);

      await disabledTab.waitForLoadState('domcontentloaded');
      const disabledBody = await disabledTab.locator('body').innerText();
      expect(disabledBody).toMatch(/"code"\s*:\s*"rest_forbidden"/i);
      await disabledTab.close();
    }
  });

  // ---------------------------------------------------------------------------
  // TEST: Sidebar links
  // ---------------------------------------------------------------------------
  test('sidebar links point to the correct URLs and open in a new tab', async ({ page }) => {
    const upgradeLink = page.locator('.wpbuoy-upgrade-widget a.button-primary');
    const faqLink     = page.locator('.wpbuoy-support-widget a').filter({ hasText: 'FAQ' });
    const docsLink    = page.locator('.wpbuoy-support-widget a').filter({ hasText: 'Documentation' });
    const supportLink = page.locator('.wpbuoy-support-widget a').filter({ hasText: 'Support' });

    await expect(upgradeLink).toHaveAttribute('href', 'https://wpbuoy.com/product/endpoint-manager/');
    await expect(upgradeLink).toHaveAttribute('target', '_blank');

    await expect(faqLink).toHaveAttribute('href', 'https://wpbuoy.com/product/endpoint-manager/#faqs');
    await expect(faqLink).toHaveAttribute('target', '_blank');

    await expect(docsLink).toHaveAttribute('href', 'https://wpbuoy.com/endpoint-manager/documentation/');
    await expect(docsLink).toHaveAttribute('target', '_blank');

    await expect(supportLink).toHaveAttribute('href', 'https://wpbuoy.com/my-account/support/');
    await expect(supportLink).toHaveAttribute('target', '_blank');
  });

});
