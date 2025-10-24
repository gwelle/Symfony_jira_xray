import http from 'k6/http';
import { check, fail } from 'k6';

export const options = {
    scenarios: { 
        activate_multiples_users: { 
            executor: 'per-vu-iterations',
            vus: 5,            // 5 utilisateurs virtuels
            iterations: 1,     // une seule itération chacun
            gracefulStop: '2s'
        }
    },
    thresholds: {
        http_req_duration: [
            'avg<500',   // moyenne < 500ms
            'p(90)<800', // 90% des requêtes < 800ms
            'p(95)<1000' // 95% des requêtes < 1s
        ],
        checks: ['rate>0.95'] // au moins 95% de checks réussis
    }
};

// Tableau de tokens : 1 par utilisateur virtuel
const TOKENS = [
    "561b85d7a2f09d9f0f83744583402fc3d1a97ceaa9d19d7c1da0ee3b92d1650b",
    "eec50cd75e0ddf3fce0e83a11a0325397007dbaf4760c818f3fa85f2f90694e9",
    "561d4677b447eeed828bbe5304a8a86c4ce176e4b0f24604fb146ad71d0e1f79",
    "a91a918a5913424c6c4a84ffbb2543a47acc120c82b609849e3e49ec195ca8f9",
    "c6a8ba504fe03798e1bd6c33516efa6fdb5c181d660457392a3a570916af07b1",
];

export default function () {
    const API_URL = __ENV.API_PLATFORM_URL;
    const vuIndex = __VU - 1; // __VU = index du VU courant (1..5)
    const token = TOKENS[vuIndex];

    if (!API_URL || !token) {
        fail(`❌ Missing API URL or token for VU ${__VU}`);
    }

    const res = http.get(`${API_URL}/activate_account/${token}`, {
        headers: { 'Content-Type': 'application/json' }
    });

    const body = res.json();
    console.log(`VU ${__VU} → ${JSON.stringify(body)}`);

    check(res, {
        'status is 200': (r) => r.status === 200,
        'response success': () => body.success && body.success.includes('Account activated'),
    });
}
