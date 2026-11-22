# Load test

Targets: p95 page < 400 ms at 200 concurrent users, board < 1.5 s with 500 tickets, search < 300 ms.

k6 script: `tests/load/load.js`. Run with `k6 run -e BASE_URL=https://staging.example.com tests/load/load.js` (k6 is not installed in the build environment, so results below are pending). Record results here before go-live:

| Scenario | Result | Date |
|---|---|---|
| login | pending | — |
| board load | pending | — |
| ticket create | pending | — |
| search | pending | — |
