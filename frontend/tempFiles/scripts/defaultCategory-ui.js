// Sélecteurs principaux de la page.
const listContainer = document.getElementById('category-list');
const statusBox = document.getElementById('status-message');

// En preview PhpStorm (port 63342), on cible explicitement le backend Laragon.
const isPhpStormPreview = window.location.port === '63342';

const CATEGORIES_API_URL = isPhpStormPreview
  ? 'http://localhost/compte/backend/controllers/defaultCategoriesCont.php'
  : '/compte/backend/controllers/defaultCategoriesCont.php';


function setStatus(message, type = '') {
  statusBox.textContent = message;
  statusBox.className = type;
}


function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}


function renderCategories(categories) {
  listContainer.innerHTML = '';

  if (!categories.length) {
    listContainer.innerHTML = '<p>Aucune catégorie par défaut trouvée.</p>';
    return;
  }

  const groupedCategories = {
    expense: [],
    income: []
  };

  categories.forEach(category => {
    if (!groupedCategories[category.type]) {
      groupedCategories[category.type] = [];
    }

    groupedCategories[category.type].push(category);
  });

  const typeLabels = {
    expense: 'Dépenses',
    income: 'Revenus'
  };

  const blocks = Object.entries(groupedCategories)
    .filter(([, items]) => items.length > 0)
    .map(([type, items]) => {

      const categoryMarkup = items.map(category => {

        const src = `../../${category.iconPath}`;

        return `
          <div class="category-item">

            <div
              class="category-icon"
              style="--category-color: ${escapeHtml(category.color)}"
            >
              <img
                src="${escapeHtml(src)}"
                alt="${escapeHtml(category.name)}"
              >
            </div>

            <div class="category-info">

              <strong>
                ${escapeHtml(category.name)}
              </strong>

              <span>
                ${escapeHtml(category.iconName)}
              </span>

              <span class="category-state">
                ${escapeHtml(category.state)}
              </span>

            </div>

          </div>
        `;
      }).join('');

      return `
        <div class="category-block">

          <h2>
            ${typeLabels[type] ?? escapeHtml(type)}
          </h2>

          <div class="category-grid">
            ${categoryMarkup}
          </div>

        </div>
      `;
    })
    .join('');

  listContainer.innerHTML = blocks;
}


async function seedCategories() {
  setStatus(
    'Ajout des catégories en cours...',
    ''
  );

  try {

    const response = await fetch(
      `${CATEGORIES_API_URL}?task=seed_default`
    );

    const data = await response.json();

    if (!response.ok || !data.success) {

      setStatus(
        data.message ??
        'Erreur pendant l\'ajout des catégories.',
        'error'
      );

      return;
    }

    const created =
      data.stats?.created_categories ?? 0;

    const existing =
      data.stats?.existing_categories ?? 0;

    setStatus(
      `${data.message} Créées : ${created} — déjà présentes : ${existing}.`,
      'success'
    );

    await loadCategories();

  } catch (error) {

    setStatus(
      'Erreur réseau lors de l\'ajout des catégories.',
      'error'
    );

  }
}


async function loadCategories() {
  setStatus(
    'Chargement des catégories...',
    ''
  );

  try {

    const response = await fetch(
      `${CATEGORIES_API_URL}?task=list_all`
    );

    const data = await response.json();

    if (!response.ok || !data.success) {

      setStatus(
        data.message ??
        'Impossible de charger les catégories.',
        'error'
      );

      return;
    }

    renderCategories(
      data.categories ?? []
    );

    setStatus(
      `${data.count ?? 0} catégorie(s) par défaut chargée(s).`,
      'success'
    );

  } catch (error) {

    setStatus(
      'Erreur réseau lors du chargement des catégories.',
      'error'
    );

  }
}


document
  .getElementById('seed-categories-btn')
  .addEventListener(
    'click',
    seedCategories
  );


document
  .getElementById('show-categories-btn')
  .addEventListener(
    'click',
    loadCategories
  );
