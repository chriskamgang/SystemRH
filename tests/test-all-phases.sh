#!/bin/bash
# ============================================================
# Script de test API — Phases 1 à 4
# Usage: bash tests/test-all-phases.sh
# Prerequis: php artisan serve (port 8000)
#            php artisan db:seed --class=Phase1234TestSeeder
# ============================================================

BASE="http://127.0.0.1:8000/api"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'
PASS=0
FAIL=0

test_endpoint() {
    local method=$1
    local url=$2
    local data=$3
    local label=$4
    local expected_code=${5:-200}

    response=$(curl -s -w "\n%{http_code}" -X "$method" "$url" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        ${data:+-d "$data"})

    http_code=$(echo "$response" | tail -1)
    body=$(echo "$response" | sed '$d')

    if [ "$http_code" == "$expected_code" ]; then
        echo -e "  ${GREEN}OK${NC} [$http_code] $label"
        PASS=$((PASS + 1))
    else
        echo -e "  ${RED}FAIL${NC} [$http_code] $label (attendu: $expected_code)"
        echo "     $body" | head -1
        FAIL=$((FAIL + 1))
    fi
}

echo ""
echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}  TEST API — PHASES 1 à 4${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""

# ===== LOGIN =====
echo -e "${YELLOW}[AUTH] Connexion avec thomas.kamga@insam.cm${NC}"
LOGIN=$(curl -s -X POST "$BASE/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"thomas.kamga@insam.cm","password":"password123","device_id":"test-device-001","device_model":"Test Script","device_os":"macOS"}')

TOKEN=$(echo $LOGIN | python3 -c "import sys,json; print(json.load(sys.stdin).get('token',''))" 2>/dev/null)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}ECHEC LOGIN — verifiez que le serveur tourne et le seeder est execute${NC}"
    echo "$LOGIN"
    exit 1
fi
echo -e "  ${GREEN}OK${NC} Token obtenu: ${TOKEN:0:20}..."
echo ""

# ===== PHASE 1 =====
echo -e "${YELLOW}[PHASE 1] Conges, Justificatifs, Mode hors-ligne${NC}"

test_endpoint GET "$BASE/leaves" "" "Liste mes conges"
test_endpoint GET "$BASE/leaves/balances" "" "Soldes de conges"
RAND_DAY=$((RANDOM % 20 + 1))
test_endpoint POST "$BASE/leaves" '{"type":"annual","start_date":"2026-08-'$(printf '%02d' $RAND_DAY)'","end_date":"2026-08-'$(printf '%02d' $((RAND_DAY+3)))'","days_count":3,"reason":"Test conge"}' "Demander un conge" "201"
test_endpoint GET "$BASE/justifications/absences" "" "Mes absences"
test_endpoint GET "$BASE/justifications/tardiness" "" "Mes retards"
test_endpoint GET "$BASE/justifications/summary" "" "Resume absences/retards"
test_endpoint GET "$BASE/attendance/my-history" "" "Historique pointages"
test_endpoint GET "$BASE/attendance/today" "" "Pointage du jour"
test_endpoint POST "$BASE/attendance/offline-sync" '{"type":"check-in","campus_id":1,"latitude":5.47,"longitude":10.42,"is_offline":true,"offline_timestamp":"'$(date -v-1H +%Y-%m-%dT%H:%M:%S)'"}' "Sync hors-ligne" "201"
echo ""

# ===== PHASE 2 =====
echo -e "${YELLOW}[PHASE 2] Attestations, Profil, Fiches de paie, Messagerie${NC}"

test_endpoint GET "$BASE/certificates" "" "Liste attestations"
test_endpoint POST "$BASE/certificates" '{"type":"salary","purpose":"Test attestation salaire '$RANDOM'"}' "Demander attestation" "201"
test_endpoint GET "$BASE/user/profile" "" "Mon profil complet"
test_endpoint PUT "$BASE/user/profile" '{"address":"Nouveau quartier Bafoussam","emergency_contact_name":"Test Contact","emergency_contact_phone":"+237699887766"}' "Mise a jour profil"
test_endpoint GET "$BASE/user/payslip-history" "" "Historique fiches de paie"
test_endpoint GET "$BASE/messaging/conversations" "" "Mes conversations"
test_endpoint GET "$BASE/messaging/contacts" "" "Liste contacts"
echo ""

# ===== PHASE 3 =====
echo -e "${YELLOW}[PHASE 3] Evaluations, CNPS, Organigramme, Onboarding${NC}"

test_endpoint GET "$BASE/evaluations" "" "Mes evaluations"

# Recuperer l'ID de l'evaluation pending
EVAL_ID=$(curl -s -X GET "$BASE/evaluations" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
for e in data.get('evaluations', []):
    if e['status'] == 'pending':
        print(e['id']); break
" 2>/dev/null)

if [ -n "$EVAL_ID" ]; then
    test_endpoint GET "$BASE/evaluations/$EVAL_ID" "" "Detail evaluation #$EVAL_ID"

    # Recuperer les criteres pour l'auto-eval
    CRITERIA=$(curl -s -X GET "$BASE/evaluations/$EVAL_ID" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
scores = [{'criteria_id': c['id'], 'score': 4} for c in data.get('criteria', [])]
print(json.dumps({'scores': scores, 'comments': 'Bonne annee de travail.'}))" 2>/dev/null)

    if [ -n "$CRITERIA" ]; then
        test_endpoint POST "$BASE/evaluations/$EVAL_ID/self-evaluate" "$CRITERIA" "Soumettre auto-evaluation"
    fi
fi

test_endpoint GET "$BASE/cnps/record" "" "Mon dossier CNPS"
test_endpoint GET "$BASE/cnps/contributions?year=2026" "" "Cotisations CNPS 2026"

test_endpoint GET "$BASE/orgchart/departments" "" "Organigramme departements"
test_endpoint GET "$BASE/orgchart/my-hierarchy" "" "Ma hierarchie"

# Recuperer un dept ID pour les membres
DEPT_ID=$(curl -s -X GET "$BASE/orgchart/departments" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
depts = data.get('departments', [])
if depts: print(depts[0]['id'])" 2>/dev/null)
if [ -n "$DEPT_ID" ]; then
    test_endpoint GET "$BASE/orgchart/departments/$DEPT_ID/members" "" "Membres departement #$DEPT_ID"
fi

test_endpoint GET "$BASE/onboarding" "" "Mes processus onboarding"

# Detail onboarding
OB_ID=$(curl -s -X GET "$BASE/onboarding" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
procs = data.get('processes', [])
if procs: print(procs[0]['id'])" 2>/dev/null)
if [ -n "$OB_ID" ]; then
    test_endpoint GET "$BASE/onboarding/$OB_ID" "" "Detail onboarding #$OB_ID"

    # Completer une tache employee
    TASK_ID=$(curl -s -X GET "$BASE/onboarding/$OB_ID" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
for t in data.get('tasks', []):
    if t['assigned_to'] == 'employee' and t['status'] != 'completed':
        print(t['id']); break" 2>/dev/null)

    if [ -n "$TASK_ID" ]; then
        test_endpoint POST "$BASE/onboarding/$OB_ID/tasks/$TASK_ID/complete" '{"notes":"Fait!"}' "Completer tache onboarding #$TASK_ID"
    fi
fi
echo ""

# ===== PHASE 4 =====
echo -e "${YELLOW}[PHASE 4] Recrutement, Formation, Analytics${NC}"

test_endpoint GET "$BASE/recruitment/postings" "" "Offres d'emploi"

POSTING_ID=$(curl -s -X GET "$BASE/recruitment/postings" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
posts = data.get('postings', [])
if posts: print(posts[0]['id'])" 2>/dev/null)
if [ -n "$POSTING_ID" ]; then
    test_endpoint GET "$BASE/recruitment/postings/$POSTING_ID" "" "Detail offre #$POSTING_ID"
    test_endpoint POST "$BASE/recruitment/postings/$POSTING_ID/apply" '{"candidate_name":"Test Candidat '$RANDOM'","candidate_email":"test.'$RANDOM'@email.com","cover_letter":"Je suis motive."}' "Postuler a l offre" "201"
    test_endpoint GET "$BASE/recruitment/postings/$POSTING_ID/pipeline" "" "Pipeline candidatures"
fi

test_endpoint GET "$BASE/training/catalog" "" "Catalogue formations"
test_endpoint GET "$BASE/training/my-enrollments" "" "Mes formations"

PROG_ID=$(curl -s -X GET "$BASE/training/catalog" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | python3 -c "
import sys,json
data = json.load(sys.stdin)
progs = data.get('programs', [])
for p in progs:
    if not p.get('my_enrollment'):
        print(p['id']); break" 2>/dev/null)
if [ -n "$PROG_ID" ]; then
    test_endpoint GET "$BASE/training/programs/$PROG_ID" "" "Detail formation #$PROG_ID"
    test_endpoint POST "$BASE/training/programs/$PROG_ID/enroll" '{}' "S inscrire a la formation" "201"
fi

test_endpoint GET "$BASE/analytics/dashboard" "" "Dashboard RH"
test_endpoint GET "$BASE/analytics/trends" "" "Tendances mensuelles"
echo ""

# ===== RESUME =====
TOTAL=$((PASS + FAIL))
echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}  RESULTATS: ${GREEN}$PASS OK${NC} / ${RED}$FAIL FAIL${NC} / $TOTAL total"
echo -e "${CYAN}============================================${NC}"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}Tous les tests passent !${NC}"
else
    echo -e "${YELLOW}$FAIL test(s) en echec. Verifiez les erreurs ci-dessus.${NC}"
fi
