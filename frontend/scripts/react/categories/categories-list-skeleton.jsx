const { useState } = React;

function getCategoryTypeLabel(type) {
  if (type === "income") {
    return "Revenu";
  }

  if (type === "expense") {
    return "Dépense";
  }

  return "Mixte";
}

function CategoryCard({ category, onToggleState, onAskDelete, onEdit }) {
  const categoryName = category?.name || "Sans nom";

  // Une catégorie par défaut ne peut être ni modifiée ni supprimée.
  const isDefault =
    Number(category?.isDefault) === 1 ||
    category?.isDefault === true;

  const typeLabel = getCategoryTypeLabel(category?.type);

  const typeClass = category?.type === "income"
    ? "category-type category-type--income"
    : "category-type category-type--expense";

  const stateLabel = category?.state === "inactive"
    ? "Inactif"
    : "Actif";

  const stateClass = category?.state === "inactive"
    ? "category-state-badge is-inactive"
    : "category-state-badge is-active";

  return (
    <article className="category-card">

      <div className="category-main">

        <span
          className="category-icon-shell"
          style={{ backgroundColor: category?.color || "#667085" }}
          aria-hidden="true"
        >

          {category?.iconPath ? (
            <img
              src={`../../${category.iconPath}`}
              alt=""
              width="24"
              height="24"
            />
          ) : (
            <span className="category-icon-fallback">•</span>
          )}

        </span>


        <div className="category-info">

          <p className="category-name">
            {categoryName}
          </p>

          <p className={typeClass}>
            {typeLabel}
          </p>

        </div>

      </div>


      <div className="category-actions">

        {/* Toutes les catégories peuvent être activées / désactivées. */}
        <button
          type="button"
          className={stateClass}
          onClick={() =>
            onToggleState(
              category?.idCategory
            )
          }
        >
          {stateLabel}
        </button>


        {/* Les actions d'édition sont réservées aux catégories personnelles. */}
        {!isDefault && (
          <button
            type="button"
            className="category-action-btn is-neutral"
            onClick={() =>
              onEdit(category)
            }
          >
            Modifier
          </button>
        )}


        {!isDefault && (
          <button
            type="button"
            className="category-action-btn is-danger"
            onClick={() =>
              onAskDelete(category)
            }
          >
            Supprimer
          </button>
        )}

      </div>

    </article>
  );
}

function CategoryColumn({ title, categories, onToggleState, onAskDelete, onEdit }) {
  return (
    <section className="categories-column">
      <h5>{title}</h5>

      <div className="categories-cards-list">

        {Array.isArray(categories) && categories.length > 0 ? (

          categories.map(category => (
            <CategoryCard
              key={String(category.idCategory)}
              category={category}
              onToggleState={onToggleState}
              onAskDelete={onAskDelete}
              onEdit={onEdit}
            />
          ))

        ) : (

          <p className="categories-empty-state">
            Aucune catégorie.
          </p>

        )}

      </div>
    </section>
  );
}

function DeleteCategoryModal({ category, onCancel, onConfirm, loading, errorMessage }) {
  if (!category) {
    return null;
  }

  return (
    <div
      className="category-modal-backdrop"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-category-title"
    >

      <form
        className="category-modal"
        onSubmit={onConfirm}
      >

        <h3 id="delete-category-title">
          Confirmer la suppression
        </h3>

        <p>
          Voulez-vous supprimer la catégorie <strong>{category.name}</strong> ?
        </p>


        {errorMessage ? (
          <p className="category-modal-error">
            {errorMessage}
          </p>
        ) : null}


        <div className="category-modal-actions">

          <button
            type="button"
            className="category-action-btn is-neutral"
            onClick={onCancel}
            disabled={loading}
          >
            Annuler
          </button>

          <button
            type="submit"
            className="category-action-btn is-danger"
            disabled={loading}
          >
            {loading ? "Suppression..." : "Confirmer"}
          </button>

        </div>

      </form>

    </div>
  );
}

function CategoriesList({ activeCategories, inactiveCategories }) {
  const [categoryToDelete, setCategoryToDelete] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [deleteError, setDeleteError] = useState("");

  function handleToggleState(categoryId) {
    if (!categoryId || typeof window.toggleCategoryState !== "function") {
      return;
    }

    window.toggleCategoryState(categoryId);
  }

  function handleAskDelete(category) {
    if (!category?.idCategory) {
      return;
    }

    // Protection UI supplémentaire.
    if (Number(category?.isDefault) === 1 || category?.isDefault === true) {
      return;
    }

    setDeleteError("");
    setCategoryToDelete(category);
  }

  function handleEdit(category) {
    if (!category?.idCategory || typeof window.openUpdateCategoryForm !== "function") {
      return;
    }

    // Protection UI supplémentaire.
    if (Number(category?.isDefault) === 1 || category?.isDefault === true) {
      return;
    }

    window.openUpdateCategoryForm(category);
  }

  function handleCancelDelete() {
    if (isDeleting) {
      return;
    }

    setDeleteError("");
    setCategoryToDelete(null);
  }

  async function handleConfirmDelete(event) {
    event.preventDefault();

    if (!categoryToDelete?.idCategory || typeof window.deleteCategory !== "function") {
      return;
    }

    setIsDeleting(true);
    setDeleteError("");

    const result =
      await window.deleteCategory(
        categoryToDelete.idCategory
      );

    if (result?.success) {
      setCategoryToDelete(null);
      setIsDeleting(false);
      return;
    }

    setDeleteError(
      result?.message ||
      "Impossible de supprimer la catégorie."
    );

    setIsDeleting(false);
  }

  return (
    <>

      <div className="categories-grid">

        <CategoryColumn
          title="Catégories actives"
          categories={activeCategories}
          onToggleState={handleToggleState}
          onAskDelete={handleAskDelete}
          onEdit={handleEdit}
        />

        <CategoryColumn
          title="Catégories inactives"
          categories={inactiveCategories}
          onToggleState={handleToggleState}
          onAskDelete={handleAskDelete}
          onEdit={handleEdit}
        />

      </div>


      <DeleteCategoryModal
        category={categoryToDelete}
        onCancel={handleCancelDelete}
        onConfirm={handleConfirmDelete}
        loading={isDeleting}
        errorMessage={deleteError}
      />

    </>
  );
}

let categoriesRoot = null;

let pendingCategories = {
  activeCategories: [],
  inactiveCategories: []
};

function mountCategoriesList() {
  const categoriesRootElement =
    document.getElementById(
      "categories-list-root"
    );

  if (!categoriesRootElement) {
    return;
  }

  categoriesRoot =
    ReactDOM.createRoot(
      categoriesRootElement
    );

  categoriesRoot.render(
    <CategoriesList
      activeCategories={pendingCategories.activeCategories}
      inactiveCategories={pendingCategories.inactiveCategories}
    />
  );

  window.dispatchEvent(
    new Event(
      "categories-list-ready"
    )
  );
}

window.renderCategoriesList = function renderCategoriesList(categoriesPayload) {
  pendingCategories = {
    activeCategories:
      Array.isArray(categoriesPayload?.activeCategories)
        ? categoriesPayload.activeCategories
        : [],

    inactiveCategories:
      Array.isArray(categoriesPayload?.inactiveCategories)
        ? categoriesPayload.inactiveCategories
        : []
  };

  if (!categoriesRoot) {
    return;
  }

  categoriesRoot.render(
    <CategoriesList
      activeCategories={pendingCategories.activeCategories}
      inactiveCategories={pendingCategories.inactiveCategories}
    />
  );
};

if (document.readyState === "loading") {
  document.addEventListener(
    "DOMContentLoaded",
    mountCategoriesList
  );
} else {
  mountCategoriesList();
}
