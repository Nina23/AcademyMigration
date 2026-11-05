#!/usr/bin/env bash
# TEST SERVER ONLY — run on the server host as root, from /opt/akademija-test, once after the database
# import and before anyone else opens the site (docker/test-server/README.md, "Prepare the copy").
#
#   bash /opt/akademija-test/tools/test-server/prepare-test-copy.sh <admin e-mail> <first name> <last name>
#
# - asks for the new test superadmin's password twice, without showing it (not in the shell history);
# - runs tools/test-server/prepare-test-copy.php inside this project's app container (as www-data);
# - writes the credentials of the existing users (email, test password) straight into
#   /root/akademija-test-credentials-<date>.tsv, mode 600, readable by root only: outside the app folder,
#   the image, git and every Docker volume. Nothing with a password is printed to the terminal or logged.
set -uo pipefail

[ "$(id -u)" = 0 ] || { echo "Run as root."; exit 1; }
[ $# -eq 3 ] || { echo "Usage: $0 <admin e-mail> <first name> <last name>"; exit 1; }
# Project and folder: the README's values (override only for a local rehearsal next to another stack).
PROJECT=${AKADEMIJA_TEST_PROJECT:-akademija-test}
DIR=${AKADEMIJA_TEST_DIR:-/opt/akademija-test}
cd "$DIR" || exit 1
ENVF="$DIR/.env.test-server"
[ -f "$ENVF" ] || { echo "Missing $ENVF"; exit 1; }
DBNAME=$(grep -E '^DB_DATABASE=' "$ENVF" | tail -1 | cut -d= -f2- | tr -d "'\"")
[ -n "$DBNAME" ] || { echo "DB_DATABASE is not set in $ENVF"; exit 1; }

OUT="/root/akademija-test-credentials-$(date +%Y-%m-%d-%H%M%S).tsv"
[ -e "$OUT" ] && { echo "$OUT already exists. Nothing changed."; exit 1; }

read -rsp "Password for the new test superadmin $1 (not shown, at least 12 characters): " PW; echo
read -rsp "Repeat it: " PW2; echo
[ "$PW" = "$PW2" ] || { unset PW PW2; echo "The passwords differ. Nothing changed."; exit 1; }
unset PW2

# The credentials go to a new temporary file (mode 600) and get their final name only on success:
# an existing credentials file is never overwritten, and a refused or failed run leaves no file.
umask 077
TMP=$(mktemp /root/.akademija-test-credentials.XXXXXX) || exit 1
printf '%s\n' "$PW" | docker compose --project-name "$PROJECT" \
    --file "$DIR/docker-compose.test-server.yml" --env-file "$ENVF" \
    exec -T -u www-data -e TEST_COPY_CONFIRM=yes app \
    php tools/test-server/prepare-test-copy.php \
    --database="$DBNAME" --admin-email="$1" --admin-first-name="$2" --admin-last-name="$3" \
    > "$TMP"
STATUS=$?
unset PW

if [ $STATUS -eq 0 ] || [ $STATUS -eq 3 ]; then
    # 0 = done; 3 = done, but another table's checksum changed (see the message above)
    chmod 600 "$TMP" && mv -n "$TMP" "$OUT"
    echo "Credentials of $(wc -l < "$OUT") existing users written to $OUT (mode 600, root only)."
    echo "Next: php artisan cache:clear in the app container (README section 6)."
else
    LINES=$(wc -l < "$TMP")
    if [ "$LINES" -eq 0 ]; then
        rm -f "$TMP"
        echo "The preparation did not run or was rolled back (exit $STATUS, see the message above). No credentials file was written."
    else
        echo "Unexpected: exit $STATUS but $TMP has $LINES lines. Kept as it is (mode 600); check before using it."
    fi
fi
exit $STATUS
