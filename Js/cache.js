/**
 * This function is a "smart downloader" for reusable parts of our website, like the header or footer.
 *
 * It's designed to be fast. Here's how it works:
 * 1. It checks if we've already downloaded a piece of content (like the header) during this browser session.
 * 2. If we have a saved copy, it uses that instantly.
 * 3. If not, it downloads the content from the server just once, saves it, and then uses it.
 *
 * This way, pages load much faster after the first visit.
 *
 * It takes two pieces of information:
 * - cacheKey: A unique name to save the content under (like 'headerHTML').
 * - url: The path to the file to download (like '../HTML/header.html').
 */
export const fetchAndCache = async (cacheKey, url) => {
  // Try to get the content from the cache first
  const cachedContent = sessionStorage.getItem(cacheKey);
  if (cachedContent) {
    return cachedContent;
  }

  // If not in cache, fetch it from the network
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Failed to load content from ${url}: ${response.status}`);
  }
  const data = await response.text();

  // Store the fetched content in the cache for next time
  sessionStorage.setItem(cacheKey, data);

  return data;
};