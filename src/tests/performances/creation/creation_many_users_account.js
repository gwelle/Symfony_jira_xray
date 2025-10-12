import http from 'k6/http';
import { check, fail } from 'k6';
import { randomFirstName, randomLastName, randomEmail, randomPasswordPair } from '../../utils.js';

// Configuration test options
export const options = {
    scenarios: { 
        stress_batch_heavy: { 
          executor: 'ramping-vus',
          stages: [
            { duration: '1m', target: 100 },  // échauffement
            { duration: '2m', target: 200 },  // montée progressive
            { duration: '3m', target: 400 },  // charge stable
            { duration: '3m', target: 600 },  // pic de charge
            { duration: '2m', target: 300 },  // redescente
            { duration: '1m', target: 0 },    // retour au calme
          ],
        gracefulRampDown: '10s',

      },
      
  },
    
  thresholds: {
      http_req_duration: ['avg<1000', 'p(95)<2000'],
      checks: ['rate>0.95'],
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
    const res = http.post(`${API_URL}`, payload, params, { tags: { endpoint: 'register' }, timeout: '120s' });

    // Check the response status and time
    check(res, {
      'is status 201': (r) => r.status === 201,
      'response time < 7000ms (per request)': (r) => r.timings.duration < 7000
    });

    // Log error details
    if (res.status !== 201) {
      fail(`❌ Error: status=${res.status}, body=${res.body}`);
    }
  } 
  catch (error) {
    fail(`Request failed: ${error.message}`);
  }
}