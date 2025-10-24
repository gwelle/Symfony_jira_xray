import { activateUser, createUser, expectValidResponse, testActivateExpiredToken, resendEmailActivation } from '../../utils.js';

// Récupérer les variable d'environnement
const apiUrl = process.env.API_PLATFORM_URL;
const tokenInvalid = process.env.TOKEN_INVALID;
const tokenExpired = process.env.TOKEN_EXPIRED;
const fakeUserEmail = process.env.FAKE_USER_EMAIL;

if (!apiUrl || !tokenInvalid || !tokenExpired || !fakeUserEmail) {
  throw new Error('Environment variables are not defined');
}

let activationToken = "";

describe('Activate User Account', () => {
  it('should create user, retrieve token, and activate successfully', async () => {

    // === Création d’un utilisateur ===
    const { response: createResponse } = await createUser(apiUrl);
    const userData = await createResponse.json();
    expect(createResponse.status).toBe(201);

    // ===  Récupération du token pour ce user ===
    const getResponse = await fetch(`${apiUrl}/${userData.id}/token`, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' },
    });

    const tokenData = await getResponse.json();
    activationToken = tokenData.token;

    expect(getResponse.status).toBe(200);
    expect(tokenData).toHaveProperty('token');
    expect(activationToken).toBeDefined();

    const { response } = await activateUser(apiUrl, activationToken);
    const data = await expectValidResponse(response, 200);

    (data.status ? 
      expect(data.status).toMatch(/Account activated/) : expect(data.status).toMatch(/Account already activated/)
    );
  });

  it('should return a message already activated', async () => {
    const { response } = await activateUser(apiUrl, activationToken);
    const data = await expectValidResponse(response, 200);
    expect(data.status).toMatch(/Account already activated/);
  });

  it('should return a message invalid token', async () => {
    const { response } = await activateUser(apiUrl, tokenInvalid);
    const data = await expectValidResponse(response, 400);
    expect(data.error).toMatch(/Invalid Token/);
  });

  it('should return a message token expired', async () => {
    const getResponse = await fetch(`${apiUrl}/refresh_tokens`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
    });
    const data = await expectValidResponse(getResponse, 200);
    expect(data.message).toMatch(/Tokens regenerated after many token expirations/);

    await testActivateExpiredToken({ apiUrl, tokenExpired, maxAttempts: 4 })
  });

  it('should return 429 after max refresh attempts', async () => {
      await testActivateExpiredToken({ apiUrl, tokenExpired, maxAttempts: 3 })
  });

  it('should create an user and resend activation email', async () => {

    const { response: createResponse } = await createUser(apiUrl);
    const userData = await createResponse.json();
    expect(createResponse.status).toBe(201);

    // Tester la limite de tentatives de renvoi d'email
    let attempts = 0;           // emails valides envoyés
    let totalAttempts = 0;      // toutes tentatives
    const maxAttempts = 3;      // côté serveur
    const maxTotalAttempts = 6; // limite pour éviter boucle infinie

    // Tant que le nombre de tentatives valides est inférieur à la limite
    // et que le nombre total de tentatives n’a pas dépassé le maximum
    while (attempts < maxAttempts && totalAttempts < maxTotalAttempts) {
      //const { response: resendResponse } = await resendEmailActivation(apiUrl, fakeUserEmail);
      const { response: resendResponse } = await resendEmailActivation(apiUrl, userData.email);
      const status = resendResponse.status;
      const resendData = await expectValidResponse(resendResponse, [200, 404, 429]);

      totalAttempts++; // Incrémente à chaque tentative

      // Si le statut est 200, on incrémente les tentatives et on vérifie le message
      if(resendResponse.status === 200) {
        attempts++;
        expect(resendData.status).toMatch(/resend/);
        expect(resendData.info).toMatch(/Checking resend email/);
      }
      // Si le statut est 404, on vérifie le message et on continue la boucle
      else if (status === 404) {
        expect(resendData.status).toMatch(/handled/);
        expect(resendData.info).toMatch(/Checking resend email/);
      }
      // Si le statut est 429, on vérifie le message et on sort de la boucle
      else if (status === 429) {
        expect(resendData.status).toMatch(/error/);
        expect(resendData.error).toMatch(/Max resend reached/);
        break;
      }
      else {
        throw new Error(`Unexpected status ${status}: ${JSON.stringify(resendData)}`);
      }

      // petite pause pour éviter d’enchaîner trop vite
      await new Promise(r => setTimeout(r, 300));
    }
    // Vérifie que le nombre de tentatives valides n’a pas dépassé la limite
    // et que le nombre total de tentatives est raisonnable
    expect(attempts).toBeLessThanOrEqual(maxAttempts);
    expect(totalAttempts).toBeLessThanOrEqual(maxTotalAttempts);
    
  });

});
