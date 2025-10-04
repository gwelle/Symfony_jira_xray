import http from 'k6/http';
import { check, fail } from 'k6';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from './utils.js';

// Configuration test options
export const options = {
    scenarios: { 
        creation_account: { 
            executor: 'per-vu-iterations', // type d'exécution : VUs constants 
            vus: 15,                        // 15 utilisateurs virtuels
            iterations: 1,                  // une itération
            gracefulStop: '500ms'              // temps pour que les VUs terminent leurs itérations
        }
    },
    thresholds: {
        http_req_duration: [
            'avg<3000',   // moyenne ≈ 3s
            'p(90)<4000', // 90% des requêtes < 4s
            'p(95)<4000', // 95% des requêtes < 4s
            'p(99)<5000'  // 99% des requêtes < 5s
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
    'response time < 4200ms (per request)': (r) => r.timings.duration < 4200
  });

  // Log error details
  if (res.status !== 201) {
    fail(`Erreur: status=${res.status}, body=${res.body}`);
  }
}