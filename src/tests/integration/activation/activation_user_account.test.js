import { activateUser } from '../../utils.js';
import { storeUserId, getUserId } from '../../cache.js';

// Récupérer les variable d'environnement
const API_URL = process.env.API_PLATFORM_URL;
const TOKEN_SUCCESS = process.env.TOKEN_SUCCESS;
const TOKEN_INVALID = process.env.TOKEN_INVALID;
const TOKEN_EXPIRED = process.env.TOKEN_EXPIRED;

if (!API_URL || !TOKEN_SUCCESS || !TOKEN_INVALID || !TOKEN_EXPIRED) {
  throw new Error('Environment variables are not defined');
}

// === Helpers génériques ===
async function expectValidResponse(response, expectedStatus) {
  expect(response.status).toBe(expectedStatus);

  const contentType = response.headers.get('content-type');
  expect(contentType).toMatch(/application\/(ld\+json|json)/);

  const data = await response.json();
  expect(data && Object.keys(data).length).toBeGreaterThan(0);
  return data;
}

describe('Activate User Account', () => {

  it('should return activated user account with success message', async () => {
    const { response } = await activateUser(API_URL, TOKEN_SUCCESS);
    const data = await expectValidResponse(response, 200);

    if (data.success) {
      expect(data.success).toMatch(/Compte activé/);

      // Vérifie qu’il n’y a plus de token pour cet utilisateur
      const userId = getUserId("newUser");
      const getResponse = await fetch(`${API_URL}/${userId}/token`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
      });

      const getData = await getResponse.json();
      expect(getResponse.status).toBe(404);
      expect(getData.error).toBe('User not found');
    } 
    else {
      expect(data.info).toMatch(/already_activated/);
    }
  });

  it('should return a message already activated', async () => {
    const { response } = await activateUser(API_URL, TOKEN_SUCCESS);
    const data = await expectValidResponse(response, 200);
    expect(data.info).toMatch(/already_activated/);
  });

  it('should return a message invalid token', async () => {
    const { response } = await activateUser(API_URL, TOKEN_INVALID);
    const data = await expectValidResponse(response, 400);
    expect(data.error).toMatch(/invalid_token/);
 });

});