import http from 'k6/http';
import { check } from 'k6';
import { fail } from 'k6';

export const options = {
    scenarios: {
        activate_account: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            gracefulStop: '200ms',
        },
    },
    thresholds: {
        http_req_duration: [
          'avg<80',   // moyenne < 80ms
          'p(90)<100', // 90% des requêtes < 100ms
          'p(95)<150', // 95% des requêtes < 150ms
          'p(99)<200'  // 99% des requêtes < 200ms

       ]
    }
};

/**
 * Function to retrieve a token for a user by ID
 * This function sends a GET request to retrieve the activation token for a specific user and checks the response
 */
export default function activateAccountUserTest() {

    // Load the API URL from environment variables
    const API_URL = __ENV.API_PLATFORM_URL;
    const token_success = __ENV.TOKEN_SUCCESS;

    if (API_URL === '' || token_success === '') {
      fail("Missing API_PLATFORM_URL or TOKEN environment variable");
    }
   
    const res = http.get(`${API_URL}/activate_account/${token_success}`, {
        headers: {'Content-Type': 'application/json'}
    });
    const body = res.json();
    console.log(`Iteration response: ${JSON.stringify(body)}`);

    check(res, {
        'status is 200': (r) => r.status === 200,
        'response is either success OR already_activated': () => {
            return (
                body.success && body.success.includes('Compte activé') ||
                body.info && body.info.includes('already_activated')
            );
        }
    });
    
}
