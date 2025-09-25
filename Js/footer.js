let footerCache = null;

// Load footer.html into the footer-placeholder
export const loadFooter = async () => {
  const placeholder = document.getElementById('footer-placeholder');
  if (!placeholder) {
    console.error('Footer placeholder not found');
    return;
  }

  try {
    if (footerCache) {
      placeholder.innerHTML = footerCache;
      return;
    }

    const response = await fetch('footer.html');
    if (!response.ok) {
      throw new Error(`Failed to load footer.html: ${response.status}`);
    }
    const data = await response.text();
    placeholder.innerHTML = data;
    footerCache = data;
  } catch (error) {
    console.error('Error loading footer:', error);
    placeholder.innerHTML = '<p>Error loading footer. Please try again later.</p>';
  }
};