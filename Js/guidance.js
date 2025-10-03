// Render 4-level hierarchy from JSON and handle navigation
const DATA_URL = '../data/guidance-map.json';
const root = document.getElementById('guide-root');

function onCardActivate(el) {
  const id = el.getAttribute('data-id');
  if (!id) return;
  window.location.href = `../HTML/guidance-detail.php?id=${encodeURIComponent(id)}`;
}

function groupBy(arr, key) {
  return arr.reduce((acc, item) => {
    const k = item[key];
    (acc[k] ||= []).push(item);
    return acc;
  }, {});
}

function createCard(a) {
  const art = document.createElement('article');
  art.className = 'guide-card';
  art.tabIndex = 0;
  art.setAttribute('data-id', a.id);
  art.setAttribute('aria-label', `Open guide: ${a.item} — ${a.title}`);
  art.innerHTML = `
    <div class="card-media">
      <img src="${a.image}" alt="${a.item} - ${a.title}" loading="lazy" />
    </div>
    <div class="card-body">
      <h3 class="card-title">${a.item}: ${a.title}</h3>
      <p class="card-desc">${a.desc}</p>
    </div>
  `;
  art.addEventListener('click', () => onCardActivate(art));
  art.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onCardActivate(art); }
  });
  return art;
}

function renderHierarchy(data) {
  const byDomain = groupBy(data.articles, 'domain');
  const frag = document.createDocumentFragment();

  Object.entries(byDomain).forEach(([domain, list]) => {
    // Domain section
    const section = document.createElement('section');
    section.className = 'group';
    const h2 = document.createElement('div');
    h2.className = 'section-header';
    h2.innerHTML = `<h2>${domain}</h2>`;
    section.appendChild(h2);

    const bySub = groupBy(list, 'subdomain');
    Object.entries(bySub).forEach(([sub, subList]) => {
      const subWrap = document.createElement('div');
      subWrap.className = 'topic-group';
      const h3 = document.createElement('div');
      h3.className = 'section-header';
      h3.innerHTML = `<h3>${sub}</h3>`;
      subWrap.appendChild(h3);

      const byItem = groupBy(subList, 'item');
      Object.entries(byItem).forEach(([itemName, items]) => {
        const itemWrap = document.createElement('div');
        itemWrap.className = 'topic-group';
        const h4 = document.createElement('div');
        h4.className = 'section-header';
        h4.innerHTML = `<div class="section-sub">${itemName}</div>`;
        itemWrap.appendChild(h4);

        const grid = document.createElement('div');
        grid.className = 'card-grid';
        items.forEach(a => grid.appendChild(createCard(a)));

        itemWrap.appendChild(grid);
        subWrap.appendChild(itemWrap);
      });

      section.appendChild(subWrap);
    });

    frag.appendChild(section);
  });

  root.innerHTML = '';
  root.appendChild(frag);
}

async function init() {
  try {
    const res = await fetch(DATA_URL);
    const data = await res.json();
    renderHierarchy(data);
  } catch (e) {
    root.innerHTML = '<p>Failed to load guidance data. Please try again later.</p>';
    console.error('Guidance data load error:', e);
  }
}

document.addEventListener('DOMContentLoaded', init);
