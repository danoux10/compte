function IconLibrary({ iconsByCategory, selectedIconId, radioName, onSelectIcon }) {
  const categories = Object.entries(iconsByCategory || {});

  if (categories.length === 0) {
    return <p>Aucune icône disponible.</p>;
  }

  return (
    <div className="icon-library-skeleton">
      {categories.map(([categoryId, icons]) => {
        if (!Array.isArray(icons) || icons.length === 0) {
          return null;
        }

        const categoryName = icons[0]?.iconCatName || `Catégorie ${categoryId}`;

        return (
          <section key={categoryId} className="icon-library-category">
            <h3>{categoryName}</h3>

            <div className="icon-library-grid">
              {icons.map(icon => {
                const iconId = String(icon.idIcon);
                const isChecked = selectedIconId === iconId;

                return (
                  <label key={`${radioName}-${iconId}`} className="icon-item">
                    <input
                      type="radio"
                      name={radioName}
                      value={iconId}
                      required={radioName === "category-icon"}
                      checked={isChecked}
                      onChange={() => onSelectIcon(iconId, icon)}
                    />
                    <span className={`icon-box ${isChecked ? "is-checked" : ""}`}>
                      <img
                        src={`../../${icon.path}`}
                        alt={icon.displayName || icon.name || "Icône"}
                        width="28"
                        height="28"
                      />
                    </span>
                  </label>
                );
              })}
            </div>
          </section>
        );
      })}
    </div>
  );
}

let addRoot = null;
let updateRoot = null;
let pendingIconsByCategory = {};
let pendingAddSelectedIconId = null;
let pendingUpdateSelectedIconId = null;

function renderAddIconLibrary() {
  if (!addRoot) {
    return;
  }

  addRoot.render(
    <IconLibrary
      iconsByCategory={pendingIconsByCategory}
      selectedIconId={pendingAddSelectedIconId}
      radioName="category-icon"
      onSelectIcon={(iconId) => {
        pendingAddSelectedIconId = iconId;
        renderAddIconLibrary();
      }}
    />
  );
}

function renderUpdateIconLibrary() {
  if (!updateRoot) {
    return;
  }

  updateRoot.render(
    <IconLibrary
      iconsByCategory={pendingIconsByCategory}
      selectedIconId={pendingUpdateSelectedIconId}
      radioName="update-category-icon-radio"
      onSelectIcon={(iconId, icon) => {
        pendingUpdateSelectedIconId = iconId;

        const updateIconInput = document.getElementById("update-category-icon");
        if (updateIconInput) {
          updateIconInput.value = iconId;
        }

        window.dispatchEvent(
          new CustomEvent("update-category-icon-selected", {
            detail: { iconId, icon }
          })
        );

        renderUpdateIconLibrary();
      }}
    />
  );
}

function mountIconLibraries() {
  const addRootElement = document.getElementById("icon-library-root");
  if (addRootElement) {
    addRoot = ReactDOM.createRoot(addRootElement);
    renderAddIconLibrary();
  }

  const updateRootElement = document.getElementById("update-icon-library-root");
  if (updateRootElement) {
    updateRoot = ReactDOM.createRoot(updateRootElement);
    renderUpdateIconLibrary();
  }

  window.dispatchEvent(new Event("icons-library-ready"));
}

window.renderIconsLibrary = function renderIconsLibrary(iconsByCategory) {
  pendingIconsByCategory = iconsByCategory || {};
  renderAddIconLibrary();
};

window.renderUpdateIconsLibrary = function renderUpdateIconsLibrary(iconsByCategory, selectedIconId) {
  if (iconsByCategory) {
    pendingIconsByCategory = iconsByCategory;
  }

  if (selectedIconId !== undefined && selectedIconId !== null && selectedIconId !== "") {
    pendingUpdateSelectedIconId = String(selectedIconId);
  }

  renderUpdateIconLibrary();
};

window.setUpdateCategorySelectedIcon = function setUpdateCategorySelectedIcon(iconId) {
  pendingUpdateSelectedIconId = iconId ? String(iconId) : null;
  renderUpdateIconLibrary();
  window.dispatchEvent(
    new CustomEvent("update-category-icon-selected", {
      detail: { iconId: pendingUpdateSelectedIconId }
    })
  );
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", mountIconLibraries);
} else {
  mountIconLibraries();
}
