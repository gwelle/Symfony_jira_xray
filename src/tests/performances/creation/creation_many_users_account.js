import http from 'k6/http';
import { check, fail } from 'k6';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from '../../utils.js';

// Configuration test options
export const options = {
    scenarios: { 
        creation_account: { 
          executor: 'ramping-vus',
          stages: [
            { duration: '20s', target: 10 },   // montée douce
                { duration: '30s', target: 30 },   // montée progressive
                { duration: '30s', target: 50 },   // plateau stable
                { duration: '30s', target: 70 },   // nouvelle montée
                { duration: '30s', target: 90 },   // plateau
                { duration: '30s', target: 100 },  // atteindre 100 VUs
                { duration: '20s', target: 0 }     // ramp down
            ],
          gracefulRampDown: '5s'
        }
    },
    thresholds: {
        http_req_duration: [
            'avg<5000',      // moyenne < 5s, réaliste pour ton backend
            'p(95)<7000',    // p95 < 7s
        ],
        checks: ['rate>0.70'], // 70% des checks doivent réussir
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

    const { plainPassword, confirmationPassword } = randomPasswordPair();
  
    // Define the payload for creating a user account
    const payload = JSON.stringify({
        email: randomEmail(),
        firstName: randomFirstName(),
        lastName: randomLastName(),
        plainPassword: plainPassword,
        confirmationPassword: confirmationPassword
    });
  
  // Set the request headers
  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };

  try {
    // Send the POST request to create a user account
    const res = http.post(`${API_URL}`, payload, params,  { tags: { endpoint: 'register' } });

    // Check the response status and time
    check(res, {
      'is status 201': (r) => r.status === 201,
      'response time < 7000ms (per request)': (r) => r.timings.duration < 7000
    });

    // Log error details
    if (res.status !== 201) {
  console.error(`
  ❌ ERREUR API
  Status: ${res.status}
  URL: ${API_URL}/users
  Durée: ${res.timings.duration}ms
  Réponse: ${res.body?.substring(0, 500)}
  `);
  }
} catch (error) {
  fail(`Request failed: ${error.message}`);
  }
}