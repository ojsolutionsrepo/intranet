/**
 * k6 smoke — Gate 5B scaffold.
 * Run: k6 run perf/k6/smoke.js
 * Full load (100 VUs): k6 run perf/k6/load.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 5,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<3000'],
  },
};

const BASE = __ENV.BASE_URL || 'http://localhost/intranet';

export default function () {
  const res = http.get(`${BASE}/up`);
  check(res, { 'health up': (r) => r.status === 200 });
  sleep(1);
}
