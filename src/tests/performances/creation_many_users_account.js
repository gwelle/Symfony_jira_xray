import http from 'k6/http';
import { check } from 'k6';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from './utils.js';

// Configuration test options
export const options = {
    scenarios: {
        default: {
            executor: 'constant-vus', // type d'exécution : VUs constants
            vus: 15,                   // 20 utilisateurs virtuels
            duration: '5s',            // durée totale du test
            gracefulStop: '2s',        // temps pour que les VUs terminent leurs itérations
        }
    },
    thresholds: {
        http_req_duration: [
          'avg<2000',   // moyenne ≈ 2s
          'p(90)<2800', // 90% des requêtes < 2.8s
          'p(95)<3000', // 95% des requêtes < 3s
          'p(99)<3400'  // 99% des requêtes < 3.4s
],
checks: ['rate>0.85'] // tolérance 85% de checks réussis
    } 
};

/**
 * Function to create a user account
 * This function sends a POST request to create a new user and checks the response
 */
export default function createManyUsersTest() {

  // Load the API URL from environment variables
    const API_URL = __ENV.API_PLATFORM_URL;
    if (!API_URL) {
      return;
    }
  
    // Define the payload for creating a user account
    const payload = JSON.stringify({
        email: randomEmail(),
        firstName: randomFirstName(),
        lastName: randomLastName(),
        plainPassword: randomPasswordPair().plainPassword,
        confirmationPassword: randomPasswordPair().confirmationPassword,
    });
  

  // Set the request headers
  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };

  // Send the POST request to create a user account
  const res = http.post(`${API_URL}`, payload, params);

  // Check the response status and time
  check(res, {
    'is status 201': (r) => r.status === 201,
    'response time < 3700ms (per request)': (r) => r.timings.duration < 3700
  });

  // Log error details
  if (res.status !== 201) {
    console.error(`Erreur: status=${res.status}, body=${res.body}`);
  }
}