const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  // Répertoire contenant les tests Playwright
  testDir: './frontend/tests/playwright',

  use: {
    // URL de base de l'application à tester
    baseURL: 'http://localhost/compte/',
    // Navigateur utilisé pour les tests
    browserName: 'chromium',
    // Mode sans interface graphique pour CI/CD
    headless: true,
    // Capturer des screenshots uniquement en cas d'échec
    screenshot: 'only-on-failure'
  },

  // Format du rapport de test
  reporter: 'list'
});