const registerForm = document.getElementById("register-form");
const registerResponse = document.getElementById("register-response");

function handleRegisterFormSubmit(event) {
  event.preventDefault();

  fetch("../../../backend/controllers/authCont.php?task=register", {
    method: "POST",
    body: new FormData(registerForm)
  })
    .then(response => response.json())
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
    });
}

registerForm.addEventListener("submit", handleRegisterFormSubmit);