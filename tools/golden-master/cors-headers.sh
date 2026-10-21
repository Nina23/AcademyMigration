#!/bin/sh
# CORS / response-header reference for a few /api requests (read-only).
# Usage: GM_API_TOKEN=... sh tools/golden-master/cors-headers.sh <label>
# Output: storage/golden-master/cors-<label>.txt (volatile headers removed).
# The token is never written: each block is labelled with a description only.
set -e
BASE=${BASE:-http://localhost:8080}
OUT="storage/golden-master/cors-$1.txt"
LIST="/api/news?sort=-date&fields%5Bnews%5D=id,image_id,date,status,title&include=image&append=thumb&locale=sr&page=1&per_page=1"
: > "$OUT"
req() {
    label=$1
    shift
    echo "### $label" >> "$OUT"
    curl -s -o /dev/null -D - "$@" \
        | tr -d '\r' \
        | grep -viE '^(date|set-cookie|phpdebugbar[a-z-]*|x-debugbar[a-z-]*|content-length|x-ratelimit-remaining|x-powered-by|server):' \
        | sort >> "$OUT"
    echo >> "$OUT"
}
AUTH="Authorization: Bearer $GM_API_TOKEN"
req "GET $LIST (token, no Origin)" -H "Accept: application/json" -H "$AUTH" "$BASE$LIST"
req "GET $LIST (token, Origin)" -H "Accept: application/json" -H "$AUTH" -H "Origin: https://example.org" "$BASE$LIST"
req "OPTIONS /api/news preflight (Origin, GET, authorization)" -X OPTIONS -H "Origin: https://example.org" -H "Access-Control-Request-Method: GET" -H "Access-Control-Request-Headers: authorization" "$BASE/api/news"
req "GET $LIST (no token, Origin) -> 401" -H "Accept: application/json" -H "Origin: https://example.org" "$BASE$LIST"
req "GET /api/tags-list (no token, Origin)" -H "Accept: application/json" -H "Origin: https://example.org" "$BASE/api/tags-list"
req "GET / (public page, Origin)" -H "Origin: https://example.org" "$BASE/"
echo "written $OUT"
