/**
 * Reads session data securely embedded in the DOM by the server.
 * This prevents hardcoding user details in JavaScript files.
 */

const body = document.body;

export const session = {
    discussionId: body.dataset.discussionId || 'general',
    currentUser: {
        id: parseInt(body.dataset.userId, 10) || 0,
        name: body.dataset.userName || 'Guest',
        avatar: body.dataset.userAvatar || 'https://placehold.co/48x48/cccccc/FFF?text=G'
    }
};