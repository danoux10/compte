const registerForm = document.getElementById("register-form");
const registerResponse = document.getElementById("register-response");

const loginForm = document.getElementById("login-form");
const loginResponse = document.getElementById("login-response");

const logoutForms = document.getElementById("logout-forms");

async function parseJsonResponse(response, fallbackMessage) {
  const contentType = response.headers.get("content-type") || "";

  if (contentType.includes("application/json")) {
    return response.json();
  }

  const text = await response.text();
  throw new Error(text || fallbackMessage);
}

function handleRegisterFormSubmit(event) {
  event.preventDefault();

  fetch("../../../backend/controllers/authCont.php?task=register", {
    method: "POST",
    body: new FormData(registerForm)
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue lors de l'inscription.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue lors de l'inscription." });
      }

      return parseJsonResponse(response, "Une erreur est survenue lors de l'inscription.");
    })
    .then(data => {
      console.log("Réponse PHP :", data);
      const success = data.success;
      console.log("Success:", success);

      registerResponse.classList.remove("hidden", "success", "error");

      if (success === true) {
        registerForm.reset();
        registerResponse.textContent = data.message;
        registerResponse.classList.add("success");
      } else {
        const errorMessages = [];

        if (typeof data.message === "string" && data.message.trim() !== "") {
          errorMessages.push(data.message);
        }

        if (data.errors && typeof data.errors === "object") {
          Object.values(data.errors).forEach(errorMessage => {
            if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
              errorMessages.push(errorMessage);
            }
          });
        }

        if (errorMessages.length <= 1) {
          registerResponse.textContent = errorMessages[0] ?? "L'inscription a échoué.";
        } else {
          registerResponse.textContent = "";
          const errorList = document.createElement("ul");

          errorMessages.forEach(errorMessage => {
            const errorItem = document.createElement("li");
            errorItem.textContent = errorMessage;
            errorList.appendChild(errorItem);
          });

          registerResponse.appendChild(errorList);
        }

        registerResponse.classList.add("error");
      }
    })
    .catch(error => {
      console.error("Error:", error);
      if (registerResponse) {
        registerResponse.classList.remove("hidden", "success", "error");
        registerResponse.textContent = error?.message || "Une erreur est survenue. Veuillez réessayer.";
        registerResponse.classList.add("error");
      }
    });
}

function handleLoginFormSubmit(event) {
  event.preventDefault();

  fetch("../../../backend/controllers/authCont.php?task=login", {
    method: "POST",
    body: new FormData(loginForm)
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue lors de la connexion.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue lors de la connexion." });
      }

      return parseJsonResponse(response, "Une erreur est survenue lors de la connexion.");
    })
    .then(data => {
      console.log("Réponse PHP :", data);
      const success = data.success;
      console.log("Success:", success);

      loginResponse.classList.remove("hidden", "success", "error");

      if (success === true) {
        loginForm.reset();
        loginResponse.textContent = data.message ?? "Connexion réussie.";
        loginResponse.classList.add("success");
        window.location.href = data.redirect ?? "testLogout.php";
        return;
      }

      const errorMessages = [];

      if (typeof data.message === "string" && data.message.trim() !== "") {
        errorMessages.push(data.message);
      }

      if (data.errors && typeof data.errors === "object") {
        Object.values(data.errors).forEach(errorMessage => {
          if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
            errorMessages.push(errorMessage);
          }
        });
      }

      if (errorMessages.length <= 1) {
        loginResponse.textContent = errorMessages[0] ?? "La connexion a échoué.";
      } else {
        loginResponse.textContent = "";
        const errorList = document.createElement("ul");

        errorMessages.forEach(errorMessage => {
          const errorItem = document.createElement("li");
          errorItem.textContent = errorMessage;
          errorList.appendChild(errorItem);
        });

        loginResponse.appendChild(errorList);
      }

      loginResponse.classList.add("error");
    })
    .catch(error => {
      console.error("Error:", error);
      loginResponse.classList.remove("hidden", "success", "error");
      loginResponse.textContent = error?.message || "Une erreur est survenue. Veuillez réessayer.";
      loginResponse.classList.add("error");
    });
}

function handleLogout(event) {
  event.preventDefault();

  fetch("../../../backend/controllers/authCont.php?task=logout", {
    method: "POST"
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue lors de la déconnexion.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue lors de la déconnexion." });
      }

      return parseJsonResponse(response, "Une erreur est survenue lors de la déconnexion.");
    })
    .then(data => {
      console.log("Réponse PHP :", data);
      const success = data.success;
      console.log("Success:", success);

      if (success === true) {
        window.location.href = data.redirect ?? "auth.php";
      }
    })
    .catch(error => {
      console.error("Error:", error);
    });
}

if (registerForm) {
  registerForm.addEventListener("submit", handleRegisterFormSubmit);
}

if (loginForm) {
  loginForm.addEventListener("submit", handleLoginFormSubmit);
}

if (logoutForms) {
  logoutForms.addEventListener("submit", handleLogout);
}