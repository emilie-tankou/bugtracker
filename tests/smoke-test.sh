#!/usr/bin/env bash
# Test de bout en bout : parcourt l'application comme un utilisateur (curl + cookies).
# Usage : bash tests/smoke-test.sh [URL_DE_BASE]
#   ex. : php -S 127.0.0.1:8000   puis   bash tests/smoke-test.sh http://127.0.0.1:8000
# Prérequis : la base importée depuis database.sql (compte demo@bugtracker.dev / demo-bugtracker).
# Attention : le test crée puis supprime un ticket et crée un compte utilisateur.

BASE="${1:-http://127.0.0.1:8000}"
EMAIL="demo@bugtracker.dev"
PASS="demo-bugtracker"
JAR="$(mktemp)"
FAILS=0

ok()   { echo "  ok    - $1"; }
fail() { echo "  ECHEC - $1"; FAILS=$((FAILS + 1)); }
check() { if [ "$2" = "true" ]; then ok "$1"; else fail "$1"; fi; }
has()  { case "$1" in *"$2"*) echo true ;; *) echo false ;; esac; }
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
csrf_from() { grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//; s/"//'; }

echo "== Sans connexion =="
H=$(curl -s -D - -o /dev/null "$BASE/dashboard.php")
check "dashboard.php sans session -> redirection vers login.php" "$(has "$H" 'login.php')"
R=$(curl -s -w '\n%{http_code}' -X POST "$BASE/api.php" -d 'action=delete_ticket&ticket_id=1')
check "api.php sans session -> 401 en JSON" "$(has "$R" '401')"
check "api.php ne supprime rien sans session" "$(has "$R" 'Authentication required')"

echo "== Connexion =="
PAGE=$(curl -s -c "$JAR" -b "$JAR" "$BASE/login.php")
TOKEN=$(echo "$PAGE" | csrf_from)
check "la page de login contient un jeton CSRF" "$([ -n "$TOKEN" ] && echo true || echo false)"

BAD=$(curl -s -c "$JAR" -b "$JAR" "$BASE/login.php" --data-urlencode "email=$EMAIL" --data-urlencode "password=mauvais" --data-urlencode "csrf_token=$TOKEN")
check "mauvais mot de passe -> refusé" "$(has "$BAD" 'Invalid email or password')"

NOCSRF=$(curl -s -c "$JAR" -b "$JAR" "$BASE/login.php" --data-urlencode "email=$EMAIL" --data-urlencode "password=$PASS")
check "connexion sans jeton CSRF -> refusée" "$(has "$NOCSRF" 'session has expired')"

SID_BEFORE=$(grep -o 'PHPSESSID.*' "$JAR" | awk '{print $2}')
H=$(curl -s -D - -o /dev/null -c "$JAR" -b "$JAR" "$BASE/login.php" --data-urlencode "email=$EMAIL" --data-urlencode "password=$PASS" --data-urlencode "csrf_token=$TOKEN")
check "bons identifiants -> redirection vers dashboard.php" "$(has "$H" 'dashboard.php')"
SID_AFTER=$(grep -o 'PHPSESSID.*' "$JAR" | awk '{print $2}')
check "l'identifiant de session change après la connexion" "$([ "$SID_BEFORE" != "$SID_AFTER" ] && echo true || echo false)"

echo "== Tableau de bord =="
DASH=$(curl -s -c "$JAR" -b "$JAR" "$BASE/dashboard.php")
check "affiche « Welcome, Demo User »" "$(has "$DASH" 'Welcome, Demo User')"
N=$(echo "$DASH" | grep -c 'class="ticket-title"')
check "affiche les 10 tickets d'exemple (trouvé : $N)" "$([ "$N" = "10" ] && echo true || echo false)"
check "le fichier js/dashboard.js est bien servi" "$([ "$(code "$BASE/js/dashboard.js")" = "200" ] && echo true || echo false)"
INJ=$(curl -s -c "$JAR" -b "$JAR" -G "$BASE/dashboard.php" --data-urlencode "filter=' OR 1=1 -- ")
case "$INJ" in *SQLSTATE*|*"Fatal error"*) fail "injection SQL : erreur SQL visible" ;; *) ok "injection SQL dans le filtre : aucune erreur visible" ;; esac

echo "== Création et édition d'un ticket =="
FORM=$(curl -s -c "$JAR" -b "$JAR" "$BASE/form.php")
TOKEN2=$(echo "$FORM" | csrf_from)
TITLE='Test <script>alert(1)</script>'
H=$(curl -s -D - -o /dev/null -c "$JAR" -b "$JAR" "$BASE/form.php" --data-urlencode "title=$TITLE" -d 'category_id=1&priority=2&status=0&assigned_to=' --data-urlencode "csrf_token=$TOKEN2")
check "création d'un ticket -> redirection vers dashboard.php" "$(has "$H" 'dashboard.php')"
DASH=$(curl -s -c "$JAR" -b "$JAR" "$BASE/dashboard.php")
check "le ticket apparaît (11 tickets)" "$([ "$(echo "$DASH" | grep -c 'class="ticket-title"')" = "11" ] && echo true || echo false)"
check "le HTML du titre est échappé (pas de XSS)" "$(has "$DASH" '&lt;script&gt;alert(1)&lt;/script&gt;')"
case "$DASH" in *"<script>alert(1)"*) fail "XSS : le script est resté tel quel" ;; *) ok "aucun <script> injecté dans la page" ;; esac

H=$(curl -s -D - -o /dev/null -c "$JAR" -b "$JAR" "$BASE/form.php" -d 'title=x&category_id=1&priority=9&status=0' --data-urlencode "csrf_token=$TOKEN2")
case "$H" in *"302"*) fail "priorité invalide acceptée" ;; *) ok "priorité invalide (9) refusée" ;; esac
EDIT=$(curl -s -c "$JAR" -b "$JAR" "$BASE/form.php?id=6")
check "édition : la priorité « Low » (0) est bien présélectionnée" "$(has "$EDIT" '<option value="0" selected')"

echo "== API (changement de statut et suppression) =="
TOK=$(echo "$DASH" | grep -o 'data-csrf="[^"]*"' | head -1 | sed 's/data-csrf="//; s/"//')
NEWID=$(echo "$DASH" | grep -o 'form.php?id=[0-9]*' | sed 's/form.php?id=//' | sort -n | tail -1)
check "api sans jeton CSRF -> 403" "$([ "$(code -b "$JAR" -X POST "$BASE/api.php" -d "action=delete_ticket&ticket_id=$NEWID")" = "403" ] && echo true || echo false)"
check "statut invalide (5) -> 400" "$([ "$(code -b "$JAR" -X POST "$BASE/api.php" -d "action=update_status&ticket_id=$NEWID&status=5&csrf_token=$TOK")" = "400" ] && echo true || echo false)"
R=$(curl -s -b "$JAR" -X POST "$BASE/api.php" -d "action=update_status&ticket_id=$NEWID&status=2&csrf_token=$TOK")
check "changement de statut valide -> succès" "$(has "$R" '"success":true')"
check "ticket inexistant -> 404" "$([ "$(code -b "$JAR" -X POST "$BASE/api.php" -d "action=delete_ticket&ticket_id=999999&csrf_token=$TOK")" = "404" ] && echo true || echo false)"
R=$(curl -s -b "$JAR" -X POST "$BASE/api.php" -d "action=delete_ticket&ticket_id=$NEWID&csrf_token=$TOK")
check "suppression du ticket de test -> succès" "$(has "$R" '"success":true')"

echo "== Inscription =="
curl -s -o /dev/null -c "$JAR" -b "$JAR" "$BASE/logout.php"
PAGE=$(curl -s -c "$JAR" -b "$JAR" "$BASE/subscribe.php")
T3=$(echo "$PAGE" | csrf_from)
SHORT=$(curl -s -c "$JAR" -b "$JAR" "$BASE/subscribe.php" -d 'name=Test&email=short@test.dev&password=123&password_confirm=123' --data-urlencode "csrf_token=$T3")
check "mot de passe trop court -> refusé" "$(has "$SHORT" 'at least 8 characters')"
UNIQ="smoke$(date +%s)@test.dev"
OKR=$(curl -s -c "$JAR" -b "$JAR" "$BASE/subscribe.php" -d "name=Smoke Test&email=$UNIQ&password=longenough1&password_confirm=longenough1" --data-urlencode "csrf_token=$T3")
check "inscription valide -> succès" "$(has "$OKR" 'Registration successful')"
DUP=$(curl -s -c "$JAR" -b "$JAR" "$BASE/subscribe.php" -d "name=Smoke Test&email=$UNIQ&password=longenough1&password_confirm=longenough1" --data-urlencode "csrf_token=$T3")
check "même email deux fois -> refusé" "$(has "$DUP" 'already registered')"

echo "== Déconnexion =="
curl -s -o /dev/null -c "$JAR" -b "$JAR" "$BASE/logout.php"
H=$(curl -s -D - -o /dev/null -b "$JAR" "$BASE/dashboard.php")
check "après déconnexion, dashboard.php redirige vers login.php" "$(has "$H" 'login.php')"

rm -f "$JAR"
echo
if [ "$FAILS" = "0" ]; then echo "Tous les tests passent."; else echo "$FAILS test(s) en échec."; exit 1; fi