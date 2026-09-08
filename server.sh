#!/usr/bin/env bash
#
# Estuaire RH + INSAM BUS — lancement de l'environnement de développement.
#
#   ./server.sh              démarre tous les services et suit les logs
#   ./server.sh --fresh      remet la base à zéro (migrate:fresh --seed) avant de démarrer
#   ./server.sh --no-cache   saute la mise en cache config/routes (rechargement à chaud)
#
# Ctrl+C arrête proprement l'ensemble des processus.
#
# Les deux espaces partagent ce serveur : l'API d'Estuaire RH répond sur
# /api, celle du transport sur /api/bus, et les deux puisent dans la même
# base MySQL.

set -uo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")" || exit 1

# --- Paramètres ---------------------------------------------------------------

HOTE="${INSAM_HOST:-0.0.0.0}"
PORT="${INSAM_PORT:-8000}"
FILES_ATTENTE="${INSAM_QUEUES:-default}"

# Le composer.lock du projet exige PHP 8.4 ; la commande `php` du système
# peut pointer ailleurs, on vise donc la version voulue quand elle existe.
PHP="${INSAM_PHP:-}"
if [[ -z "$PHP" ]]; then
    for candidat in /usr/local/opt/php@8.4/bin/php /opt/homebrew/opt/php@8.4/bin/php php; do
        if command -v "$candidat" >/dev/null 2>&1; then PHP="$candidat"; break; fi
    done
fi

DOSSIER_LOGS="storage/logs/dev"
FICHIER_LARAVEL="storage/logs/laravel.log"

FRESH=0
CACHE=1

for argument in "$@"; do
    case "$argument" in
        --fresh)    FRESH=1 ;;
        --no-cache) CACHE=0 ;;
        -h|--help)
            sed -n '3,14p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
            exit 0
            ;;
        *)
            echo "Option inconnue : $argument (voir --help)" >&2
            exit 1
            ;;
    esac
done

# --- Couleurs (désactivées si la sortie n'est pas un terminal) ----------------

if [[ -t 1 ]]; then
    ROUGE=$'\033[31m'; VERT=$'\033[32m'; JAUNE=$'\033[33m'
    BLEU=$'\033[34m'; MAGENTA=$'\033[35m'; CYAN=$'\033[36m'
    GRIS=$'\033[90m'; GRAS=$'\033[1m'; NEUTRE=$'\033[0m'
else
    ROUGE=''; VERT=''; JAUNE=''; BLEU=''; MAGENTA=''; CYAN=''
    GRIS=''; GRAS=''; NEUTRE=''
fi

info()    { printf '%s▸%s %s\n' "$CYAN" "$NEUTRE" "$1"; }
succes()  { printf '%s✓%s %s\n' "$VERT" "$NEUTRE" "$1"; }
alerte()  { printf '%s!%s %s\n' "$JAUNE" "$NEUTRE" "$1"; }
erreur()  { printf '%s✗%s %s\n' "$ROUGE" "$NEUTRE" "$1" >&2; }

# --- Arrêt propre -------------------------------------------------------------

PIDS=()
NOMS_PIDS=()
ARRET_EN_COURS=0
VEILLE=""

arreter() {
    # Ctrl+C pendant l'arrêt ne doit pas relancer la procédure.
    [[ $ARRET_EN_COURS -eq 1 ]] && return
    ARRET_EN_COURS=1

    printf '\n'
    info "Arrêt des services…"

    # Le sleep de la boucle de veille survivrait sinon quelques secondes.
    [[ -n "$VEILLE" ]] && kill -TERM "$VEILLE" 2>/dev/null

    # On termine chaque enfant direct ET sa descendance : `php artisan serve`
    # délègue le travail à un serveur PHP intégré qui survivrait à un simple kill
    # sur son parent, et garderait le port occupé.
    #
    # Les services sont listés qu'ils soient encore vivants ou non : un vrai Ctrl+C
    # frappe tout le groupe de processus, donc certains sont déjà tombés en
    # arrivant ici — les taire donnerait un récapitulatif trompeur.
    for indice in "${!PIDS[@]}"; do
        local pid="${PIDS[$indice]}"
        local nom="${NOMS_PIDS[$indice]}"

        for enfant in $(pgrep -P "$pid" 2>/dev/null); do
            kill -TERM "$enfant" 2>/dev/null
        done
        kill -TERM "$pid" 2>/dev/null

        [[ "$nom" == *"(logs)"* ]] && continue
        printf '  %s·%s %s\n' "$GRIS" "$NEUTRE" "$nom"
    done

    # Laisse 3 secondes aux processus pour se fermer d'eux-mêmes.
    local attente=0
    while [[ $attente -lt 30 ]]; do
        local restants=0
        for pid in "${PIDS[@]}"; do
            kill -0 "$pid" 2>/dev/null && restants=1
        done
        [[ $restants -eq 0 ]] && break
        sleep 0.1
        attente=$((attente + 1))
    done

    # Ce qui résiste est terminé sans ménagement.
    for pid in "${PIDS[@]}"; do
        kill -0 "$pid" 2>/dev/null && kill -KILL "$pid" 2>/dev/null
    done

    # Filet de sécurité : tout serveur PHP encore accroché à notre port.
    # Le serveur intégré s'annonce sous la forme « php -S 0.0.0.0:8000 ».
    local occupant
    occupant="$(lsof -nP -iTCP:"${PORT}" -sTCP:LISTEN -t 2>/dev/null)"
    if [[ -n "$occupant" ]]; then
        kill -KILL $occupant 2>/dev/null
    fi

    succes "Tous les services sont arrêtés."
    exit 0
}

# Le trap note l'intention d'arrêt AVANT de lancer la procédure : un Ctrl+C
# frappe tout le groupe de processus, et la boucle de veille verrait sinon les
# services tomber « tout seuls » et annoncerait une panne au lieu d'un arrêt.
demande_arret() {
    ARRET_DEMANDE=1
    arreter
}

ARRET_DEMANDE=0
trap demande_arret INT TERM

# --- Vérifications préalables -------------------------------------------------

printf '\n%s%sEstuaire RH%s + %s%sINSAM BUS%s — environnement de développement\n\n' \
    "$GRAS" "$BLEU" "$NEUTRE" "$GRAS" "$JAUNE" "$NEUTRE"

[[ -n "$PHP" ]] || { erreur "PHP est introuvable."; exit 1; }
[[ -f artisan ]] || { erreur "artisan introuvable : lancez le script depuis la racine du projet."; exit 1; }

# Le lock du projet exige PHP 8.4 : une version antérieure fait échouer
# composer et, plus loin, des appels de la librairie standard.
VERSION_PHP="$("$PHP" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null)"
if [[ "$(printf '%s\n8.4\n' "$VERSION_PHP" | sort -V | head -n1)" != "8.4" ]]; then
    erreur "PHP 8.4 est requis (version détectée : ${VERSION_PHP:-inconnue})."
    printf '    %sInstallez-le (brew install php@8.4) ou pointez-le :%s\n' "$GRIS" "$NEUTRE"
    printf '      INSAM_PHP=/usr/local/opt/php@8.4/bin/php ./server.sh\n\n'
    exit 1
fi

if [[ ! -f .env ]]; then
    erreur "Fichier .env manquant. Copiez .env.example puis lancez php artisan key:generate."
    exit 1
fi

[[ -d vendor ]] || { erreur "Dossier vendor absent. Lancez composer install."; exit 1; }

PORT_REVERB="$(grep -E '^REVERB_PORT=' .env | cut -d= -f2 | tr -d '[:space:]')"
PORT_REVERB="${PORT_REVERB:-8080}"

# Les deux ports doivent être libres : un port occupé fait échouer le service
# concerné quelques secondes après le démarrage, ce qui est bien plus déroutant
# qu'un refus immédiat.
verifier_port() {
    local port="$1" service="$2" astuce="$3"

    if lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; then
        erreur "Le port ${port} (${service}) est déjà utilisé."
        lsof -nP -iTCP:"$port" -sTCP:LISTEN | tail -n +2 | awk '{printf "    %s (PID %s)\n", $1, $2}'
        printf '    %s%s%s\n\n' "$GRIS" "$astuce" "$NEUTRE"
        exit 1
    fi
}

verifier_port "$PORT" "HTTP" "Libérez-le, ou relancez avec INSAM_PORT=8002 ./server.sh"
verifier_port "$PORT_REVERB" "Reverb" "Ajustez REVERB_PORT dans le .env, ou libérez le port."

# La connexion à la base conditionne tout le reste. Quand elle échoue, le message
# générique « vérifiez vos réglages » envoie souvent chercher au mauvais endroit :
# on regarde donc ce qui cloche réellement avant de rendre la main.
diagnostiquer_base() {
    local hote port base service

    hote="$(grep -E '^DB_HOST=' .env | cut -d= -f2 | tr -d '[:space:]')"
    port="$(grep -E '^DB_PORT=' .env | cut -d= -f2 | tr -d '[:space:]')"
    base="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '[:space:]')"
    hote="${hote:-127.0.0.1}"
    port="${port:-3306}"

    # Un serveur qui écoute déjà : le problème vient des identifiants, du nom de
    # la base ou des droits — pas du service.
    if lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; then
        printf '    %sUn serveur écoute bien sur %s:%s.%s\n' "$GRIS" "$hote" "$port" "$NEUTRE"

        # Cause la plus fréquente ici : la base n'a jamais été créée.
        if command -v mysql >/dev/null 2>&1 && [[ -n "$base" ]]; then
            if ! mysql -u root -e "USE \`$base\`" >/dev/null 2>&1; then
                printf '    %sLa base « %s » est introuvable.%s\n' "$GRIS" "$base" "$NEUTRE"
                printf '\n    %sPour la créer :%s\n' "$GRAS" "$NEUTRE"
                printf '      mysql -u root -e "CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"\n' "$base"
                printf '      %s ./server.sh --fresh\n' "$PHP"
                return
            fi
        fi

        printf '    %sVérifiez DB_DATABASE, DB_USERNAME et DB_PASSWORD du .env.%s\n' "$GRIS" "$NEUTRE"
        printf '    %sDétail de l'"'"'erreur :%s\n' "$GRIS" "$NEUTRE"
        "$PHP" artisan db:show 2>&1 | grep -iE 'SQLSTATE|denied|Unknown database|Connection refused' | head -3 \
            | sed "s/^/      /"
        return
    fi

    printf '    %sAucun serveur n'"'"'écoute sur %s:%s.%s\n' "$GRIS" "$hote" "$port" "$NEUTRE"

    command -v brew >/dev/null 2>&1 || {
        printf '    %sDémarrez votre serveur MySQL, puis relancez ce script.%s\n' "$GRIS" "$NEUTRE"
        return
    }

    # On retient le service réellement enrôlé — « started » ou en « error » :
    # les autres sont des reliquats, et pointer vers eux enverrait sur une
    # fausse piste.
    service="$(brew services list 2>/dev/null \
        | awk '$1 ~ /^(mysql|mariadb)/ && $2 != "none" { print $1; exit }')"

    [[ -z "$service" ]] && service="$(brew services list 2>/dev/null \
        | awk '$1 ~ /^(mysql|mariadb)/ { print $1; exit }')"

    [[ -z "$service" ]] && {
        printf '    %sMySQL ne semble pas installé via Homebrew.%s\n' "$GRIS" "$NEUTRE"
        return
    }

    printf '    %sMySQL est arrêté.%s\n' "$GRIS" "$NEUTRE"
    printf '\n    %sPour le démarrer :%s\n' "$GRAS" "$NEUTRE"
    printf '      brew services start %s\n' "$service"
}

if ! "$PHP" artisan db:show --json >/dev/null 2>&1; then
    erreur "Connexion à la base impossible."
    diagnostiquer_base
    printf '\n'
    exit 1
fi
succes "Base de données accessible"

# --- Préparation --------------------------------------------------------------

mkdir -p "$DOSSIER_LOGS"
: > "$FICHIER_LARAVEL"

if [[ $FRESH -eq 1 ]]; then
    alerte "Réinitialisation complète de la base (--fresh)"
    # Les jetons de session vivent en base : les applications deja
    # connectees devront se reconnecter apres cette operation.
    alerte "Les sessions mobiles ouvertes seront invalidées"
    if ! "$PHP" artisan migrate:fresh --seed --no-interaction; then
        erreur "La réinitialisation a échoué."
        exit 1
    fi

    # Emploi du temps d'essai : sans lui, l'etudiant du transport ouvre un
    # ecran vide, faute d'unites d'enseignement a lui montrer.
    "$PHP" artisan db:seed --class=EmploiDuTempsEtudiantSeeder --no-interaction >/dev/null 2>&1 \
        && succes "Emploi du temps d'essai posé" \
        || alerte "Emploi du temps non posé (voir EmploiDuTempsEtudiantSeeder)"

    succes "Base réinitialisée et peuplée"
else
    # Une migration en attente provoquerait des erreurs difficiles à diagnostiquer.
    if "$PHP" artisan migrate:status 2>/dev/null | grep -q 'Pending'; then
        alerte "Des migrations sont en attente — application automatique"
        "$PHP" artisan migrate --no-interaction --force >/dev/null 2>&1 \
            && succes "Migrations appliquées" \
            || erreur "Échec des migrations (poursuite du démarrage)"
    fi
fi

# Donnees de reference d'Estuaire RH : les quatre roles, leurs permissions
# et un administrateur capable d'ouvrir le back-office.
#
# La fusion avec INSAM BUS a laisse une base ou seul le role `employe`
# subsistait, et ou l'administrateur pointait dessus : la connexion au
# back-office repartait vers le formulaire sans dire pourquoi. Le seeder
# est rejouable, on le passe donc a chaque demarrage plutot que d'attendre
# du developpeur qu'il devine la manoeuvre.
"$PHP" artisan db:seed --class=EnvironnementRhSeeder --no-interaction >/dev/null 2>&1 \
    && succes "Rôles et administrateur d'Estuaire RH en place" \
    || alerte "Données de référence RH non posées (voir EnvironnementRhSeeder)"

info "Nettoyage des caches"
"$PHP" artisan cache:clear  >/dev/null 2>&1
"$PHP" artisan view:clear   >/dev/null 2>&1
"$PHP" artisan route:clear  >/dev/null 2>&1
"$PHP" artisan config:clear >/dev/null 2>&1

if [[ $CACHE -eq 1 ]]; then
    "$PHP" artisan config:cache >/dev/null 2>&1
    "$PHP" artisan route:cache  >/dev/null 2>&1
    succes "Configuration et routes mises en cache"
else
    alerte "Caches désactivés (--no-cache) : les modifications sont prises à chaud"
fi

# --- Démarrage des services ---------------------------------------------------

# Préfixe chaque ligne de log avec le nom coloré du service qui l'émet.
suivre() {
    local nom="$1" couleur="$2" fichier="$3"
    tail -n 0 -F "$fichier" 2>/dev/null | while IFS= read -r ligne; do
        printf '%s%-9s%s │ %s\n' "$couleur" "$nom" "$NEUTRE" "$ligne"
    done
}

demarrer() {
    local nom="$1" couleur="$2" fichier="$3"
    shift 3

    : > "$fichier"
    "$@" > "$fichier" 2>&1 &
    local pid=$!

    PIDS+=("$pid")
    NOMS_PIDS+=("$nom")

    suivre "$nom" "$couleur" "$fichier" &
    PIDS+=("$!")
    NOMS_PIDS+=("$nom (logs)")

    printf '  %s%-9s%s démarré %s(PID %s)%s\n' "$couleur" "$nom" "$NEUTRE" "$GRIS" "$pid" "$NEUTRE"
}

printf '\n'
info "Démarrage des services"

demarrer "http"     "$VERT"    "$DOSSIER_LOGS/http.log" \
    "$PHP" artisan serve --host="$HOTE" --port="$PORT"

demarrer "reverb"   "$MAGENTA" "$DOSSIER_LOGS/reverb.log" \
    "$PHP" artisan reverb:start

demarrer "queue"    "$BLEU"    "$DOSSIER_LOGS/queue.log" \
    "$PHP" artisan queue:work --queue="$FILES_ATTENTE" --tries=3 --timeout=90

demarrer "schedule" "$JAUNE"   "$DOSSIER_LOGS/schedule.log" \
    "$PHP" artisan schedule:work

# Boite mail de developpement : les codes de connexion y arrivent au lieu
# d'etre reellement envoyes. Absente en production, ou un vrai SMTP prend
# le relais via MAIL_MAILER.
if command -v mailpit > /dev/null 2>&1; then
    demarrer "mailpit"  "$CYAN"    "$DOSSIER_LOGS/mailpit.log" \
        mailpit --smtp "127.0.0.1:1025" --listen "127.0.0.1:8025"
fi

# Le log applicatif n'appartient à aucun service : il est suivi à part.
suivre "laravel" "$CYAN" "$FICHIER_LARAVEL" &
PIDS+=("$!")
NOMS_PIDS+=("laravel (logs)")

# --- Récapitulatif ------------------------------------------------------------

if [[ "$HOTE" == "0.0.0.0" ]]; then
    AFFICHAGE_HOTE="localhost"
    IP_LOCALE="$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || true)"
else
    AFFICHAGE_HOTE="$HOTE"
    IP_LOCALE=""
fi

sleep 2

printf '\n%s%s─────────────────────────────────────────────%s\n' "$GRAS" "$GRIS" "$NEUTRE"
printf '  %sAdmin RH%s      http://%s:%s/admin/dashboard\n' "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE" "$PORT"
printf '  %sAdmin BUS%s     http://%s:%s/admin/bus\n' "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE" "$PORT"
printf '  %sAPI RH%s        http://%s:%s/api\n'        "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE" "$PORT"
printf '  %sAPI BUS%s       http://%s:%s/api/bus\n'    "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE" "$PORT"
printf '  %sWebSocket%s     ws://%s:%s\n'              "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE" "$PORT_REVERB"
if command -v mailpit > /dev/null 2>&1; then
    printf '  %sMailpit%s       http://%s:8025\n'      "$GRAS" "$NEUTRE" "$AFFICHAGE_HOTE"
fi

# Utile pour tester l'app Flutter depuis un téléphone du même réseau.
if [[ -n "$IP_LOCALE" ]]; then
    printf '  %sRéseau local%s  http://%s:%s %s(appareils mobiles)%s\n' \
        "$GRAS" "$NEUTRE" "$IP_LOCALE" "$PORT" "$GRIS" "$NEUTRE"
fi

printf '\n  %sLogs%s          %s/\n' "$GRAS" "$NEUTRE" "$DOSSIER_LOGS"
printf '%s%s─────────────────────────────────────────────%s\n' "$GRAS" "$GRIS" "$NEUTRE"

# Comptes de developpement. Les mots de passe ne sont rappeles que si le
# seeder vient de les poser : un mot de passe change en base ne doit pas
# etre annonce ici comme s'il valait encore.
printf '  %sCompte RH%s     admin@gmail.com / admin123 %s— les deux espaces%s\n' \
    "$GRAS" "$NEUTRE" "$GRIS" "$NEUTRE"
printf '  %sCompte BUS%s    bus@gmail.com / bus123     %s— transport seul%s\n' \
    "$GRAS" "$NEUTRE" "$GRIS" "$NEUTRE"
printf '%s%s─────────────────────────────────────────────%s\n' "$GRAS" "$GRIS" "$NEUTRE"
printf '  %sCtrl+C pour tout arrêter%s\n\n' "$GRIS" "$NEUTRE"

# --- Surveillance -------------------------------------------------------------

# Si un service principal meurt, on arrête l'ensemble plutôt que de laisser
# tourner un environnement incomplet et trompeur.
#
# macOS n'embarque que bash 3.2, dépourvu de `wait -n`. On veille donc avec un
# `sleep` lancé en arrière-plan puis attendu : bash ne traite un signal qu'entre
# deux commandes, mais il interrompt `wait` immédiatement — là où un `sleep` au
# premier plan retarderait l'arrêt de toute sa durée.
while true; do
    sleep 2 &
    VEILLE=$!
    wait "$VEILLE" 2>/dev/null

    # Un Ctrl+C est déjà pris en charge par le trap : rien à diagnostiquer ici.
    [[ $ARRET_DEMANDE -eq 1 || $ARRET_EN_COURS -eq 1 ]] && break

    # On ne surveille que les services : les indices pairs de PIDS.
    # (chaque demarrer() empile le service, puis son suiveur de logs)
    for indice in 0 2 4 6; do
        pid="${PIDS[$indice]:-}"
        [[ -z "$pid" ]] && continue

        if ! kill -0 "$pid" 2>/dev/null; then
            # Un Ctrl+C frappe tout le groupe de processus : les services
            # tombent alors ensemble, et le trap n'a pas forcement encore
            # pose son drapeau — bash ne le traite qu'entre deux commandes.
            #
            # On compte donc les survivants : une panne reelle n'emporte
            # qu'un service, un arret groupe les fauche tous. Le second cas
            # n'a rien d'un incident, et l'annoncer comme tel ferait douter
            # d'un arret parfaitement normal.
            morts=0
            total=0
            for autre in 0 2 4 6; do
                autre_pid="${PIDS[$autre]:-}"
                [[ -z "$autre_pid" ]] && continue
                total=$((total + 1))
                kill -0 "$autre_pid" 2>/dev/null || morts=$((morts + 1))
            done

            # La moitie ou plus est tombee d'un coup : c'est un arret groupe.
            if [[ $ARRET_DEMANDE -eq 1 || $ARRET_EN_COURS -eq 1 ]] \
               || [[ $total -gt 0 && $((morts * 2)) -ge $total ]]; then
                break 2
            fi

            printf '\n'
            erreur "Le service « ${NOMS_PIDS[$indice]} » s'est arrêté — voir $DOSSIER_LOGS/"
            arreter
        fi
    done
done

# Sortie de boucle sur un arrêt groupé : on repasse par la procédure de ménage
# pour ne laisser derrière ni suiveur de logs, ni port occupé.
arreter
