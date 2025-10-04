/**
 * This module handles all internationalization (i18n) logic.
 * It loads language files, applies translations to the DOM, and persists the user's choice.
 */
import { fetchAndCache } from './cache.js';

const DEFAULT_LANG = 'en';
let currentTranslations = {};

/**
 * Fetches the translation file for a given language.
 * @param {string} lang - The language code (e.g., 'en', 'am').
 * @returns {Promise<Object>} A promise that resolves to the translation data.
 */
async function fetchTranslations(lang) {
  try {
    // Use fetchAndCache to avoid re-downloading language files during the same session.
    // We use a unique cache key for each language.
    const jsonText = await fetchAndCache(`lang_${lang}`, `../Languages/${lang}.json`);
    return JSON.parse(jsonText);
  } catch (error) {
    console.error(`Error fetching translation file for ${lang}:`, error);
    // Fallback to default language if the selected one fails
    if (lang !== DEFAULT_LANG) {
      return fetchTranslations(DEFAULT_LANG);
    }
    return {};
  }
}

/**
 * Applies translations to all elements on the page with a `data-i18n-key` attribute.
 * It also handles placeholder translations.
 */
export function applyTranslationsToPage() {
  document.querySelectorAll('[data-i18n-key]').forEach(element => {
    const key = element.getAttribute('data-i18n-key');
    const translation = currentTranslations[key];

    if (translation) {
      // Directly set the text content. This is more robust than finding specific text nodes.
      element.textContent = translation;
    }
  });

  document.querySelectorAll('[data-i18n-placeholder-key]').forEach(element => {
    const key = element.getAttribute('data-i18n-placeholder-key');
    const translation = currentTranslations[key];
    if (translation) {
      element.placeholder = translation;
    }
  });
}

/**
 * Sets the application's language.
 * It fetches the new translations, applies them, updates the document's lang attribute,
 * and saves the preference to localStorage.
 * @param {string} lang - The language code to switch to.
 */
export async function setLanguage(lang) {
  if (!lang) {
    lang = DEFAULT_LANG;
  }

  // Load the new language translations
  currentTranslations = await fetchTranslations(lang);

  // Apply translations to the page
  applyTranslationsToPage();

  // Update the language switcher UI to reflect the change
  updateLangSwitcher();

  // Update the document's language attribute for accessibility
  document.documentElement.lang = lang;

  // Save the user's preference
  localStorage.setItem('user_lang', lang);
}
/**
 * Updates the text of the language dropdown button to reflect the current language.
 */
export function updateLangSwitcher() {
  const dropbtn = document.querySelector('.dropdown .dropbtn');
  if (dropbtn) {
    // Re-apply translation, preserving the icon.
    dropbtn.innerHTML = `${getTranslation('header.language')} <i class="fas fa-caret-down"></i>`;
  }
}

/**
 * Initializes the translation system on page load.
 * It determines the language to use based on saved preferences or defaults.
 */
export async function initializeI18n() {
  const savedLang = localStorage.getItem('user_lang');
  await setLanguage(savedLang || DEFAULT_LANG);
}

/**
 * A utility function to get a single translated string.
 * Useful for parts of the app that generate dynamic content in JS.
 * @param {string} key - The translation key.
 * @returns {string} The translated string or the key if not found.
 */
export function getTranslation(key) {
  // Ensure translations are loaded before trying to get one.
  if (Object.keys(currentTranslations).length === 0) {
    console.warn(`i18n: getTranslation('${key}') called before translations were loaded.`);
    return key;
  }
  return currentTranslations[key] || key;
}