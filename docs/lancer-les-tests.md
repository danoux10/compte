# Lancer les tests — Backend & Frontend

Petit mémo pour lancer rapidement les tests du projet **Compte**.

## PHPUnit — Backend PHP

Depuis la racine du projet :

```powershell
cd C:\laragon\www\compte
```

Tous les tests PHPUnit :

```powershell
.\vendor\bin\phpunit
```

Uniquement le test d'inscription :

```powershell
.\vendor\bin\phpunit backend\tests\php\RegisterValidatorTest.php
```

Lister les tests détectés :

```powershell
.\vendor\bin\phpunit --list-tests backend\tests\php\RegisterValidatorTest.php
```

## Playwright — Frontend

Tous les tests frontend :

```powershell
npx playwright test
```

Lister les tests détectés :

```powershell
npx playwright test --list
```

Voir le navigateur pendant les tests :

```powershell
npx playwright test --headed
```

Ouvrir l'interface Playwright :

```powershell
npx playwright test --ui
```

Uniquement le test d'inscription :

```powershell
npx playwright test frontend\tests\playwright\RegisterTest.spec.js
```

## Résumé

```text
PHPUnit    → teste le backend PHP
Playwright → teste le frontend dans le navigateur
```

Commandes principales :

```powershell
.\vendor\bin\phpunit
npx playwright test
```
