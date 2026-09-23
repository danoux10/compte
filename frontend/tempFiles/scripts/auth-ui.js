document.addEventListener('DOMContentLoaded', () => {

  /* =========================
     ELEMENTS
  ========================= */

  const loginBtn =
    document.getElementById('login-btn');

  const registerBtn =
    document.getElementById('register-btn');

  const loginForm =
    document.getElementById('login-form');

  const registerForm =
    document.getElementById('register-form');


  /* =========================
     FORMULAIRE PAR DÉFAUT
  ========================= */

  /*
    Choix possible :

    'login'
    'register'
  */

  const defaultForm = 'login';


  /* =========================
     AFFICHAGE FORMULAIRE
  ========================= */

  function showForm(form) {

    /* LOGIN */

    if (form === 'login') {

      loginForm.style.display = 'flex';
      registerForm.style.display = 'none';

      loginBtn.classList.add('active');
      registerBtn.classList.remove('active');

      return;
    }


    /* REGISTER */

    if (form === 'register') {

      loginForm.style.display = 'none';
      registerForm.style.display = 'flex';

      registerBtn.classList.add('active');
      loginBtn.classList.remove('active');

    }

  }


  /* =========================
     FORMULAIRE AU DÉMARRAGE
  ========================= */

  showForm(defaultForm);


  /* =========================
     EVENTS
  ========================= */

  loginBtn.addEventListener('click', () => {
    showForm('login');
  });


  registerBtn.addEventListener('click', () => {
    showForm('register');
  });

});