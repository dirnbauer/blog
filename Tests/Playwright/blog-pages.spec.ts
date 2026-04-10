import { expect, test } from '@playwright/test';

const baseUrl = process.env.BLOG_BASE_URL;
const pages = [
    {
        name: 'blog list page',
        path: process.env.BLOG_LIST_PATH ?? '/blog1/',
    },
    {
        name: 'blog post page',
        path: process.env.BLOG_POST_PATH ?? '/blog1/first-blog-post',
    },
];

test.describe('blog page smoke tests', () => {
    test.skip(!baseUrl, 'Set BLOG_BASE_URL to a running TYPO3 blog instance to run Playwright smoke tests.');

    test.describe.configure({ mode: 'parallel' });

    for (const entry of pages) {
        test(`${entry.name} renders without frontend exceptions`, async ({ page }) => {
            const response = await page.goto(entry.path, { waitUntil: 'networkidle' });

            expect(response, `No HTTP response for ${entry.path}`).not.toBeNull();
            expect(response?.status(), `Unexpected status for ${entry.path}`).toBe(200);

            await expect(page.locator('html')).toBeVisible();
            await expect(page.locator('body')).not.toContainText('IncompleteRecordException');
            await expect(page.locator('body')).not.toContainText('RecordPropertyNotFoundException');
            await expect(page.locator('main')).toHaveCount(1);
            await expect(page.locator('.blogcontainer, .container, [class*="max-w-"]').first()).toBeVisible();
        });
    }

    test('blog post page exposes a sidebar region', async ({ page }) => {
        const response = await page.goto(process.env.BLOG_POST_PATH ?? '/blog1/first-blog-post', {
            waitUntil: 'networkidle',
        });

        expect(response, 'No HTTP response for the configured blog post path').not.toBeNull();
        expect(response?.status(), 'Unexpected status for the configured blog post path').toBe(200);

        await expect(page.locator('aside, [role="complementary"], .blogcontainer-sidebar').first()).toBeVisible();
    });
});
