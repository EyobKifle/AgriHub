// hash.js
const bcrypt = require('bcrypt');

const plaintext = 'Agrihubpass'; // choose a strong password
const saltRounds = 12;

bcrypt.hash(plaintext, saltRounds)
  .then(hash => {
    console.log('BCRYPT HASH:', hash);
  })
  .catch(err => console.error(err));
