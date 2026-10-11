const iconsForm = document.getElementById("icons-form");
const iconsResponse = document.getElementById("response-icon") || document.getElementById("icon-response");
const categoryFormAjax = document.getElementById("category-form");
const categoryResponseAjax = document.getElementById("response-category");
const updateCategoryFormAjax = document.getElementById("update-category-form");
const updateCategoryResponseAjax = document.getElementById("response-update-category");
let iconsByCategoryCache = {};

async function parseJsonResponse(response, fallbackMessage) {
  const contentType = response.headers.get("content-type") || "";

  if (contentType.includes("application/json")) {
    return response.json();
  }

  const text = await response.text();
  throw new Error(text || fallbackMessage);
}

function renderResponseMessage(responseElement, data, defaultErrorMessage) {
  responseElement.classList.remove("hidden", "success", "error");

  if (data.success === true) {
    responseElement.textContent = data.message ?? "Icône ajoutée avec succès.";
    responseElement.classList.add("success");
    return;
  }

  const errorMessages = [];

  if (typeof data.message === "string" && data.message.trim() !== "") {
    errorMessages.push(data.message);
  }

  if (typeof data.error === "string" && data.error.trim() !== "") {
    errorMessages.push(data.error);
  }

  if (data.errors && typeof data.errors === "object") {
    Object.values(data.errors).forEach(errorMessage => {
      if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
        errorMessages.push(errorMessage);
      }
    });
  }

  responseElement.textContent = errorMessages[0] ?? defaultErrorMessage;
  responseElement.classList.add("error");
}

function handleIconsFormSubmit(event) {
  event.preventDefault();

  if (!iconsForm) {
    return;
  }

  if (!iconsResponse) {
    return;
  }

  fetch("../../../backend/controllers/categoriesCont.php?task=add_icon", {
    method: "POST",
    body: new FormData(iconsForm)
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue pendant l'ajout de l'icône.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue pendant l'ajout de l'icône." });
      }

      return parseJsonResponse(response, "Une erreur est survenue pendant l'ajout de l'icône.");
    })
    .then(data => {
      renderResponseMessage(iconsResponse, data, "L'ajout de l'icône a échoué.");

      if (data.success === true) {
        iconsForm.reset();
      }
    })
    .catch(error => {
      console.error("Error:", error);

      iconsResponse.classList.remove("hidden", "success", "error");
      iconsResponse.textContent = error?.message || "Une erreur est survenue. Veuillez réessayer.";
      iconsResponse.classList.add("error");
    });
}

if (iconsForm) {
  iconsForm.addEventListener("submit", handleIconsFormSubmit);
}

function handleCategoryFormSubmit(event) {
  event.preventDefault();

  if (!categoryFormAjax || !categoryResponseAjax) {
    return;
  }

  fetch("../../../backend/controllers/categoriesCont.php?task=add_category", {
    method: "POST",
    body: new FormData(categoryFormAjax)
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue pendant l'ajout de la catégorie.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue pendant l'ajout de la catégorie." });
      }

      return parseJsonResponse(response, "Une erreur est survenue pendant l'ajout de la catégorie.");
    })
    .then(data => {
      renderResponseMessage(categoryResponseAjax, data, "L'ajout de la catégorie a échoué.");

      if (data.success === true) {
        categoryFormAjax.reset();
        loadCategoriesList();
      }
    })
    .catch(error => {
      console.error("Error:", error);

      categoryResponseAjax.classList.remove("hidden", "success", "error");
      categoryResponseAjax.textContent = error?.message || "Une erreur est survenue. Veuillez réessayer.";
      categoryResponseAjax.classList.add("error");
    });
}

if (categoryFormAjax) {
  categoryFormAjax.addEventListener("submit", handleCategoryFormSubmit);
}

function handleUpdateCategoryFormSubmit(event) {
  event.preventDefault();

  if (!updateCategoryFormAjax || !updateCategoryResponseAjax) {
    return;
  }

  const payload = new FormData(updateCategoryFormAjax);
  const selectedType = updateCategoryFormAjax.querySelector("input[name='update-category-type']:checked")?.value;
  if (selectedType) {
    payload.set("category-type", selectedType);
  }

  fetch("../../../backend/controllers/categoriesCont.php?task=update_category", {
    method: "POST",
    body: payload,
    credentials: "same-origin"
  })
    .then(async response => {
      if (!response.ok) {
        const data = await parseJsonResponse(response, "Une erreur est survenue pendant la modification de la catégorie.");
        return Promise.reject(data || { success: false, message: "Une erreur est survenue pendant la modification de la catégorie." });
      }

      return parseJsonResponse(response, "Une erreur est survenue pendant la modification de la catégorie.");
    })
    .then(data => {
      renderResponseMessage(updateCategoryResponseAjax, data, "La mise à jour de la catégorie a échoué.");

      if (data.success === true) {
        updateCategoryFormAjax.classList.add("is-hidden");
        loadCategoriesList();
      }
    })
    .catch(error => {
      console.error("Error:", error);
      updateCategoryResponseAjax.classList.remove("hidden", "success", "error");
      updateCategoryResponseAjax.textContent = error?.message || "Une erreur est survenue. Veuillez réessayer.";
      updateCategoryResponseAjax.classList.add("error");
    });
}

if (updateCategoryFormAjax) {
  updateCategoryFormAjax.addEventListener("submit", handleUpdateCategoryFormSubmit);
}

async function getIcons() {
  const response = await fetch("../../../backend/controllers/categoriesCont.php?task=get_icons", {
    method: "GET",
    credentials: "same-origin"
  });

  const data = await parseJsonResponse(
    response,
    "Une erreur est survenue pendant la récupération des icônes."
  );

  if (!response.ok || data.success !== true) {
    throw new Error(data.message || data.error || "Impossible de récupérer les icônes.");
  }

  return data.iconsByCategory ?? {};
}

async function getCategories() {
  const response = await fetch("../../../backend/controllers/categoriesCont.php?task=get_categories", {
    method: "GET",
    credentials: "same-origin"
  });

  const data = await parseJsonResponse(
    response,
    "Une erreur est survenue pendant la récupération des catégories."
  );

  if (!response.ok || data.success !== true) {
    throw new Error(data.message || data.error || "Impossible de récupérer les catégories.");
  }

  return {
    activeCategories: Array.isArray(data.activeCategories) ? data.activeCategories : [],
    inactiveCategories: Array.isArray(data.inactiveCategories) ? data.inactiveCategories : []
  };
}

window.getCategorie = getCategories;

async function requestToggleCategoryState(categoryId) {
  const payload = new FormData();
  payload.append("category-id", String(categoryId));

  const response = await fetch("../../../backend/controllers/categoriesCont.php?task=toggle_category_state", {
    method: "POST",
    body: payload,
    credentials: "same-origin"
  });

  const data = await parseJsonResponse(
    response,
    "Une erreur est survenue pendant la mise à jour de la catégorie."
  );

  if (!response.ok || data.success !== true) {
    throw new Error(data.message || data.error || "Impossible de mettre à jour la catégorie.");
  }

  return data;
}

async function requestDeleteCategory(categoryId) {
  const payload = new FormData();
  payload.append("category-id", String(categoryId));

  const response = await fetch("../../../backend/controllers/categoriesCont.php?task=delete_category", {
    method: "POST",
    body: payload,
    credentials: "same-origin"
  });

  const data = await parseJsonResponse(
    response,
    "Une erreur est survenue pendant la suppression de la catégorie."
  );

  if (!response.ok || data.success !== true) {
    throw new Error(data.message || data.error || "Impossible de supprimer la catégorie.");
  }

  return data;
}

async function loadCategoriesList() {
  try {
    const { activeCategories, inactiveCategories } = await getCategories();

    if (typeof window.renderCategoriesList === "function") {
      window.renderCategoriesList({
        activeCategories,
        inactiveCategories
      });
      return;
    }
  } catch (error) {
    console.error("Erreur chargement catégories:", error);
  }
}

async function loadIconsLibrary() {
  const iconLibraryRoot = document.getElementById("icon-library-root");
  if (!iconLibraryRoot) {
    return;
  }

  try {
    const iconsByCategory = await getIcons();
    iconsByCategoryCache = iconsByCategory;
    window.iconsByCategoryCache = iconsByCategoryCache;

    if (typeof window.renderIconsLibrary === "function") {
      window.renderIconsLibrary(iconsByCategory);
    }

    if (typeof window.renderUpdateIconsLibrary === "function") {
      window.renderUpdateIconsLibrary(iconsByCategory);
    }
  } catch (error) {
    console.error("Erreur chargement library:", error);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadIconsLibrary();
  loadCategoriesList();
});

window.addEventListener("icons-library-ready", () => {
  loadIconsLibrary();
});

window.addEventListener("categories-list-ready", () => {
  loadCategoriesList();
});

window.toggleCategoryState = async function toggleCategoryStateFromUI(categoryId) {
  try {
    await requestToggleCategoryState(categoryId);
    await loadCategoriesList();
  } catch (error) {
    console.error("Erreur changement état catégorie:", error);
  }
};

window.deleteCategory = async function deleteCategoryFromUI(categoryId) {
  try {
    await requestDeleteCategory(categoryId);
    await loadCategoriesList();
    return { success: true };
  } catch (error) {
    console.error("Erreur suppression catégorie:", error);
    return {
      success: false,
      message: error?.message || "Impossible de supprimer la catégorie."
    };
  }
};