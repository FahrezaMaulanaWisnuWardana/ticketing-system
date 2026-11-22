import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '1m', target: 50 },
        { duration: '3m', target: 200 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<400'],
        http_req_failed: ['rate<0.01'],
    },
};

const BASE = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    const login = http.get(`${BASE}/login`);
    check(login, { 'login page 200': (r) => r.status === 200 });

    const up = http.get(`${BASE}/up`);
    check(up, { 'health 200': (r) => r.status === 200 });

    const search = http.get(`${BASE}/search`);
    check(search, { 'search page 200': (r) => r.status === 200 });

    sleep(1);
}
