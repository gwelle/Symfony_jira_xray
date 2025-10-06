import fetch from 'node-fetch';

// Génère une chaîne de caractères aléatoire
export function randomString(length = 8, charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
  let str = '';
  for (let i = 0; i < length; i++) {
    str += charset.charAt(Math.floor(Math.random() * charset.length));
  }
  return str;
}

// Génère un prénom fictif (ex : "Zerak")
export function randomFirstName(length = 6) {
  const name = randomString(length).toLowerCase();
  return name.charAt(0).toUpperCase() + name.slice(1);
}

// Génère un nom fictif (ex : "Trivako")
export function randomLastName(length = 8) {
  const name = randomString(length).toLowerCase();
  return name.charAt(0).toUpperCase() + name.slice(1);
}

// Génère un email unique (ex : "qsdfrt.abcd123@gmail.com")
export function randomEmail(domain = 'gmail.com') {
  const local = randomString(6).toLowerCase();
  const uniq = Math.random().toString(36).substring(2, 8); // suffixe unique
  return `${local}.${uniq}@${domain}`;
}

// Génère un mot de passe robuste (ex : "Xy8$trQ!mn2")
export function randomPassword(minLength = 8, maxLength = 15) {
  const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const lower = 'abcdefghijklmnopqrstuvwxyz';
  const digits = '0123456789';
  const symbols = '!@#$%^&*()_+=-';
  const all = upper + lower + digits + symbols;

  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/;

  let pwd = '';
  do {
    const len = Math.floor(Math.random() * (maxLength - minLength + 1)) + minLength;
    pwd = '';
    for (let i = 0; i < len; i++) {
      pwd += all.charAt(Math.floor(Math.random() * all.length));
    }
  } while (!regex.test(pwd));

  return pwd;
}

// Génère un couple mot de passe + confirmation
export function randomPasswordPair(length = 8) {
  const pwd = randomPassword(length);
  return { plainPassword: pwd, confirmationPassword: pwd };
}

/**
 *  Crée un utilisateur via l'API
 * @param {*} apiUrl 
 * @param {*} overrides 
 * @returns 
 */
export async function createUser(apiUrl, overrides = {}) {
  const { plainPassword, confirmationPassword } = randomPasswordPair();

  const payload = {
    email: randomEmail(),
    firstName: randomFirstName(),
    lastName: randomLastName(),
    plainPassword,
    confirmationPassword,
    ...overrides // permet d'écraser un champ pour un test précis
  };

  const response = await fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Accept': 'application/ld+json'
    },
    body: JSON.stringify(payload)
  });

  return { response, payload };
}

/** Active un compte utilisateur via l'API
 * @param {*} apiUrl 
 * @param {*} activationToken 
 * @returns 
 */
export async function activateUser(apiUrl, activationToken) {
  const response = await fetch(`${apiUrl}/activate_account/${activationToken}`, {
    method: 'GET',
    headers: {
      'Accept': 'application/ld+json, application/json, */*;q=0.8'
    }
  });

  return { response };
}