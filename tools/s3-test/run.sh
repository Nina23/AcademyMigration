#!/usr/bin/env bash
# One full S3 checklist run on the checked-out branch (see README.md).
#   tools/s3-test/run.sh <label>      e.g. old-code, step-1
# Fresh bucket + fresh akademija_s3test, then checks a-h. Results in storage/app/s3-test/<label>-*.txt.
# Takes ~8 minutes (the temporary URL of check c is tested after its 5-minute expiry).
# Prints no credentials: trace lines are reduced to operation, path, Content-Type, ACL and status.
set -euo pipefail
cd "$(dirname "$0")/../.."
export MSYS_NO_PATHCONV=1

LABEL="${1:?label}"
DC="docker compose -f docker-compose.yml -f docker-compose.s3.yml"
MC_IMAGE=cgr.dev/chainguard/minio-client@sha256:f7fc12ca86b804be7f964260447acc4578fac730778117c8658ad96fab94bf8b
OUT=storage/app/s3-test
mkdir -p "$OUT"

net() { echo "container:$($DC ps -q app-s3)"; }
mc() { docker run --rm --network "$(net)" --env-file .env.s3test "$MC_IMAGE" "$@"; }
checks() { $DC exec -T app-s3 php tools/s3-test/run-checks.php "$@"; }
app_key() { grep -E '^AWS_ACCESS_KEY_ID=' .env.s3test | cut -d= -f2-; }

# Reduce "mc admin trace -v" output to one line per S3 request (no auth headers).
summarize_trace() {
  awk '
    /\[REQUEST s3\./ { match($0, /s3\.[A-Za-z0-9]+/); op = substr($0, RSTART + 3, RLENGTH - 3); host = $1;
                       getline; split($0, a, " "); method = a[2]; path = a[3]; sub(/\?.*/, "", path);
                       if (host ~ /^akademija-s3test\./) { path = "/akademija-s3test" path; style = "virtual-host" } else { style = "path" }
                       ct = "-"; acl = "-"; inreq = 1; next }
    inreq && / Content-Type: / { ct = $3 }
    inreq && / X-Amz-Acl: / { acl = $3 }
    inreq && /\[RESPONSE\]/ { getline; status = $2; inreq = 0;
                              printf "%s %s %s style=%s content-type=%s acl=%s -> %s\n", op, method, path, style, ct, acl, status }
  '
}

errors_since() {
  # First line of each logged exception (no stack trace), masked.
  $DC logs --no-log-prefix --since "$1" app-s3 2>&1 | grep -E '\.(ERROR|CRITICAL)' \
    | sed -E 's/(X-Amz-(Credential|Signature)=)[^& ]+/\1…/g' | cut -c1-400 || true
}

echo "== reset bucket and database"
bash tools/s3-test/setup.sh reset

echo "== main checks (with request trace)"
docker rm -f s3test-trace >/dev/null 2>&1 || true
docker run -d --name s3test-trace --network "$(net)" --env-file .env.s3test "$MC_IMAGE" admin trace -v --call s3 s3test >/dev/null
sleep 2
checks main "$LABEL"
docker logs s3test-trace 2>&1 | summarize_trace > "$OUT/$LABEL-trace.txt"
docker rm -f s3test-trace >/dev/null
echo "trace: $(wc -l < "$OUT/$LABEL-trace.txt") S3 requests -> $OUT/$LABEL-trace.txt"

echo "== g1: MinIO stopped during upload"
since=$(date -u +%Y-%m-%dT%H:%M:%SZ)
$DC stop minio
checks fail "$LABEL" minio-down
errors_since "$since" > "$OUT/$LABEL-fail-minio-down-log.txt"
$DC start minio
for i in $(seq 1 30); do mc ready s3test >/dev/null 2>&1 && break; sleep 1; done
checks fail-verify "$LABEL" minio-down

echo "== g2: app credentials rejected (MinIO user disabled) during upload"
since=$(date -u +%Y-%m-%dT%H:%M:%SZ)
mc admin user disable s3test "$(app_key)" >/dev/null
checks fail "$LABEL" user-disabled
errors_since "$since" > "$OUT/$LABEL-fail-user-disabled-log.txt"
mc admin user enable s3test "$(app_key)" >/dev/null
checks fail-verify "$LABEL" user-disabled

echo "== done: $OUT/$LABEL-*"
