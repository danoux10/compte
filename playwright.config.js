const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './frontend/tests/playwright',

  use: {
    baseURL: 'http://localhost/compte/',
    browserName: 'chromium',
    headless: true,
    screenshot: 'only-on-failure'
  },

  reporter: 'list'
});