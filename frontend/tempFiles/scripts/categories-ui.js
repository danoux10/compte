const iconFileInput = document.getElementById("icon-file");
const previewIcon = document.getElementById("preview-icon");

const FORBIDDEN_SVG_TAGS = new Set([
  "script",
  "foreignobject",
  "iframe",
  "object",
  "embed",
  "image",
  "audio",
  "video"
]);

function getEffectiveBackgroundColor(element) {
  let current = element;

  while (current) {
    const bgColor = window.getComputedStyle(current).backgroundColor;
    const rgb = parseRgbColor(bgColor);

    if (rgb && rgb.a > 0) {
      return rgb;
    }

    current = current.parentElement;
  }

  return { r: 255, g: 255, b: 255, a: 1 };
}

function parseRgbColor(color) {
  const match = color.match(/rgba?\(([^)]+)\)/i);
  if (!match) {
    return null;
  }

  const [r, g, b, a = "1"] = match[1].split(",").map(value => value.trim());

  return {
    r: Number.parseFloat(r),
    g: Number.parseFloat(g),
    b: Number.parseFloat(b),
    a: Number.parseFloat(a)
  };
}

function isDarkBackground(rgb) {
  const luminance = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b) / 255;
  return luminance < 0.5;
}

function sanitizeAndNormalizeSvg(svgText) {
  const parser = new DOMParser();
  const doc = parser.parseFromString(svgText, "image/svg+xml");

  if (doc.querySelector("parsererror")) {
    throw new Error("Le fichier SVG est invalide.");
  }

  const root = doc.documentElement;
  if (!root || root.tagName.toLowerCase() !== "svg") {
    throw new Error("Le fichier sélectionné n'est pas un SVG.");
  }

  FORBIDDEN_SVG_TAGS.forEach(tag => {
    doc.querySelectorAll(tag).forEach(node => node.remove());
  });

  doc.querySelectorAll("*").forEach(node => {
    Array.from(node.attributes).forEach(attribute => {
      const name = attribute.name.toLowerCase();
      const value = attribute.value.trim().toLowerCase();

      if (name.startsWith("on")) {
        node.removeAttribute(attribute.name);
        return;
      }

      if (value.startsWith("javascript:")) {
        node.removeAttribute(attribute.name);
        return;
      }

      if ((name === "href" || name === "xlink:href") && !value.startsWith("#")) {
        node.removeAttribute(attribute.name);
      }
    });

    node.removeAttribute("color");

    const normalizeColorAttribute = attrName => {
      if (!node.hasAttribute(attrName)) {
        return;
      }

      const value = node.getAttribute(attrName).trim().toLowerCase();
      if (value === "none" || value === "transparent" || value.startsWith("url(")) {
        return;
      }

      node.setAttribute(attrName, "currentColor");
    };

    normalizeColorAttribute("fill");
    normalizeColorAttribute("stroke");

    if (node.hasAttribute("style")) {
      const style = node.getAttribute("style");
      const normalizedStyle = style
        .replace(/color\s*:\s*[^;]+;?/gi, "")
        .replace(/(fill|stroke)\s*:\s*([^;]+)/gi, (full, property, value) => {
          const normalizedValue = value.trim().toLowerCase();
          if (normalizedValue === "none" || normalizedValue === "transparent" || normalizedValue.startsWith("url(")) {
            return `${property}:${normalizedValue}`;
          }
          return `${property}:currentColor`;
        });

      node.setAttribute("style", normalizedStyle);
    }
  });

  if (!root.hasAttribute("viewBox")) {
    const width = root.getAttribute("width");
    const height = root.getAttribute("height");
    if (width && height && !Number.isNaN(Number(width)) && !Number.isNaN(Number(height))) {
      root.setAttribute("viewBox", `0 0 ${Number(width)} ${Number(height)}`);
    }
  }

  root.setAttribute("width", "72");
  root.setAttribute("height", "72");

  if (!root.hasAttribute("fill")) {
    root.setAttribute("fill", "currentColor");
  }

  return new XMLSerializer().serializeToString(root);
}

function clearPreview() {
  if (!previewIcon) {
    return;
  }

  previewIcon.innerHTML = "";
  previewIcon.style.padding = "";
  previewIcon.style.border = "";
  previewIcon.style.borderRadius = "";
  previewIcon.style.display = "";
  previewIcon.style.alignItems = "";
  previewIcon.style.justifyContent = "";
  previewIcon.style.width = "";
  previewIcon.style.height = "";
  previewIcon.style.backgroundColor = "";
}

function renderPreview(svgMarkup) {
  if (!previewIcon) {
    return;
  }

  const background = getEffectiveBackgroundColor(previewIcon);
  const darkBackground = isDarkBackground(background);
  const iconColor = darkBackground ? "#f5f5f5" : "#111111";
  const frameBackground = darkBackground ? "rgba(255, 255, 255, 0.06)" : "rgba(0, 0, 0, 0.06)";
  const frameBorder = darkBackground ? "1px solid rgba(255, 255, 255, 0.22)" : "1px solid rgba(0, 0, 0, 0.18)";

  previewIcon.style.display = "inline-flex";
  previewIcon.style.alignItems = "center";
  previewIcon.style.justifyContent = "center";
  previewIcon.style.width = "96px";
  previewIcon.style.height = "96px";
  previewIcon.style.padding = "10px";
  previewIcon.style.borderRadius = "10px";
  previewIcon.style.border = frameBorder;
  previewIcon.style.backgroundColor = frameBackground;
  previewIcon.style.color = iconColor;
  previewIcon.innerHTML = svgMarkup;
}

async function handleIconPreviewChange() {
  if (!iconFileInput || !previewIcon) {
    return;
  }

  const selectedFile = iconFileInput.files?.[0];
  if (!selectedFile) {
    clearPreview();
    return;
  }

  const fileContent = await selectedFile.text();
  const svgMarkup = sanitizeAndNormalizeSvg(fileContent);
  renderPreview(svgMarkup);
}

if (iconFileInput) {
  iconFileInput.addEventListener("change", () => {
    handleIconPreviewChange().catch(() => {
      clearPreview();
      previewIcon.textContent = "Aperçu indisponible pour ce fichier.";
    });
  });
}

if (iconFileInput?.form) {
  iconFileInput.form.addEventListener("reset", () => {
    clearPreview();
  });
}

const categoryForm = document.getElementById("category-form");
const toggleCategoryFormButton = document.getElementById("toggle-category-form");
const updateCategoryStateButton = document.getElementById("update-category-state-toggle");
const updateCategoryStateInput = document.getElementById("update-category-state");
const updateCategoryForm = document.getElementById("update-category-form");
const updateCategoryIdInput = document.getElementById("update-category-id");
const updateCategoryIconInput = document.getElementById("update-category-icon");
const updateCategoryNameInput = document.getElementById("update-category-name");
const updateCategoryColorInput = document.getElementById("update-category-color");
const updateCategoryExpenseInput = document.getElementById("update-expense");
const updateCategoryIncomeInput = document.getElementById("update-income");
const updateCategoryPreviewContainer = document.getElementById("update-category-preview");
const closeUpdateCategoryButton = document.getElementById("close-update-category");
const categoryNameInput = document.getElementById("categoryName");
const categoryColorInput = document.getElementById("categoryColor");
const randomColorButton = document.getElementById("random-color");
const categoryPreview = document.getElementById("preview-category");
const openAddIconButton = document.getElementById("open-addIcon");
const closeAddIconButton = document.getElementById("close-addIcon");

function syncToggleCategoryFormButtonLabel() {
  if (!toggleCategoryFormButton || !categoryForm) {
    return;
  }

  const isHidden = categoryForm.classList.contains("is-hidden");
  toggleCategoryFormButton.textContent = isHidden
    ? "Afficher ajouter catégorie"
    : "Masquer ajouter catégorie";
  toggleCategoryFormButton.setAttribute("aria-expanded", String(!isHidden));
}

function toggleCategoryFormVisibility() {
  if (!categoryForm) {
    return;
  }

  categoryForm.classList.toggle("is-hidden");
  syncToggleCategoryFormButtonLabel();
}

function showAddIconForm() {
  if (!iconsForm) {
    return;
  }

  iconsForm.classList.remove("is-hidden");
  iconsForm.scrollIntoView({ behavior: "smooth", block: "start" });
}

function hideAddIconForm() {
  if (!iconsForm) {
    return;
  }

  iconsForm.classList.add("is-hidden");
}

function randomHexColor() {
  const value = Math.floor(Math.random() * 16777215);
  return `#${value.toString(16).padStart(6, "0")}`;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function hexToRgb(hexColor) {
  const normalized = hexColor.replace("#", "");

  if (!/^[0-9a-f]{6}$/i.test(normalized)) {
    return { r: 122, g: 92, b: 255 };
  }

  return {
    r: Number.parseInt(normalized.slice(0, 2), 16),
    g: Number.parseInt(normalized.slice(2, 4), 16),
    b: Number.parseInt(normalized.slice(4, 6), 16)
  };
}

function getReadableTextColor(hexColor) {
  const { r, g, b } = hexToRgb(hexColor);
  const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
  return luminance > 0.62 ? "#111827" : "#f8fafc";
}

function ensureCategoryPreviewStructure() {
  if (!categoryPreview) {
    return null;
  }

  if (!categoryPreview.querySelector(".category-preview-card")) {
    categoryPreview.innerHTML = `
      <div class="category-preview-card">
        <span class="category-preview-icon" aria-hidden="true">+</span>
        <div class="category-preview-content">
          <p class="category-preview-name">Nom de catégorie</p>
          <p class="category-preview-type">Type non défini</p>
        </div>
      </div>
    `;
  }

  return {
    card: categoryPreview.querySelector(".category-preview-card"),
    icon: categoryPreview.querySelector(".category-preview-icon"),
    name: categoryPreview.querySelector(".category-preview-name"),
    type: categoryPreview.querySelector(".category-preview-type")
  };
}

function updateCategoryPreview() {
  const previewNodes = ensureCategoryPreviewStructure();
  if (!previewNodes) {
    return;
  }

  const fallbackColor = "#7a5cff";
  const currentColor = categoryColorInput?.value || fallbackColor;
  const textColor = getReadableTextColor(currentColor);
  const selectedType = categoryForm?.querySelector("input[name='category-type']:checked");
  const selectedIcon = categoryForm?.querySelector("input[name='category-icon']:checked");

  const typedName = categoryNameInput?.value?.trim();
  previewNodes.name.textContent = typedName || "Nom de catégorie";

  if (selectedType?.value === "expense") {
    previewNodes.type.textContent = "Dépense";
  } else if (selectedType?.value === "income") {
    previewNodes.type.textContent = "Revenu";
  } else {
    previewNodes.type.textContent = "Type non défini";
  }

  previewNodes.card.style.background = "";
  previewNodes.card.style.color = "";
  previewNodes.card.style.borderColor = "";
  previewNodes.icon.style.background = currentColor;
  previewNodes.icon.style.color = textColor;
  previewNodes.icon.style.borderColor = textColor === "#111827"
    ? "rgba(17, 24, 39, 0.28)"
    : "rgba(248, 250, 252, 0.35)";

  if (selectedIcon) {
    const selectedIconImage = selectedIcon.closest("label")?.querySelector("img");
    if (selectedIconImage?.src) {
      previewNodes.icon.innerHTML = `<img src="${selectedIconImage.src}" alt="" />`;
      const previewImage = previewNodes.icon.querySelector("img");
      if (previewImage) {
        previewImage.style.filter = textColor === "#111827"
          ? "brightness(0) invert(0)"
          : "brightness(0) invert(1)";
      }
      return;
    }
  }

  previewNodes.icon.textContent = "+";
}

if (categoryForm) {
  categoryForm.addEventListener("change", event => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (
      target.matches("input[name='category-type']") ||
      target.matches("input[name='category-icon']") ||
      target.id === "categoryColor"
    ) {
      updateCategoryPreview();
    }
  });

  categoryForm.addEventListener("reset", () => {
    window.setTimeout(() => {
      if (categoryColorInput) {
        categoryColorInput.value = "#7a5cff";
      }
      updateCategoryPreview();
    }, 0);
  });
}

if (categoryNameInput) {
  categoryNameInput.addEventListener("input", updateCategoryPreview);
}

if (categoryColorInput) {
  if (!categoryColorInput.value) {
    categoryColorInput.value = "#7a5cff";
  }

  categoryColorInput.addEventListener("input", updateCategoryPreview);
}

if (randomColorButton) {
  randomColorButton.addEventListener("click", () => {
    if (!categoryColorInput) {
      return;
    }

    categoryColorInput.value = randomHexColor();
    updateCategoryPreview();
  });
}

if (openAddIconButton) {
  openAddIconButton.addEventListener("click", showAddIconForm);
}

if (closeAddIconButton) {
  closeAddIconButton.addEventListener("click", hideAddIconForm);
}

if (toggleCategoryFormButton) {
  toggleCategoryFormButton.addEventListener("click", toggleCategoryFormVisibility);
}

if (updateCategoryStateButton && updateCategoryStateInput) {
  updateCategoryStateButton.addEventListener("click", () => {
    const currentState = updateCategoryStateInput.value === "inactive" ? "inactive" : "active";
    const nextState = currentState === "active" ? "inactive" : "active";

    syncUpdateCategoryState(nextState);
  });
}

function syncUpdateCategoryState(state) {
  if (!updateCategoryStateButton || !updateCategoryStateInput) {
    return;
  }

  const normalizedState = state === "inactive" ? "inactive" : "active";
  updateCategoryStateInput.value = normalizedState;
  updateCategoryStateButton.dataset.state = normalizedState;
  updateCategoryStateButton.classList.toggle("is-active", normalizedState === "active");
  updateCategoryStateButton.classList.toggle("is-inactive", normalizedState === "inactive");
  updateCategoryStateButton.textContent = normalizedState === "active" ? "Actif" : "Inactif";
  updateCategoryStateButton.setAttribute("aria-pressed", String(normalizedState === "active"));
}

function renderUpdateCategoryPreviewFromValues() {
  if (!updateCategoryPreviewContainer) {
    return;
  }

  const safeName = escapeHtml(updateCategoryNameInput?.value || "Nom de catégorie");
  const typeLabel = updateCategoryIncomeInput?.checked ? "Revenu" : "Dépense";
  const colorValue = updateCategoryColorInput?.value || "#7a5cff";
  const selectedUpdateIcon = document.querySelector("input[name='update-category-icon-radio']:checked");
  const selectedIconImage = selectedUpdateIcon?.closest("label")?.querySelector("img");
  const iconMarkup = selectedIconImage?.src
    ? `<img src="${escapeHtml(selectedIconImage.src)}" alt="" />`
    : "+";

  updateCategoryPreviewContainer.innerHTML = `
    <div class="category-preview-card">
      <span class="category-preview-icon" style="background:${colorValue};">
        ${iconMarkup}
      </span>
      <div class="category-preview-content">
        <p class="category-preview-name">${safeName}</p>
        <p class="category-preview-type">${typeLabel}</p>
      </div>
    </div>
  `;
}

function renderUpdateCategoryPreview(category) {
  if (!updateCategoryPreviewContainer) {
    return;
  }

  const safeName = escapeHtml(category?.name || "Nom de catégorie");
  const typeLabel = category?.type === "income" ? "Revenu" : "Dépense";
  const iconPath = category?.iconPath ? `../../${escapeHtml(category.iconPath)}` : "";

  updateCategoryPreviewContainer.innerHTML = `
    <div class="category-preview-card">
      <span class="category-preview-icon" style="background:${category?.color || "#7a5cff"};">
        ${iconPath ? `<img src="${iconPath}" alt="" />` : "+"}
      </span>
      <div class="category-preview-content">
        <p class="category-preview-name">${safeName}</p>
        <p class="category-preview-type">${typeLabel}</p>
      </div>
    </div>
  `;
}

window.openUpdateCategoryForm = function openUpdateCategoryForm(category) {
  if (!updateCategoryForm || !category) {
    return;
  }

  if (updateCategoryIdInput) {
    updateCategoryIdInput.value = String(category.idCategory || "");
  }

  if (updateCategoryIconInput) {
    updateCategoryIconInput.value = String(category.icon || "");
  }

  if (updateCategoryNameInput) {
    updateCategoryNameInput.value = category.name || "";
  }

  if (updateCategoryColorInput) {
    updateCategoryColorInput.value = category.color || "#7a5cff";
  }

  if (updateCategoryExpenseInput && updateCategoryIncomeInput) {
    const isIncome = category.type === "income";
    updateCategoryIncomeInput.checked = isIncome;
    updateCategoryExpenseInput.checked = !isIncome;
  }

  syncUpdateCategoryState(category.state);
  renderUpdateCategoryPreview(category);

  if (typeof window.setUpdateCategorySelectedIcon === "function") {
    window.setUpdateCategorySelectedIcon(category.icon || "");
  } else if (typeof window.renderUpdateIconsLibrary === "function") {
    window.renderUpdateIconsLibrary(window.iconsByCategoryCache || {}, category.icon || "");
  }
  window.setTimeout(renderUpdateCategoryPreviewFromValues, 0);

  updateCategoryForm.classList.remove("is-hidden");
  updateCategoryForm.scrollIntoView({ behavior: "smooth", block: "start" });
};

if (closeUpdateCategoryButton && updateCategoryForm) {
  closeUpdateCategoryButton.addEventListener("click", () => {
    updateCategoryForm.classList.add("is-hidden");
  });
}

if (updateCategoryForm) {
  updateCategoryForm.addEventListener("change", event => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (
      target.matches("input[name='update-category-type']") ||
      target.matches("input[name='update-category-icon-radio']") ||
      target.id === "update-category-color"
    ) {
      renderUpdateCategoryPreviewFromValues();
    }
  });
}

if (updateCategoryNameInput) {
  updateCategoryNameInput.addEventListener("input", renderUpdateCategoryPreviewFromValues);
}

window.addEventListener("update-category-icon-selected", () => {
  renderUpdateCategoryPreviewFromValues();
});

window.addEventListener("icons-library-ready", () => {
  updateCategoryPreview();
});

syncToggleCategoryFormButtonLabel();
updateCategoryPreview();