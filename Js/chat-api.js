/**
 * This module is the data layer for the chat application.
 * It handles all communication with the backend PHP API.
 * It abstracts away the fetch calls, so the UI code doesn't need to know about URLs or HTTP methods.
 */

const API_URL = '../php/api/discussions.php';

/**
 * A helper function to handle API requests and parse the JSON response.
 * @param {string} url - The API endpoint URL.
 * @param {object} options - The options for the fetch call (method, headers, body).
 * @returns {Promise<any>} The JSON data from the API response.
 * @throws {Error} If the network response is not ok or the API returns an error.
 */
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            // Try to get a more specific error from the API response body
            const errorBody = await response.json().catch(() => null);
            const errorMessage = errorBody?.error?.code || `HTTP error! status: ${response.status}`;
            throw new Error(errorMessage);
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error?.code || 'error.api.unknown');
        }
        return data;
    } catch (error) {
        console.error('API Request Failed:', error);
        // Re-throw the error so the calling function can handle it
        throw error;
    }
}

/**
 * Fetches all messages for a given discussion from the server.
 * @param {string} discussionId - The ID of the discussion to fetch messages for.
 * @returns {Promise<Array>} A promise that resolves to an array of message objects.
 */
export async function getMessages(discussionId) {
    const data = await apiRequest(`${API_URL}?action=get_messages&discussion_id=${encodeURIComponent(discussionId)}`);
    return data.messages || [];
}

/**
 * Sends a new message to the server to be saved.
 * @param {object} message - The message object to add.
 * @returns {Promise<object>} The server's response, likely including the new message's ID.
 */
export async function addMessage(message) {
    return apiRequest(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add_message', message: message })
    });
}

/**
 * Sends an updated message content to the server.
 * @param {string} messageId - The ID of the message to update.
 * @param {string} newText - The new text content for the message.
 * @returns {Promise<object>} The server's confirmation response.
 */
export async function updateMessage(messageId, newText) {
    return apiRequest(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_message', message_id: messageId, text: newText })
    });
}

/**
 * Sends a request to the server to delete a message.
 * @param {string} messageId - The ID of the message to delete.
 * @returns {Promise<object>} The server's confirmation response.
 */
export async function deleteMessage(messageId) {
    return apiRequest(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_message', message_id: messageId })
    });
}