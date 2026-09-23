const { test, expect } = require('@playwright/test');


/* =========================
   DONNÉES VALIDES
========================= */

// Utilisateur valide utilisé comme base pour les tests
const validUser = {
  name: 'Dupont',
  lastname: 'Jean',
  email: 'jean@test.fr',
  password: 'Test123!',
  passwordConfirm: 'Test123!',
  terms: true
};


/* =========================
   REMPLISSAGE DU FORMULAIRE
========================= */

// Fonction utilitaire pour remplir le formulaire d'inscription avec les données fournies
async function fillRegisterForm(page, data = {}) {

  // Fusion des données par défaut avec les données personnalisées
  const user = {
    ...validUser,
    ...data
  };

  // Remplissage des champs du formulaire
  await page
    .locator('#name-register')
    .fill(user.name);

  await page
    .locator('#lastname-register')
    .fill(user.lastname);

  await page
    .locator('#email-register')
    .fill(user.email);

  await page
    .locator('#password-register')
    .fill(user.password);

  await page
    .locator('#confirm-password-register')
    .fill(user.passwordConfirm);


  // Gestion de la case à cocher des conditions d'utilisation
  const terms =
    page.locator('#terms-register');


  if (user.terms) {

    await terms.check();

  } else {

    await terms.uncheck();

  }

}


/* =========================
   SIMULATION DU BACKEND
========================= */

// Fonction pour simuler la réponse du backend et intercepter l'appel API
async function mockRegisterResponse(
  page,
  body,
  status = 400
) {

  // Interceptation de la requête d'inscription
  await page.route(
    '**/authCont.php?task=register',

    async route => {

      // Renvoi d'une réponse mockée avec le statut et le corps spécifiés
      await route.fulfill({
        status,
        contentType: 'application/json',
        body: JSON.stringify(body)
      });

    }
  );

}


/* =========================
   AVANT CHAQUE TEST
========================= */

// Configuration exécutée avant chaque test
test.beforeEach(async ({ page }) => {

  // Navigation vers la page d'authentification et affichage du formulaire d'inscription
  await page.goto(
    'frontend/pages/auth.php'
  );

  await page
    .locator('#register-btn')
    .click();

});


/* =========================
   FORMULAIRE VISIBLE
========================= */

test(
  'formulaire inscription visible',

  async ({ page }) => {

    await expect(
      page.locator('#register-form')
    ).toBeVisible();

  }
);


/* =========================
   SWITCH LOGIN / REGISTER
========================= */

test(
  'switch connexion inscription',

  async ({ page }) => {

    await page
      .locator('#login-btn')
      .click();


    await expect(
      page.locator('#login-form')
    ).toBeVisible();


    await expect(
      page.locator('#register-form')
    ).toBeHidden();


    await page
      .locator('#register-btn')
      .click();


    await expect(
      page.locator('#register-form')
    ).toBeVisible();

  }
);


/* =========================
   NOM OBLIGATOIRE
========================= */

test(
  'nom obligatoire',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        name: ''
      }
    );


    const input =
      page.locator('#name-register');


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   PRÉNOM OBLIGATOIRE
========================= */

test(
  'prénom obligatoire',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        lastname: ''
      }
    );


    const input =
      page.locator('#lastname-register');


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   EMAIL OBLIGATOIRE
========================= */

test(
  'email obligatoire',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        email: ''
      }
    );


    const input =
      page.locator('#email-register');


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   EMAIL INVALIDE
========================= */

test(
  'email invalide',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        email: 'email-invalide'
      }
    );


    const input =
      page.locator('#email-register');


    const typeMismatch =
      await input.evaluate(
        element =>
          element.validity.typeMismatch
      );


    expect(typeMismatch)
      .toBe(true);

  }
);


/* =========================
   PASSWORD OBLIGATOIRE
========================= */

test(
  'mot de passe obligatoire',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        password: ''
      }
    );


    const input =
      page.locator('#password-register');


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   CONFIRMATION OBLIGATOIRE
========================= */

test(
  'confirmation obligatoire',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        passwordConfirm: ''
      }
    );


    const input =
      page.locator(
        '#confirm-password-register'
      );


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   CONDITIONS OBLIGATOIRES
========================= */

test(
  'conditions obligatoires',

  async ({ page }) => {

    await fillRegisterForm(
      page,
      {
        terms: false
      }
    );


    const input =
      page.locator('#terms-register');


    const valueMissing =
      await input.evaluate(
        element =>
          element.validity.valueMissing
      );


    expect(valueMissing)
      .toBe(true);

  }
);


/* =========================
   EMAIL DÉJÀ UTILISÉ
========================= */

test(
  'affichage erreur email déjà utilisé',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: false,

        errors: {
          email:
            'Cette adresse email est déjà utilisée.'
        }
      }
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    const response =
      page.locator('#register-response');


    await expect(response)
      .toHaveClass(/error/);


    await expect(response)
      .toContainText(
        'déjà utilisée'
      );

  }
);


/* =========================
   ERREUR MOT DE PASSE
========================= */

test(
  'affichage erreur mot de passe',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: false,

        errors: {
          password:
            'Le mot de passe doit contenir au moins 8 caractères.'
        }
      }
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    await expect(
      page.locator('#register-response')
    ).toContainText(
      'au moins 8 caractères'
    );

  }
);


/* =========================
   PASSWORDS DIFFÉRENTS
========================= */

test(
  'affichage passwords différents',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: false,

        errors: {
          password_confirm:
            'Les mots de passe ne correspondent pas.'
        }
      }
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    await expect(
      page.locator('#register-response')
    ).toContainText(
      'ne correspondent pas'
    );

  }
);


/* =========================
   PLUSIEURS ERREURS
========================= */

test(
  'affichage plusieurs erreurs',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: false,

        errors: {

          name:
            'Le nom est obligatoire.',

          lastname:
            'Le prénom est obligatoire.',

          email:
            'L\'email n\'est pas valide.'

        }
      }
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    const response =
      page.locator('#register-response');


    await expect(response)
      .toHaveClass(/error/);


    await expect(
      response.locator('li')
    ).toHaveCount(3);


    await expect(response)
      .toContainText(
        'Le nom est obligatoire.'
      );


    await expect(response)
      .toContainText(
        'Le prénom est obligatoire.'
      );


    await expect(response)
      .toContainText(
        'L\'email n\'est pas valide.'
      );

  }
);


/* =========================
   INSCRIPTION RÉUSSIE
========================= */

test(
  'inscription réussie',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: true,

        message:
          'Inscription réussie pour "jean@test.fr".'
      },
      201
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    const response =
      page.locator('#register-response');


    await expect(response)
      .toHaveClass(/success/);


    await expect(response)
      .toContainText(
        'Inscription réussie'
      );

  }
);


/* =========================
   RESET APRÈS SUCCÈS
========================= */

test(
  'formulaire vidé après succès',

  async ({ page }) => {

    await mockRegisterResponse(
      page,
      {
        success: true,
        message: 'Inscription réussie.'
      },
      201
    );


    await fillRegisterForm(page);


    await page
      .locator(
        '#register-form button[type="submit"]'
      )
      .click();


    await expect(
      page.locator('#name-register')
    ).toHaveValue('');


    await expect(
      page.locator('#lastname-register')
    ).toHaveValue('');


    await expect(
      page.locator('#email-register')
    ).toHaveValue('');


    await expect(
      page.locator('#password-register')
    ).toHaveValue('');


    await expect(
      page.locator(
        '#confirm-password-register'
      )
    ).toHaveValue('');


    await expect(
      page.locator('#terms-register')
    ).not.toBeChecked();

  }
);