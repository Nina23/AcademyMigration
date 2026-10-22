#!/usr/bin/env bash
# Local S3 test stack (MinIO). See tools/s3-test/README.md.
# Prints no credentials. MinIO and the akademija_s3test DB copy only.
#
#   tools/s3-test/setup.sh env        create .env.s3test with random dummy credentials (once)
#   tools/s3-test/setup.sh up         build app-s3 for the checked-out branch and start app-s3 + MinIO
#   tools/s3-test/setup.sh composer   composer install into the app-s3 vendor volume
#   tools/s3-test/setup.sh bucket     (re)create the bucket, anonymous read, app user + policy
#   tools/s3-test/setup.sh db         (re)create akademija_s3test from backups/akademija_production.sql
#   tools/s3-test/setup.sh reset      bucket + db (fresh state before a run)
#   tools/s3-test/setup.sh down       stop app-s3 and MinIO (volumes are kept)
set -euo pipefail
cd "$(dirname "$0")/../.."
export MSYS_NO_PATHCONV=1  # Git Bash: do not rewrite /paths in docker arguments

DC="docker compose -f docker-compose.yml -f docker-compose.s3.yml"
ENV_FILE=.env.s3test
BUCKET=akademija-s3test
DB=akademija_s3test
DUMP=backups/akademija_production.sql

envval() { grep -E "^$1=" "$ENV_FILE" | head -1 | cut -d= -f2-; }
rnd() { local s; s=$(head -c 1024 /dev/urandom | LC_ALL=C tr -dc 'A-Za-z0-9'); echo "${s:0:$1}"; }
MC_IMAGE=cgr.dev/chainguard/minio-client@sha256:f7fc12ca86b804be7f964260447acc4578fac730778117c8658ad96fab94bf8b
# MinIO client (no shell in the image): one command per run, in the network namespace of app-s3.
mc() { docker run --rm --network "container:$($DC ps -q app-s3)" --env-file "$ENV_FILE" -v "$(pwd -W 2>/dev/null || pwd)/tools/s3-test:/cfg:ro" "$MC_IMAGE" "$@"; }

cmd_env() {
  if [ -e "$ENV_FILE" ]; then echo "$ENV_FILE exists, not changed"; return; fi
  local root_user root_pass app_key app_secret app_key_b64
  root_user="s3root$(rnd 8)"; root_pass="$(rnd 32)"
  app_key="s3app$(rnd 12)"; app_secret="$(rnd 40)"
  app_key_b64="base64:$(head -c 32 /dev/urandom | base64)"
  sed -e "s|<base64 key, e.g. from: php artisan key:generate --show>|$app_key_b64|" \
      -e "s|<dummy app access key>|$app_key|" \
      -e "s|<dummy app secret key>|$app_secret|" \
      -e "s|<dummy root user>|$root_user|g" \
      -e "s|<dummy root password>|$root_pass|g" \
      .env.s3test.example > "$ENV_FILE"
  echo "$ENV_FILE created (values not shown)"
}

cmd_up() {
  [ -f "$ENV_FILE" ] || { echo "run: $0 env"; exit 1; }
  $DC build app-s3
  $DC up -d --force-recreate app-s3 minio
}

cmd_composer() {
  # TypiCMS/Laravel-Sidebar is a VCS repository: clone it over HTTPS (no SSH key in the container).
  $DC exec -T app-s3 git config --global url."https://github.com/".insteadOf "git@github.com:"
  $DC exec -T app-s3 composer config --global github-protocols https
  # After a branch switch, vendor symlinks to pinned copies (packages/*) that the
  # branch does not have are dangling; composer cannot replace them.
  $DC exec -T app-s3 find vendor -mindepth 2 -maxdepth 2 -xtype l -exec unlink {} \;
  $DC exec -T app-s3 composer install --no-interaction --no-progress
}

cmd_bucket() {
  local app_key
  app_key="$(envval AWS_ACCESS_KEY_ID)"
  for i in $(seq 1 30); do mc ready s3test >/dev/null 2>&1 && break; sleep 1; done
  mc rb --force s3test/$BUCKET >/dev/null 2>&1 || true
  mc mb s3test/$BUCKET
  # Production objects are readable because each one is uploaded with ACL public-read.
  # MinIO ignores per-object ACLs, so anonymous read is granted for the bucket instead.
  mc anonymous set download s3test/$BUCKET
  mc admin policy create s3test akademija-app /cfg/app-policy.json >/dev/null 2>&1 \
    || echo "policy akademija-app exists"
  mc admin user add s3test "$app_key" "$(envval AWS_SECRET_ACCESS_KEY)" >/dev/null
  mc admin policy attach s3test akademija-app --user "$app_key" >/dev/null 2>&1 \
    || echo "policy already attached"
  mc admin user enable s3test "$app_key" >/dev/null
  echo "bucket $BUCKET ready (anonymous download, app user with bucket policy)"
}

cmd_db() {
  [ "$(envval DB_DATABASE)" = "$DB" ] || { echo "DB_DATABASE in $ENV_FILE is not $DB"; exit 1; }
  $DC exec -T mariadb mariadb -uroot -proot -e \
    "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON \`$DB\`.* TO 'akademija'@'%';"
  # The dump has no CREATE DATABASE / USE statements: it is loaded into $DB only.
  $DC exec -T mariadb mariadb -uroot -proot "$DB" < "$DUMP"
  echo "database $DB imported from $DUMP"
}

cmd_down() { $DC stop app-s3 minio; }

case "${1:-}" in
  env) cmd_env ;;
  up) cmd_up ;;
  composer) cmd_composer ;;
  bucket) cmd_bucket ;;
  db) cmd_db ;;
  reset) cmd_bucket; cmd_db ;;
  down) cmd_down ;;
  *) sed -n '2,13p' "$0"; exit 1 ;;
esac
