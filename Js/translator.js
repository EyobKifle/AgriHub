/**
 * This module handles the translation of the website's text content.
 */

const TRANSLATE_API_URL = "http://localhost:5000/translate";

/**
 * Translates a given text string using the translation API.
 * @param {string} text The text to translate.
 * @param {string} targetLang The target language code (e.g., 'en', 'am').
 * @param {string} sourceLang The source language code (e.g., 'am', 'en').
 * @returns {Promise<string>} The translated text.
 */
async function translateText(text, targetLang, sourceLang = 'en') {
  try {
    const response = await fetch(TRANSLATE_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        q: text,
        source: sourceLang,
        target: targetLang,
      }),
    });

    if (!response.ok) {
      throw new Error(`Translation API error: ${response.statusText}`);
    }

    const data = await response.json();
    return data.translatedText;
  } catch (error) {
    console.error("Translation failed:", error);
    return text; // Return original text on failure
  }
}

/**
 * Finds all text nodes in the document body and translates them.
 * This is a simple implementation and might be slow on large pages.
 */
export async function translatePageContent(targetLang) {
  // This is where you would implement the logic to walk the DOM
  // and replace text content. For a real-world app, you would
  // likely use a library or a more sophisticated approach that uses
  // translation keys instead of direct DOM manipulation.
  console.log(`TODO: Implement page translation to ${targetLang}`);
  // Example:
  // const textToTranslate = document.body.innerText;
  // const translated = await translateText(textToTranslate, targetLang);
  // ...then you would need a way to put it back.
}