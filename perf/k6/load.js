/**
 * k6 load — 100 concurrent users, p95 < 3s (Gate 5B).
 * Requires authenticated session cookie via __ENV.SESSION_COOKIE for deep pages.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 100,
  duration: '2m',
  thresholds: {
    http_req_duration: ['p(95)<3000'],
  },
};

const BASE = __ENV.BASE_URL || 'http://localhost/intranet';

export default function () {
  const health = http.get(`${BASE}/up`);
  check(health, { 'up': (r) => r.status === 200 });

  const login = http.get(`${BASE}/login`);
  check(login, { 'login page': (r) => r.status === 200 });

  sleep(0.5);
}
