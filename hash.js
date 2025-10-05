// hash.js
// We use 'bcryptjs' here because it's a pure JavaScript implementation
// and doesn't require any special build tools to install, making it simpler.
// To install it, run: npm install bcryptjs
const bcrypt = require('bcryptjs');

// The number of rounds to use for the salt. 12 is a good, secure default.
const saltRounds = 12;

// Get the password from the command line arguments, or use a default.
// To run: node hash.js YourPasswordHere
const plaintext = process.argv[2] || 'AgrihubPass';

if (plaintext === 'AgrihubPass') {
  console.log("No password provided. Using default: 'AgrihubPass'");
}

console.log(`\nGenerating hash for: "${plaintext}"`);

bcrypt.hash(plaintext, saltRounds, (err, hash) => {
  if (err) {
    console.error('Error generating hash:', err);
    return;
  }
  console.log('\nSUCCESS! Here is your generated hash:\n');
  console.log(hash);
  console.log('\nCopy the hash above and paste it into your SQL query.');
});
