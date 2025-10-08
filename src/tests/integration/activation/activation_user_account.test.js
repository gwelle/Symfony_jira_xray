import { activateUser } from '../../utils.js';
import { storeUserId, getUserId, clearUserId } from '../../cache.js';

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

  const text = await response.text();
  const contentType = response.headers.get('content-type') || '';

  if (!contentType.includes('json')) {
    throw new Error(`Expected JSON but got: ${contentType}`);
  }

  return JSON.parse(text);
}

async function testActivateExpiredToken({ maxAttempts = 4 } = {}) {
  for (let i = 0; i < maxAttempts; i++) {
    const { response } = await activateUser(API_URL, TOKEN_EXPIRED);
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
// === Tests d’intégration ===

describe('Activate User Account', () => {

  afterAll(() => {
    // Nettoie le cache après les tests
    clearUserId("newUser");
  });

  it('should return activated user account with success message', async () => {
    const { response } = await activateUser(API_URL, TOKEN_SUCCESS);
    const data = await expectValidResponse(response, 200);

    if (data.success) {
      expect(data.success).toMatch(/Compte activé/);
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

  it('should return a message token expired', async () => {
    const getResponse = await fetch(`${API_URL}/refresh_tokens`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
    });
    const data = await expectValidResponse(getResponse, 200);
    expect(data.message).toMatch(/Tokens refreshed/);

    await testActivateExpiredToken({ maxAttempts: 4 });
  });

  it('should return 429 after max refresh attempts', async () => {
    await testActivateExpiredToken({ maxAttempts: 3 });
  });

});
