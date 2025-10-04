/**
 * Configuration and static data for the chat module.
 */

// A unique key to save our chat messages in the browser's local storage.
export const LS_KEY = 'agrihub_chat_messages_v1';

// An object representing the person using the chat.
export const currentUser = {
  id: 'me',
  name: 'You',
  avatar: 'https://placehold.co/80x80/1e4620/FFF?text=Y'
};

// A list of fake users for demonstration purposes.
export const demoUsers = [
  { id: 'u1', name: 'Alemayehu', avatar: 'https://placehold.co/80x80/2a7742/FFF?text=A' },
  { id: 'u2', name: 'Meron', avatar: 'https://placehold.co/80x80/d97706/FFF?text=M' },
  { id: 'u3', name: 'Kebede', avatar: 'https://placehold.co/80x80/0a6e2d/FFF?text=K' }
];