import http from 'k6/http';
import { check } from 'k6';


// Configuration test options
export const options = {
    scenarios: {
        default: {
            executor: 'constant-vus', // type d'exécution : VUs constants
            vus: 10,                   // 10 utilisateurs virtuels
            duration: '5s',            // durée totale du test
            gracefulStop: '1s',        // temps pour que les VUs terminent leurs itérations
        }
    },
    thresholds: {
        http_req_duration: [
          'avg<900',   // moyenne ≈ 870ms
          'p(90)<2600', // 90% des requêtes < 2.6s
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
  // Define the payload for creating a user account
  const payload = JSON.stringify({
      email: `user.${Math.random().toString(36).substring(2, 8)}@gmail.com`,
      firstName: 'Brice',
      lastName: 'Aurtefeuil',
      plainPassword: 'Test1234$$',
      confirmationPassword: 'Test1234$$',
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

  // Log error details if the status is not 201
  if (res.status !== 201) {
    console.error(`Erreur : status=${res.status}`);
    if (res.body) {
        try {
            const json = JSON.parse(res.body);
            console.error(`Body JSON : ${JSON.stringify(json)}`);
        } catch (e) {
            console.error(`Body non JSON : ${res.body}`);
        }
    } else {
        console.error('Body vide ou nul');
    }
  }


  // Log if the response time exceeds 3700ms
  if(res.timings.duration >= 3700) {
    console.error(`Response time exceeded: ${res.timings.duration}ms`);
  }
}