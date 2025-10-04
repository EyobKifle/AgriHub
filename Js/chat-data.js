import { LS_KEY } from './chat-config.js';

/**
 * Loads the saved chat messages from the browser's local storage.
 * It uses a try-catch block in case the saved data is corrupted.
 */
export function loadMessages() {
  try {
    return JSON.parse(localStorage.getItem(LS_KEY)) || [];
  } catch {
    return [];
  }
}

/**
 * Saves the entire list of messages to the browser's local storage.
 */
export function saveMessages(list) {
  localStorage.setItem(LS_KEY, JSON.stringify(list));
}