/**
 * Génère une chaîne de caractères aléatoire  (ex : "aZxYbCdE")
 * @param {*} length 
 * @param {*} charset 
 * @returns 
 */
export function randomString(length = 8, charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
  let str = '';
  for (let i = 0; i < length; i++) {
    str += charset.charAt(Math.floor(Math.random() * charset.length));
  }
  return str;
}

/**
 * Génère un prénom fictif (ex : "Julien")
 * @param {*} length 
 * @returns 
 */
export function randomFirstName(length = 6) {
  const name = randomString(length).toLowerCase();
  return name.charAt(0).toUpperCase() + name.slice(1);
}

/**
 * Génère un nom fictif (ex : "Trivako")
 * @param {*} length 
 * @returns 
 */
export function randomLastName(length = 8) {
  const name = randomString(length).toLowerCase();
  return name.charAt(0).toUpperCase() + name.slice(1);
}

/**
 * Génère un email unique (ex : "qsdfrt.abcd123@gmail.com") 
 * @param {*} domain 
 * @returns 
 */
export function randomEmail(domain = 'gmail.com') {
  const local = randomString(6).toLowerCase();
  const uniq = Math.random().toString(36).substring(2, 10); // suffixe unique
  return `${local}.${uniq}@${domain}`;
}

/**
 * Génère un mot de passe robuste (ex : "Xy8$trQ!mn2")
 * @param {*} minLength 
 * @param {*} maxLength 
 * @returns 
 */
export function randomPassword(minLength = 8, maxLength = 15) {
  const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const lower = 'abcdefghijklmnopqrstuvwxyz';
  const digits = '0123456789';
  const symbols = '!@#$%^&*()_+=-';
  const all = upper + lower + digits + symbols;

  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/;

  let pwd = '';
  do {
    const length = Math.floor(Math.random() * (15 - 8 + 1)) + 8; // longueur entre 8 et 15
    pwd = '';
    for (let i = 0; i < length; i++) {
      pwd += all.charAt(Math.floor(Math.random() * all.length));
    }
  } 
  while (!regex.test(pwd));

  return pwd;
}

/**
 * Génère un couple mot de passe + confirmation
 * @param {*} length 
 * @returns 
 */
export function randomPasswordPair(length = 8) {

  const pwd = randomPassword(length);

  // Vérification immédiate et stricte
  if (!pwd || typeof pwd !== 'string') {
    fail('[ERREUR] Mot de passe invalide généré');
  }

  const pair = {
    plainPassword: pwd.trim(),
    confirmationPassword: pwd.trim(), // on s’assure qu’ils sont *strictement* identiques
  };

  // Double vérification
  if (pair.plainPassword !== pair.confirmationPassword) {
    console.error('[ERREUR CRITIQUE] Les mots de passe générés ne correspondent pas :', pair);
    fail('Les mots de passe ne correspondent pas — test interrompu');
  }

  return pair;
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
      'Accept': 'application/ld+json, application/json, */*;q=0.8'
    },
    body: JSON.stringify(payload)
  });

  return { response, payload };
}


/** 
 * Active un compte utilisateur via l'API
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

/**
 *  Renvoie un email d'activation de compte via l'API
 * @param {*} apiUrl 
 * @param {*} emailUser
 * @returns 
 */
export async function resendEmailActivation(apiUrl, emailUser) {
  const response = await fetch(`${apiUrl}/resend_activation_account`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Accept': 'application/ld+json, application/json, */*;q=0.8'
    },
    body: JSON.stringify({email: emailUser})
  });

  return { response };
}

/** 
 * Vérifie qu'une réponse est valide et au format JSON
 * @param {*} response 
 * @param {*} expectedStatus 
 * @returns 
 */
export async function expectValidResponse(response, expectedStatus) {
  // Autorise soit un nombre, soit un tableau de nombres
  const allowedStatuses = Array.isArray(expectedStatus) ? expectedStatus : [expectedStatus];

  // Vérifie que le statut HTTP reçu fait partie des statuts attendus
  expect(allowedStatuses).toContain(response.status);

  const text = await response.text();
  const contentType = response.headers.get('content-type') || '';

  if (!contentType.includes('json')) {
    throw new Error(`Expected JSON but got: ${contentType}`);
  }
  return JSON.parse(text);
}

/**
 * Teste l'activation avec un token expiré, en gérant la limite de tentatives
 * @param {*} apiUrl
 * @param {*} tokenExpired
 * @param {*} maxAttempts
 * @return {*}
 */
export async function testActivateExpiredToken({ apiUrl, tokenExpired, maxAttempts = 4 } = {}) {
  for (let i = 0; i < maxAttempts; i++) {
    const { response } = await activateUser(apiUrl, tokenExpired);
    const status = response.status;
    const result = await response.json();

    if (status === 400) {
      // Token expiré
      expect(result.error).toMatch(/token_expired/);
    } 
    else if (status === 429) {
      // Limite atteinte
      expect(result.error).toMatch(/max_resend_reached/);
      break; // inutile de continuer
    } else {
      throw new Error(`Unexpected status ${status}: ${JSON.stringify(result)}`);
    }
    await new Promise(r => setTimeout(r, 300)); // pause facultative
  }
}