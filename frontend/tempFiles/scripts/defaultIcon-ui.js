// Sélecteurs principaux de la page.
const listContainer = document.getElementById('icon-list');
const statusBox = document.getElementById('status-message');

// En preview PhpStorm (port 63342), on cible explicitement le backend Laragon.
const isPhpStormPreview = window.location.port === '63342';
const ICONS_API_URL = isPhpStormPreview
  ? 'http://localhost/compte/backend/controllers/defaultIconsCont.php'
  : '/compte/backend/controllers/defaultIconsCont.php';

function setStatus(message, type = '') {
  statusBox.textContent = message;
  statusBox.className = type;
}

function renderCategories(categories) {
  listContainer.innerHTML = '';

  if (!Object.keys(categories).length) {
    listContainer.innerHTML = '<p>Aucune icône trouvée.</p>';
    return;
  }

  const blocks = Object.entries(categories).map(([categoryName, icons]) => {
    const iconMarkup = icons.map(icon => {
      const src = `../../${icon.path}`;
      return `
        <div class="icon-item">
          <img src="${src}" alt="${icon.name}">
          <span>${icon.name}</span>
        </div>
      `;
    }).join('');

    return `
      <div class="category-block">
        <h2>${categoryName}</h2>
        <div class="icon-grid">
          ${iconMarkup}
        </div>
      </div>
    `;
  }).join('');

  listContainer.innerHTML = blocks;
}

async function seedIcons() {
  setStatus('Ajout en cours...', '');

  try {
    const response = await fetch(`${ICONS_API_URL}?task=seed_default`);
    const data = await response.json();

    if (!response.ok || !data.success) {
      setStatus(data.message || 'Erreur pendant l\'ajout des icônes.', 'error');
      return;
    }

    setStatus(data.message || 'Icônes ajoutées avec succès.', 'success');
    await loadIcons();
  } catch (error) {
    setStatus('Erreur réseau lors de l\'ajout des icônes.', 'error');
  }
}

async function loadIcons() {
  setStatus('Chargement des icônes...', '');

  try {
    const response = await fetch(`${ICONS_API_URL}?task=list_all`);
    const data = await response.json();

    if (!response.ok || !data.success) {
      setStatus(data.message || 'Impossible de charger les icônes.', 'error');
      return;
    }

    renderCategories(data.categories || {});
    setStatus(`${data.count || 0} icône(s) chargée(s).`, 'success');
  } catch (error) {
    setStatus('Erreur réseau lors du chargement des icônes.', 'error');
  }
}

document.getElementById('seed-icons-btn').addEventListener('click', seedIcons);
document.getElementById('show-icons-btn').addEventListener('click', loadIcons);
