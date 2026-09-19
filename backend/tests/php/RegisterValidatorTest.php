<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/validator/register.php';

final class RegisterValidatorTest extends TestCase
{
  /**
   * Données valides utilisées comme base pour les tests.
   */
  private function validData(): array
  {
    return [
      'name' => 'Dupont',
      'lastname' => 'Jean',
      'email' => 'jean@test.fr',
      'password' => 'Test123!',
      'password_confirm' => 'Test123!',
      'terms' => true
    ];
  }


  /* =========================
     INSCRIPTION VALIDE
  ========================= */

  #[Test]
  public function registerValid(): void
  {
    $errors = validateRegister(
      $this->validData()
    );

    $this->assertEmpty($errors);
  }


  /* =========================
     NOM
  ========================= */

  #[Test]
  public function nameRequired(): void
  {
    $data = $this->validData();

    $data['name'] = '';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le nom est obligatoire.',
      $errors['name']
    );
  }


  #[Test]
  public function nameWithOnlySpacesIsInvalid(): void
  {
    $data = $this->validData();

    $data['name'] = '   ';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le nom est obligatoire.',
      $errors['name']
    );
  }


  /* =========================
     PRÉNOM
  ========================= */

  #[Test]
  public function lastnameRequired(): void
  {
    $data = $this->validData();

    $data['lastname'] = '';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le prénom est obligatoire.',
      $errors['lastname']
    );
  }


  #[Test]
  public function lastnameWithOnlySpacesIsInvalid(): void
  {
    $data = $this->validData();

    $data['lastname'] = '   ';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le prénom est obligatoire.',
      $errors['lastname']
    );
  }


  /* =========================
     EMAIL
  ========================= */

  #[Test]
  public function emailRequired(): void
  {
    $data = $this->validData();

    $data['email'] = '';

    $errors = validateRegister($data);

    $this->assertSame(
      'L\'email est obligatoire.',
      $errors['email']
    );
  }


  #[Test]
  public function emailInvalid(): void
  {
    $data = $this->validData();

    $data['email'] = 'email-invalide';

    $errors = validateRegister($data);

    $this->assertSame(
      'L\'email n\'est pas valide.',
      $errors['email']
    );
  }


  #[Test]
  public function emailValid(): void
  {
    $data = $this->validData();

    $data['email'] = 'test@example.com';

    $errors = validateRegister($data);

    $this->assertArrayNotHasKey(
      'email',
      $errors
    );
  }


  /* =========================
     MOT DE PASSE
  ========================= */

  #[Test]
  public function passwordRequired(): void
  {
    $data = $this->validData();

    $data['password'] = '';
    $data['password_confirm'] = '';

    $errors = validateRegister($data);

    $this->assertSame(
      'Veuillez renseigner un mot de passe.',
      $errors['password']
    );
  }


  #[Test]
  public function passwordTooShort(): void
  {
    $data = $this->validData();

    $data['password'] = 'Test1!';
    $data['password_confirm'] = 'Test1!';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le mot de passe doit contenir au moins 8 caractères.',
      $errors['password']
    );
  }


  #[Test]
  public function passwordWithoutLowercase(): void
  {
    $data = $this->validData();

    $data['password'] = 'TEST123!';
    $data['password_confirm'] = 'TEST123!';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le mot de passe doit contenir au moins une minuscule.',
      $errors['password']
    );
  }


  #[Test]
  public function passwordWithoutUppercase(): void
  {
    $data = $this->validData();

    $data['password'] = 'test123!';
    $data['password_confirm'] = 'test123!';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le mot de passe doit contenir au moins une majuscule.',
      $errors['password']
    );
  }


  #[Test]
  public function passwordWithoutNumber(): void
  {
    $data = $this->validData();

    $data['password'] = 'TestTest!';
    $data['password_confirm'] = 'TestTest!';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le mot de passe doit contenir au moins un chiffre.',
      $errors['password']
    );
  }


  #[Test]
  public function passwordWithoutSpecialCharacter(): void
  {
    $data = $this->validData();

    $data['password'] = 'Test1234';
    $data['password_confirm'] = 'Test1234';

    $errors = validateRegister($data);

    $this->assertSame(
      'Le mot de passe doit contenir au moins un caractère spécial.',
      $errors['password']
    );
  }


  /* =========================
     CONFIRMATION PASSWORD
  ========================= */

  #[Test]
  public function passwordConfirmationRequired(): void
  {
    $data = $this->validData();

    $data['password_confirm'] = '';

    $errors = validateRegister($data);

    $this->assertSame(
      'Veuillez confirmer le mot de passe.',
      $errors['password_confirm']
    );
  }


  #[Test]
  public function passwordsDoNotMatch(): void
  {
    $data = $this->validData();

    $data['password'] = 'Test123!';
    $data['password_confirm'] = 'Test456!';

    $errors = validateRegister($data);

    $this->assertSame(
      'Les mots de passe ne correspondent pas.',
      $errors['password_confirm']
    );
  }


  /* =========================
     CONDITIONS
  ========================= */

  #[Test]
  public function termsRequired(): void
  {
    $data = $this->validData();

    $data['terms'] = false;

    $errors = validateRegister($data);

    $this->assertSame(
      'Vous devez accepter les conditions d\'utilisation.',
      $errors['terms']
    );
  }


  /* =========================
     PLUSIEURS ERREURS
  ========================= */

  #[Test]
  public function multipleErrors(): void
  {
    $errors = validateRegister([
      'name' => '',
      'lastname' => '',
      'email' => '',
      'password' => '',
      'password_confirm' => '',
      'terms' => false
    ]);

    $this->assertArrayHasKey(
      'name',
      $errors
    );

    $this->assertArrayHasKey(
      'lastname',
      $errors
    );

    $this->assertArrayHasKey(
      'email',
      $errors
    );

    $this->assertArrayHasKey(
      'password',
      $errors
    );

    $this->assertArrayHasKey(
      'password_confirm',
      $errors
    );

    $this->assertArrayHasKey(
      'terms',
      $errors
    );

    $this->assertCount(
      6,
      $errors
    );
  }
}