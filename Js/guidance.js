/**
 * This file controls the Farming Guidance page.
 * It fetches a JSON file containing all the guidance topics and renders them
 * in a structured, hierarchical way (Domain > Subdomain > Item > Title).
 */

// The location of the data file.
const DATA_URL = "../data/guidance-map.json";
const root = document.getElementById("guide-root"); // The main container element where all the content will be placed.
const searchInput = document.getElementById("guide-search");
const sidebar = document.getElementById("guidance-sidebar");

/**
 * Handles what happens when a user clicks or activates a guide card.
 * It reads the unique ID from the card and navigates to the detail page for that ID.
 */
function onCardActivate(el) {
  const id = el.getAttribute("data-id");
  if (!id) return;
  window.location.href = `../HTML/guidance-detail.php?id=${encodeURIComponent(
    id
  )}`;
}

/**
 * A helper function to group an array of objects by a specific key.
 * For example, it can take all articles and group them by their 'domain'.
 */
function groupBy(arr, key) {
  // Start with an empty object to hold our groups.
  const groups = {};
  // Loop through each item in the array.
  arr.forEach(item => {
    const groupName = item[key]; // e.g., 'Crop Farming'
    // If we haven't seen this group name before, create an empty array for it.
    if (!groups[groupName]) {
      groups[groupName] = [];
    }
    // Add the current item to its group.
    groups[groupName].push(item);
  });
  return groups;
}

function createCard(a) {
  const art = document.createElement("article");
  art.className = "guide-card";
  art.tabIndex = 0;
  art.setAttribute("data-id", a.id);
  art.setAttribute("aria-label", `Open guide: ${a.item} — ${a.title}`);
  art.innerHTML = `
    <div class="card-media">
      <img src="${a.image}" alt="${a.item} - ${a.title}" loading="lazy" />
    </div>
    <div class="card-body">
      <h3 class="card-title">${a.item}: ${a.title}</h3>
      <p class="card-desc">${a.desc}</p>
    </div>
  `;
  // Add event listeners for both mouse clicks and keyboard activation (Enter/Space).
  art.addEventListener("click", () => onCardActivate(art));
  art.addEventListener("keypress", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      onCardActivate(art);
    }
  });
  return art;
}

/**
 * Takes the raw data and builds the entire nested HTML structure of
 * sections, groups, and card grids.
 */
function renderHierarchy(data) {
  if (!root) return;
  const byDomain = groupBy(data.articles, "domain");
  const domainNames = Object.keys(byDomain);
  const mainContent = document.querySelector('.main-content');
  const mainHeader = document.createElement('div');
  mainHeader.className = 'main-content-header';
  mainContent.insertBefore(mainHeader, root);
  const frag = document.createDocumentFragment();

  Object.entries(byDomain).forEach(([domain, list]) => {
    // Domain section
    const section = document.createElement("section");
    section.className = "guide-domain";
    section.setAttribute("data-domain-name", domain);
    section.id = `domain-${domain.replace(/\s|&/g, "-")}`; // Create a URL-friendly ID

    const content = document.createElement("div");
    content.className = "guide-domain-content"; // Keep class for structure

    const bySub = groupBy(list, "subdomain");
    Object.entries(bySub).forEach(([sub, subList]) => {
      const subWrap = document.createElement("div");
      subWrap.className = "topic-group";
      const subHeader = document.createElement("div");
      subHeader.className = "section-header";
      subHeader.innerHTML = `<h3>${sub}</h3>`;
      subWrap.appendChild(subHeader);

      const byItem = groupBy(subList, "item");
      Object.entries(byItem).forEach(([itemName, items]) => {
        const itemWrap = document.createElement("div");
        itemWrap.className = "item-group";
        const itemHeader = document.createElement("div");
        itemHeader.className = "section-header";
        itemHeader.innerHTML = `<div class="section-sub">${itemName}</div>`;
        itemWrap.appendChild(itemHeader);

        const grid = document.createElement("div");
        grid.className = "card-grid";
        items.forEach((a) => grid.appendChild(createCard(a)));

        itemWrap.appendChild(grid);
        // Only add if there are cards
        if (items.length > 0) {
          subWrap.appendChild(itemWrap);
        }
      });
      // Only add if there's content
      if (subList.length > 0) {
        content.appendChild(subWrap);
      }
    });

    section.appendChild(content);
    frag.appendChild(section);
  });

  root.innerHTML = "";
  root.appendChild(frag);

  // Show the first domain by default
  const firstDomain = document.querySelector('.guide-domain');
  if (firstDomain) {
    firstDomain.classList.add('active');
    mainHeader.innerHTML = `<h2>${firstDomain.getAttribute('data-domain-name')}</h2>`;
  }

  // Render the sidebar navigation
  renderSidebarNav(domainNames);
}

/**
 * Renders the navigation links in the sidebar.
 */
function renderSidebarNav(domains) {
  if (!sidebar) return;

  const navHeader = document.createElement("h3");
  navHeader.textContent = "Categories";

  const list = document.createElement("ul");
  list.className = "domain-nav-list";

  domains.forEach(domain => {
    const item = document.createElement("li");
    const link = document.createElement("a");
    link.href = `#domain-${domain.replace(/\s|&/g, "-")}`;
    link.textContent = domain;
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = link.getAttribute('href');
      const targetSection = document.querySelector(targetId);
      if (!targetSection) return;

      // Manage active state
      document.querySelectorAll('.domain-nav-list a').forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      // Show the selected domain and hide others
      document.querySelectorAll('.guide-domain').forEach(s => s.classList.remove('active'));
      targetSection.classList.add('active');
      
      document.querySelector('.main-content-header h2').textContent = domain;
    });
    item.appendChild(link);
    list.appendChild(item);
  });

  sidebar.innerHTML = '';
  sidebar.appendChild(navHeader);
  sidebar.appendChild(list);

  // Set the first link as active by default
  const firstLink = sidebar.querySelector('.domain-nav-list a');
  if (firstLink) {
    firstLink.classList.add('active');
  }
}

/**
 * Filters the guidance articles based on a search term.
 * It hides non-matching cards and entire domain sections if they have no matching content.
 */
function filterGuides(term) {
  const searchTerm = term.toLowerCase().trim();
  let totalVisible = 0;

  document.querySelectorAll('.guide-card').forEach(card => {
    const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
    const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';
    const isMatch = title.includes(searchTerm) || desc.includes(searchTerm);
    card.hidden = !isMatch;
  });

  document.querySelectorAll('.guide-domain').forEach(domainSection => {
    const visibleCards = domainSection.querySelectorAll('.guide-card:not([hidden])');
    const isActive = domainSection.classList.contains('active');

    if (searchTerm) {
      // If searching, hide domains without matches
      domainSection.hidden = visibleCards.length === 0;
    } else {
      // If search is cleared, only show the active one
      domainSection.hidden = false;
      domainSection.classList.toggle('active', isActive);
    }
    totalVisible += visibleCards.length;
  });

  const emptyState = document.getElementById('empty-state');
  if (emptyState) {
    emptyState.hidden = totalVisible > 0 || !searchTerm;
  }
}

/**
 * The main initialization function for the page.
 */
export async function initializeGuidancePage() {
  // Check if we are on the guidance page by looking for the root element and search input.
  if (!root) return;

  try {
    // Download the guidance data from the JSON file.
    const res = await fetch(DATA_URL);
    const data = await res.json();
    // Once the data is loaded, call the function to render it.
    renderHierarchy(data);

    // Add search event listener
    if (searchInput) {
      searchInput.addEventListener('input', (e) => filterGuides(e.target.value));
    }
  } catch (e) {
    // If the download fails, show an error message to the user.
    root.innerHTML =
      "<p>Failed to load guidance data. Please try again later.</p>";
    console.error("Guidance data load error:", e);
  }
}
