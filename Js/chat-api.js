/**
 * This module handles all API communication for the chat feature.
 * It's designed to interact with a backend that connects to the database.
 */

const API_BASE = '../php/api/messages.php'; // Example API endpoint

/**
 * Fetches all messages for a specific discussion.
 * @param {string} discussionId - The ID of the discussion to load messages for.
 * @returns {Promise<Array>} A promise that resolves to an array of message objects.
 */
export async function fetchMessages(discussionId) {
  // In a real implementation, this would fetch from the backend.
  // For now, it returns an empty array to prevent errors.
  console.log(`Fetching messages for discussion: ${discussionId}`);
  // const response = await fetch(`${API_BASE}?discussion_id=${discussionId}`);
  // if (!response.ok) throw new Error('Failed to fetch messages');
  // const data = await response.json();
  // return data.messages;
  return []; // Replace with actual API call
}

/**
 * Sends a new message to the backend to be saved.
 * @param {object} messageData - The message object to save.
 * @returns {Promise<object>} A promise that resolves to the saved message object from the server.
 */
export async function addMessage(messageData) {
  console.log('Adding new message:', messageData);
  // const response = await fetch(API_BASE, { method: 'POST', body: JSON.stringify(messageData), headers: {'Content-Type': 'application/json'} });
  // if (!response.ok) throw new Error('Failed to send message');
  // return await response.json();
  return messageData; // Simulate successful API call
}

/**
 * Sends an updated message content to the backend.
 * @param {string} messageId - The ID of the message to update.
 * @param {string} content - The new text content for the message.
 */
export async function updateMessage(messageId, content) {
  console.log(`Updating message ${messageId} with content: "${content}"`);
  // await fetch(API_BASE, { method: 'PUT', body: JSON.stringify({ messageId, content }), headers: {'Content-Type': 'application/json'} });
}

/**
 * Sends a request to the backend to delete a message.
 * @param {string} messageId - The ID of the message to delete.
 */
export async function deleteMessage(messageId) {
  console.log(`Deleting message ${messageId}`);
  // await fetch(API_BASE, { method: 'DELETE', body: JSON.stringify({ messageId }), headers: {'Content-Type': 'application/json'} });
}
