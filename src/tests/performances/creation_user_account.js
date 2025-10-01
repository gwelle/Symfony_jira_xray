import http from 'k6/http';
import { check } from 'k6';


// Configuration test options
export const options = {
    vus: 1,           // 1 utilisateur virtuel
    duration: '5s',  // durée totale du test
    gracefulStop: '1s',
    thresholds: {
      // Seuils de performance pour les requêtes HTTP
      // Moyenne en dessous de 350ms, 
      // 95% en dessous de 800ms, 
      // 99% en dessous de 900ms
        http_req_duration: ['avg<350', 'p(95)<800', 'p(99)<900'],

  }
};

/**
 * Function to create a user account
 * This function sends a POST request to create a new user and checks the response
 */
export default function createUserTest() {

  // Load the API URL from environment variables
  const API_URL = __ENV.API_PLATFORM_URL;
  // Define the payload for creating a user account
  const payload = JSON.stringify({
      email: `user.${Math.random().toString(36).substring(2, 8)}@gmail.com`,
      firstName: 'Alexis',
      lastName: 'Sanchez',
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
    'response time < 800ms (per request)': (r) => r.timings.duration < 800
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


  // Log if the response time exceeds 800ms
  if(res.timings.duration >= 800) {
    console.error(`Response time exceeded: ${res.timings.duration}ms`);
  }
}