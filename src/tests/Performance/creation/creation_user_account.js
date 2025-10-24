import http from 'k6/http';
import { check, fail } from 'k6';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from '../../utils.js';

// Configuration test options
export const options = {
    scenarios: { 
      creation_account: { 
        executor: 'per-vu-iterations', // type d'exécution : per-vu-iterations
        vus: 1,                   // 1 utilisateur virtuel
        iterations: 1,            // 1 itération
        gracefulStop: '500ms'       // temps pour que les VUs terminent leurs itérations
    }
  },
    thresholds: {
      // Seuils de performance pour les requêtes HTTP
      // Moyenne en dessous de 700ms, 
      // 95% en dessous de 750ms, 
      // 99% en dessous de 800ms
      http_req_duration: [
        'avg<700', 
        'p(95)<750', 
        'p(99)<800'
      ]
  }
};

/**
 * Function to create a user account
 * This function sends a POST request to create a new user and checks the response
 */
export default function createUserTest() {

  // Load the API URL from environment variables
  const API_URL = __ENV.API_PLATFORM_URL;
  if (!API_URL) {
    return;
  }

  const { plainPassword, confirmationPassword } = randomPasswordPair();
  // Define the payload for creating a user account
  const payload = JSON.stringify({
      email: randomEmail(),
      firstName: randomFirstName(),
      lastName: randomLastName(),
      plainPassword: plainPassword,
      confirmationPassword: confirmationPassword,
  });

  // Set the request headers
  const params = {
    headers: {
      'Content-Type': 'application/json',
    }
  };

  // Send the POST request to create a user account
  const res = http.post(`${API_URL}`, payload, params);

  // Check the response status and time
  check(res, {
    'is status 201': (r) => r.status === 201,
    'response time < 900ms (per request)': (r) => r.timings.duration < 900
  });

  // Log error details
  if (res.status !== 201) {
    fail(`Erreur: status=${res.status}, body=${res.body}`);
  }
}