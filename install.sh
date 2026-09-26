#!/usr/bin/env bash
# =============================================================================
#  miam · installation sur un serveur Amazon Linux (2023, ou 2)
#
#  Ajoute le site à côté de ceux qui tournent déjà, sans toucher à leur
#  configuration :
#    - un fichier de configuration à part, chargé en dernier ;
#    - vos autres sites sont testés avant et après : si l'un d'eux répond
#      différemment, tout est annulé automatiquement.
#
#  Installer          curl -fsSL https://tinyurl.com/miam-install | sudo bash -s -- mondomaine.fr
#                     (le lien court mène à ce fichier, sur GitHub)
#  Sans domaine       curl -fsSL https://tinyurl.com/miam-install | sudo bash
#                     (adresse automatique du type http://miam.12-34-56-78.sslip.io)
#  Mettre à jour      la même commande : messages, statistiques, compte et
#                     config.php sont gardés
#  Désinstaller       curl -fsSL https://tinyurl.com/miam-install | sudo bash -s -- --desinstaller
#
#  Options : --sans-https, --email=vous@exemple.fr (pour Let's Encrypt)
#  Variables : MIAM_DIR (dossier du site, /var/www/miam par défaut),
#              MIAM_IP (adresse IP publique, si elle n'est pas détectée)
# =============================================================================
set -euo pipefail

REPO_RAW="https://raw.githubusercontent.com/laurenttranchard9-beep/salam/claude/nice-maxwell-kmxeg2"
ZIP_URL="${MIAM_ZIP_URL:-$REPO_RAW/miam-site-apache.zip}"
APP_DIR="${MIAM_DIR:-/var/www/miam}"
APP_DIR="${APP_DIR%/}"
BACKUP_DIR="/root/miam-sauvegardes"
CONF="zzz-miam"                       # chargé après les autres : ne devient jamais le site « par défaut »
DEFAULT_KEEPER="000-miam-site-par-defaut"
LOG="/var/log/miam-install.log"

# ---------- Affichage ----------
if [ -t 1 ]; then B=$'\e[1m'; G=$'\e[32m'; Y=$'\e[33m'; R=$'\e[31m'; D=$'\e[2m'; N=$'\e[0m'; else B='' G='' Y='' R='' D='' N=''; fi
step() { printf '\n%s▸ %s%s\n' "$B" "$*" "$N"; }
ok()   { printf '  %s✓%s %s\n' "$G" "$N" "$*"; }
info() { printf '  %s%s%s\n' "$D" "$*" "$N"; }
warn() { printf '  %s!%s %s\n' "$Y" "$N" "$*"; }
die()  { printf '\n%s✗ %s%s\n' "$R" "$*" "$N" >&2; exit 1; }

usage() {
  cat <<'EOF'
Installer          curl -fsSL https://tinyurl.com/miam-install | sudo bash -s -- mondomaine.fr
Sans domaine       curl -fsSL https://tinyurl.com/miam-install | sudo bash
Mettre à jour      la même commande
Désinstaller       curl -fsSL https://tinyurl.com/miam-install | sudo bash -s -- --desinstaller
Options            --sans-https   --email=vous@exemple.fr
Dossier du site    MIAM_DIR (par défaut /var/www/miam)
EOF
}

# ---------- Terminal (la commande arrive par « curl | bash » : on parle au clavier via /dev/tty) ----------
has_tty() { { : </dev/tty; } 2>/dev/null; }
ask() { # ask "Question" "défaut" → REPLY
  REPLY=''
  if has_tty; then printf '  %s ' "$1" >/dev/tty; IFS= read -r REPLY </dev/tty || REPLY=''; fi
  [ -n "$REPLY" ] || REPLY=${2-}
}
ask_secret() {
  REPLY=''
  if has_tty; then
    printf '  %s ' "$1" >/dev/tty
    stty -echo </dev/tty 2>/dev/null || true
    IFS= read -r REPLY </dev/tty || REPLY=''
    stty echo </dev/tty 2>/dev/null || true
    printf '\n' >/dev/tty
  fi
}

# ---------- Système ----------
has_systemd() { [ -d /run/systemd/system ]; }
is_running() { # nom exact du processus
  local f
  for f in /proc/[0-9]*/comm; do
    [ "$(cat "$f" 2>/dev/null)" = "$1" ] && return 0
  done
  return 1
}
port80_busy() { grep -Eqs ':0050 [0-9A-F]+:0000 0A' /proc/net/tcp /proc/net/tcp6; }
pkg_install() { "$PKG" install -y "$@" >>"$LOG" 2>&1 || die "Installation impossible : $* (détails dans $LOG)"; }
ensure_tool() { command -v "$1" >/dev/null 2>&1 || pkg_install "$2"; }

reload_web() {
  if [ "$SERVER" = apache ]; then
    if has_systemd; then systemctl reload httpd >>"$LOG" 2>&1; else httpd -k graceful >>"$LOG" 2>&1; fi
  else
    if has_systemd; then systemctl reload nginx >>"$LOG" 2>&1; else nginx -s reload >>"$LOG" 2>&1; fi
  fi
  sleep 2
}
start_web() {
  if [ "$SERVER" = apache ]; then
    if has_systemd; then systemctl enable --now httpd >>"$LOG" 2>&1; else httpd -k start >>"$LOG" 2>&1; fi
  else
    if has_systemd; then systemctl enable --now nginx >>"$LOG" 2>&1; else nginx >>"$LOG" 2>&1; fi
  fi
  sleep 1
}
config_test() {
  if [ "$SERVER" = apache ]; then httpd -t >>"$LOG" 2>&1; else nginx -t >>"$LOG" 2>&1; fi
}
fpm_up() { # démarre PHP-FPM s'il ne tourne pas, sinon le recharge (nouvelles extensions)
  if is_running php-fpm; then
    if has_systemd; then systemctl reload php-fpm >>"$LOG" 2>&1; else kill -USR2 "$(cat /run/php-fpm/php-fpm.pid)"; fi
  else
    if has_systemd; then systemctl enable --now php-fpm >>"$LOG" 2>&1; else mkdir -p /run/php-fpm; php-fpm -D; fi
  fi
  sleep 1
}

public_ip() {
  local ip='' token
  ip=$(curl -fsS --max-time 5 https://checkip.amazonaws.com 2>/dev/null | tr -d '[:space:]') || ip=''
  if ! [[ $ip =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    token=$(curl -fsS --max-time 2 -X PUT http://169.254.169.254/latest/api/token -H 'X-aws-ec2-metadata-token-ttl-seconds: 60' 2>/dev/null) || token=''
    ip=$(curl -fsS --max-time 2 -H "X-aws-ec2-metadata-token: $token" http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null) || ip=''
  fi
  [[ $ip =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] && printf '%s' "$ip"
}
resolve() { { getent ahostsv4 "$1" 2>/dev/null || true; } | head -n 1 | cut -d' ' -f1; }

# ---------- Tests des sites (avant / après) ----------
fingerprint() { # empreinte d'une page : code HTTP, redirection, et titre (ou début du contenu)
  local body="$WORK/page" meta title
  meta=$(curl -sk --noproxy '*' -m 8 -o "$body" -w '%{http_code} %{redirect_url}' "$@" 2>/dev/null) || meta="000 "
  [ -f "$body" ] || : >"$body"
  title=$(tr -d '\r\n' <"$body" | sed -n 's/.*<title[^>]*>\([^<]*\)<\/title>.*/\1/Ip' | head -c 80) || title=''
  if [ -z "$title" ]; then
    title="#$(head -c 400 "$body" | tr -d '0-9[:space:]' | md5sum | cut -c1-10)"
  fi
  rm -f "$body"
  printf '%s %s' "${meta% }" "$title"
}
probe() { # empreintes http et https d'un nom de site, en passant par ce serveur
  printf '%s || %s' \
    "$(fingerprint -H "Host: $1" http://127.0.0.1/)" \
    "$(fingerprint --resolve "$1:443:127.0.0.1" "https://$1/")"
}
list_other_sites() { # noms déclarés dans la configuration, sauf ceux de ce site
  local names
  if [ "$SERVER" = apache ]; then
    names=$(httpd -S 2>/dev/null | sed -n 's/.*namevhost \([^ ]*\).*/\1/p; s/^ *alias \([^ ]*\).*/\1/p; s/^[^ ]*:[0-9]* *\([^ (]*\) (.*/\1/p')
  else
    names=$(nginx -T 2>/dev/null | sed -n 's/^[[:space:]]*server_name[[:space:]]\{1,\}\([^;]*\);.*/\1/p' | tr ' ' '\n')
  fi
  { printf '%s\n' "$names"; printf '127.0.0.1\n'; [ -n "${PUBLIC_IP:-}" ] && printf '%s\n' "$PUBLIC_IP"; } \
    | grep -Ev '^$|^_$|^~|\*|^is$' | grep -Fxv -e "${DOMAIN:-@}" -e "${ALIAS:-@}" | sort -u | head -n 40
}
snapshot() { # snapshot fichier : l'état de chaque autre site
  local n
  : >"$1"
  while IFS= read -r n; do printf '%s %s\n' "$n" "$(probe "$n")" >>"$1"; done < <(list_other_sites)
}
compare_snapshots() { # compare avant / après ; relance une fois les tests qui diffèrent (site lent, rechargement)
  local before=$1 changed='' n was now
  while read -r n was; do
    now=$(probe "$n")
    if [ "$now" != "$was" ]; then sleep 3; now=$(probe "$n"); fi
    [ "$now" = "$was" ] || changed+="    $n"$'\n'"      avant : $was"$'\n'"      après : $now"$'\n'
  done <"$before"
  printf '%s' "$changed"
}

# ---------- Fichiers de configuration du serveur web ----------
conf_path() { if [ "$SERVER" = apache ]; then printf '/etc/httpd/conf.d/%s.conf' "$1"; else printf '/etc/nginx/conf.d/%s.conf' "$1"; fi; }
current_domain() { # domaine d'une installation précédente
  local f
  for f in /etc/httpd/conf.d/$CONF.conf /etc/nginx/conf.d/$CONF.conf; do
    [ -f "$f" ] || continue
    sed -n 's/^[[:space:]]*ServerName[[:space:]]\{1,\}\([^[:space:]]*\).*/\1/p; s/^[[:space:]]*server_name[[:space:]]\{1,\}\([^[:space:];]*\).*/\1/p' "$f" | head -n 1
    return
  done
}

write_apache_conf() {
  cat >"$(conf_path "$CONF")" <<EOF
# Site miam, ajouté par install.sh le $(date '+%d/%m/%Y').
# Fichier chargé en dernier exprès : il ne change pas le site par défaut du serveur.
# Les réglages fins (adresses propres, fichiers protégés, cache) sont dans $APP_DIR/.htaccess
<VirtualHost *:80>
    ServerName $DOMAIN
${ALIAS:+    ServerAlias $ALIAS}
    DocumentRoot $APP_DIR
    <Directory $APP_DIR>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog /var/log/httpd/miam-error.log
    CustomLog /var/log/httpd/miam-access.log combined
</VirtualHost>
EOF
}

keep_apache_default() {
  # Si aucun site n'est déclaré en VirtualHost sur le port 80, vos sites passent par la configuration
  # principale d'Apache. Ajouter un VirtualHost la court-circuiterait : on la garde donc en premier.
  local s
  s=$(httpd -S 2>&1 | sed -n '/VirtualHost configuration/,/ServerRoot/p') || s=''
  if ! grep -Eq '(^|[[:space:]])[^[:space:]]*:80([[:space:]]|$)' <<<"$s"; then
    cat >"$(conf_path "$DEFAULT_KEEPER")" <<'EOF'
# Ajouté par l'installateur de miam : la configuration principale d'Apache reste le site
# par défaut du port 80 (sans ce bloc, le site miam le deviendrait).
# À garder tant que zzz-miam.conf existe.
<VirtualHost *:80>
</VirtualHost>
EOF
    CREATED+=("$(conf_path "$DEFAULT_KEEPER")")
    info "Vos sites passent par la configuration principale d'Apache : elle reste le site par défaut."
  fi
}

write_nginx_conf() {
  local listen fpm ipv6=''
  # IPv6 seulement si vos autres sites l'utilisent déjà (sinon le rechargement pourrait échouer)
  grep -Eq 'listen[[:space:]]+\[::\]:80' <<<"$(nginx -T 2>/dev/null || true)" && ipv6=1
  listen=$(sed -n 's/^[[:space:]]*listen[[:space:]]*=[[:space:]]*\([^[:space:]]*\).*/\1/p' /etc/php-fpm.d/www.conf 2>/dev/null | head -n 1) || listen=''
  listen=${listen:-/run/php-fpm/www.sock}
  if [[ $listen == /* ]]; then fpm="unix:$listen"; else fpm=$listen; fi
  cat >"$(conf_path "$CONF")" <<EOF
# Site miam, ajouté par install.sh le $(date '+%d/%m/%Y').
# Fichier chargé en dernier exprès : il ne change pas le site par défaut du serveur.
server {
    listen 80;
${ipv6:+    listen [::]:80;}
    server_name $DOMAIN${ALIAS:+ $ALIAS};
    root $APP_DIR;
    index index.php;
    charset utf-8;
    client_max_body_size 1m;
    access_log /var/log/nginx/miam-access.log;
    error_log /var/log/nginx/miam-error.log;

    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=(), interest-cohort=()" always;

    # Adresses propres (équivalent du .htaccess)
    rewrite ^/(formules|demo|realisations|menus|methode|contact)/?\$ /\$1.php last;
    rewrite ^/mentions-legales/?\$ /legal.php?page=mentions last;
    rewrite ^/confidentialite/?\$ /legal.php?page=confidentialite last;
    rewrite ^/robots\.txt\$ /robots.php last;
    rewrite ^/sitemap\.xml\$ /sitemap.php last;

    # Fichiers et dossiers internes : jamais servis
    location ~ /\.(?!well-known/) { deny all; }
    location ~ ^/(includes|data)(/|\$) { deny all; }
    location = /config.php { deny all; }
    location ~* \.(sqlite|sqlite3|db|log|md|sh|ini|lock|dist|bak|example)\$ { deny all; }

    location / {
        try_files \$uri \$uri/ /404.php;
    }
    location ~* \.(?:css|js|woff2)\$ {
        expires 1y;
        access_log off;
        try_files \$uri /404.php;
    }
    location ~* \.(?:webp|png|jpe?g|svg|ico)\$ {
        expires 30d;
        access_log off;
        try_files \$uri /404.php;
    }
    location ~ \.php\$ {
        try_files \$uri /404.php;
        fastcgi_pass $fpm;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOF
}

# ---------- Annulation ----------
CREATED=()          # fichiers ajoutés pendant cette exécution
SAVED=()            # « original|copie » des fichiers modifiés
rollback_conf() {
  local f pair
  for f in ${CREATED[@]+"${CREATED[@]}"}; do rm -f "$f"; done
  for pair in ${SAVED[@]+"${SAVED[@]}"}; do cp -p "${pair#*|}" "${pair%%|*}"; done
  CREATED=(); SAVED=()
  if config_test; then reload_web || true; fi
}
commit_conf() { CREATED=(); SAVED=(); }   # les changements précédents sont validés
abort() { # annule tout (configuration, et fichiers si c'était une première installation), puis s'arrête
  rollback_conf
  if [ "$UPDATE" = 0 ] && [ -f "$APP_DIR/includes/core.php" ]; then rm -rf "$APP_DIR"; fi
  die "$@"
}
save_file() { # garde une copie avant modification
  local copy
  [ -f "$1" ] || return 0
  copy="$WORK/$(basename "$1").orig"
  cp -p "$1" "$copy"
  SAVED+=("$1|$copy")
}

# ---------- Étapes ----------
check_system() {
  [ "$(id -u)" -eq 0 ] || die "Lancez la commande avec sudo :  curl -fsSL https://tinyurl.com/miam-install | sudo bash"
  : >>"$LOG"
  local os_id os_name
  # shellcheck disable=SC1091
  os_id=$(. /etc/os-release 2>/dev/null && printf '%s' "${ID:-}") || os_id=''
  # shellcheck disable=SC1091
  os_name=$(. /etc/os-release 2>/dev/null && printf '%s' "${PRETTY_NAME:-}") || os_name=''
  if [ "$os_id" != amzn ]; then warn "Système détecté : ${os_name:-inconnu}. Ce script est prévu pour Amazon Linux."; fi
  if command -v dnf >/dev/null 2>&1; then PKG=dnf; elif command -v yum >/dev/null 2>&1; then PKG=yum; else die "Ni dnf ni yum : ce script est prévu pour Amazon Linux."; fi
  ensure_tool curl curl
  ensure_tool unzip unzip
  ensure_tool tar tar
  ensure_tool find findutils
  ok "${os_name:-Système} · gestionnaire de paquets $PKG"
}

detect_server() {
  SERVER=''
  local httpd_on=0 nginx_on=0
  is_running httpd && httpd_on=1
  is_running nginx && nginx_on=1
  if [ $httpd_on = 1 ] && [ $nginx_on = 1 ]; then
    # Les deux tournent : celui qui écoute sur le port 80 reçoit les visiteurs
    local owner=''
    command -v ss >/dev/null 2>&1 && owner=$(ss -ltnpH 'sport = :80' 2>/dev/null || true)
    if grep -q '"httpd"' <<<"$owner"; then SERVER=apache; else SERVER=nginx; fi
  elif [ $httpd_on = 1 ]; then SERVER=apache
  elif [ $nginx_on = 1 ]; then SERVER=nginx
  elif port80_busy; then
    die "Le port 80 est utilisé par un autre programme qu'Apache ou Nginx (Docker, Node, Caddy…). Ce script ne sait pas s'y greffer sans risque pour vos autres sites."
  elif command -v httpd >/dev/null 2>&1; then SERVER=apache; start_web
  elif command -v nginx >/dev/null 2>&1; then SERVER=nginx; start_web
  else
    info "Aucun serveur web trouvé : installation d'Apache."
    pkg_install httpd
    SERVER=apache; start_web
  fi
  if [ "$SERVER" = apache ]; then ok "Serveur web : Apache ($(httpd -v 2>/dev/null | sed -n 's/^Server version: //p'))"
  else ok "Serveur web : Nginx ($(nginx -v 2>&1 | sed 's/^nginx version: //'))"; fi
}

setup_php() {
  local bin pkgname prefix version mods missing=()
  if ! command -v php >/dev/null 2>&1 && ! command -v php-fpm >/dev/null 2>&1; then
    info "PHP n'est pas installé : installation de la dernière version."
    if [ "$PKG" = yum ] && command -v amazon-linux-extras >/dev/null 2>&1; then
      amazon-linux-extras enable php8.2 >>"$LOG" 2>&1 || true
      yum clean metadata >>"$LOG" 2>&1 || true
    fi
    pkg_install php-cli php-fpm php-pdo php-mbstring
  fi
  # Préfixe des paquets PHP déjà en place (ex. php8.3) : on complète la même version, sans en changer
  bin=$(command -v php-fpm || command -v php)
  pkgname=$(rpm -qf --qf '%{NAME}\n' "$(readlink -f "$bin")" 2>/dev/null | head -n 1) || pkgname=''
  prefix=${pkgname%-fpm}; prefix=${prefix%-cli}; prefix=${prefix:-php}
  command -v php >/dev/null 2>&1 || pkg_install "$prefix-cli"
  command -v php-fpm >/dev/null 2>&1 || pkg_install "$prefix-fpm"

  version=$(php -r 'echo PHP_VERSION_ID;' 2>/dev/null) || version=0
  if [ "$version" -lt 80100 ]; then
    die "PHP $(php -r 'echo PHP_VERSION;' 2>/dev/null) est installé et sert sûrement à vos autres sites. Ce site demande PHP 8.1 ou plus : je ne change pas la version de PHP automatiquement pour ne pas les casser."
  fi
  mods=$(php -m 2>/dev/null || true)
  grep -qix 'pdo_sqlite' <<<"$mods" || missing+=("$prefix-pdo")
  grep -qix 'mbstring' <<<"$mods" || missing+=("$prefix-mbstring")
  if [ ${#missing[@]} -gt 0 ]; then
    info "Extensions ajoutées : ${missing[*]}"
    pkg_install "${missing[@]}"
  fi
  mods=$(php -m 2>/dev/null || true)
  grep -qix 'pdo_sqlite' <<<"$mods" || die "L'extension PHP pdo_sqlite reste introuvable (voir $LOG)."
  fpm_up
  PHP_USER=$(sed -n 's/^[[:space:]]*user[[:space:]]*=[[:space:]]*\([^[:space:]]*\).*/\1/p' /etc/php-fpm.d/www.conf 2>/dev/null | head -n 1) || PHP_USER=''
  PHP_USER=${PHP_USER:-apache}
  id "$PHP_USER" >/dev/null 2>&1 || PHP_USER=apache
  PHP_GROUP=$(id -gn "$PHP_USER")
  ok "PHP $(php -r 'echo PHP_VERSION;') avec SQLite · compte $PHP_USER"
}

choose_domain() {
  local previous
  previous=$(current_domain)
  if [ -z "$DOMAIN" ] && [ -n "$previous" ]; then DOMAIN=$previous; fi
  if [ -z "$DOMAIN" ] && has_tty; then
    printf '\n' >/dev/tty
    ask "Nom de domaine du site (ex. miam.mondomaine.fr), ou Entrée pour une adresse automatique :" ''
    DOMAIN=$REPLY
  fi
  DOMAIN=$(printf '%s' "$DOMAIN" | tr '[:upper:]' '[:lower:]' | sed -E 's#^[a-z]+://##; s#/.*$##; s#:[0-9]+$##; s#\.$##')
  if [ -z "$DOMAIN" ]; then
    [ -n "$PUBLIC_IP" ] || die "Impossible de trouver l'adresse IP publique du serveur : indiquez un nom de domaine."
    DOMAIN="miam.${PUBLIC_IP//./-}.sslip.io"
    AUTO_DOMAIN=1
  fi
  [[ $DOMAIN =~ ^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$ ]] || die "Nom de domaine invalide : $DOMAIN"
  ALIAS=''
  # Domaine « nu » (mondomaine.fr) : on ajoute www.mondomaine.fr s'il pointe aussi ici
  if [ "$AUTO_DOMAIN" = 0 ] && [ "$(tr -cd '.' <<<"$DOMAIN" | wc -c)" -eq 1 ] && [ -n "$PUBLIC_IP" ] && [ "$(resolve "www.$DOMAIN")" = "$PUBLIC_IP" ]; then
    ALIAS="www.$DOMAIN"
  fi
  if [ -n "$previous" ] && [ "$previous" != "$DOMAIN" ]; then DOMAIN_CHANGED=1; fi
  ok "Adresse : $DOMAIN${ALIAS:+ (et $ALIAS)}"
  if [ "$AUTO_DOMAIN" = 0 ] && [ -n "$PUBLIC_IP" ]; then
    local ip; ip=$(resolve "$DOMAIN")
    if [ "$ip" != "$PUBLIC_IP" ]; then
      warn "$DOMAIN ne pointe pas encore vers ce serveur (${ip:-aucune adresse}, attendu $PUBLIC_IP)."
      warn "Chez votre registraire, ajoutez un enregistrement A « $DOMAIN → $PUBLIC_IP ». Le site marchera dès qu'il sera pris en compte ; relancez ensuite la commande pour le HTTPS."
      DNS_OK=0
    fi
  fi
}

deploy_files() {
  local new="$APP_DIR.nouveau" old="$APP_DIR.ancien" stamp
  stamp=$(date +%Y%m%d-%H%M%S)
  info "Téléchargement de la dernière version…"
  curl -fsSL --retry 3 -o "$WORK/site.zip" "$ZIP_URL" || die "Téléchargement impossible : $ZIP_URL"
  rm -rf "$new"; mkdir -p "$new"
  unzip -q "$WORK/site.zip" -d "$new" || die "Archive illisible."
  [ -f "$new/index.php" ] && [ -f "$new/includes/core.php" ] || { rm -rf "$new"; die "Archive inattendue : index.php introuvable."; }

  if [ -d "$APP_DIR" ] && [ -n "$(ls -A "$APP_DIR" 2>/dev/null)" ]; then
    if [ ! -f "$APP_DIR/includes/core.php" ]; then
      rm -rf "$new"
      die "$APP_DIR existe déjà et contient autre chose : je n'y touche pas. Choisissez un autre dossier, par ex. :  curl -fsSL https://tinyurl.com/miam-install | sudo MIAM_DIR=/var/www/miam2 bash"
    fi
    UPDATE=1
    mkdir -p "$BACKUP_DIR"; chmod 700 "$BACKUP_DIR"
    tar -C "$(dirname "$APP_DIR")" -czf "$BACKUP_DIR/miam-$stamp.tar.gz" "$(basename "$APP_DIR")"
    # On ne garde que les 5 dernières sauvegardes
    ls -1t "$BACKUP_DIR"/miam-*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm -f
    ok "Sauvegarde de la version en place : $BACKUP_DIR/miam-$stamp.tar.gz"
    cp -p "$APP_DIR/config.php" "$new/config.php"
    rm -rf "$new/data"
    mv "$APP_DIR/data" "$new/data"
  fi
  rm -rf "$old"
  [ -d "$APP_DIR" ] && mv "$APP_DIR" "$old"
  mv "$new" "$APP_DIR"
  rm -rf "$old"
  mkdir -p "$APP_DIR/data"

  # Droits : le code appartient à root (PHP ne peut pas le modifier), seules les données sont modifiables
  chown -R root:root "$APP_DIR"
  find "$APP_DIR" -type d -exec chmod 755 {} +
  find "$APP_DIR" -type f -exec chmod 644 {} +
  chown root:"$PHP_GROUP" "$APP_DIR/config.php"; chmod 640 "$APP_DIR/config.php"
  fix_data_rights
  if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce 2>/dev/null)" = Enforcing ]; then
    chcon -R -t httpd_sys_content_t "$APP_DIR" 2>/dev/null || true
    chcon -R -t httpd_sys_rw_content_t "$APP_DIR/data" 2>/dev/null || true
    info "SELinux : dossier data/ autorisé en écriture."
  fi
  if [ "$UPDATE" = 1 ]; then ok "Site mis à jour dans $APP_DIR (données et config.php conservés)"; else ok "Site installé dans $APP_DIR"; fi
}
fix_data_rights() {
  chown -R "$PHP_USER:$PHP_GROUP" "$APP_DIR/data"
  find "$APP_DIR/data" -type d -exec chmod 770 {} +
  find "$APP_DIR/data" -type f -exec chmod 660 {} +
}

create_admin() {
  local cli="$APP_DIR/includes/cli/create-admin.php" user pass pass2
  if php "$cli" --existe >>"$LOG" 2>&1; then ok "Compte de l'espace gestion : déjà créé"; fix_data_rights; return; fi
  if has_tty; then
    printf '\n' >/dev/tty
    info "Création de votre compte pour l'espace gestion (messages et statistiques) :"
    ask "Identifiant [laurent] :" laurent; user=$REPLY
    while :; do
      ask_secret "Mot de passe (10 caractères minimum) :"; pass=$REPLY
      if [ "${#pass}" -lt 10 ]; then warn "Trop court."; continue; fi
      ask_secret "Le même, pour confirmer :"; pass2=$REPLY
      [ "$pass" = "$pass2" ] && break
      warn "Les deux mots de passe ne correspondent pas."
    done
  else
    user=laurent
    pass=$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 16)
    GENERATED_PASS=$pass
  fi
  printf '%s' "$pass" | php "$cli" "$user" >>"$LOG" 2>&1 || die "Création du compte impossible (voir $LOG)."
  ADMIN_USER=$user
  fix_data_rights
  ok "Compte « $user » créé"
}

configure_web() {
  local conf; conf=$(conf_path "$CONF")
  if [ -f "$conf" ] && [ "$DOMAIN_CHANGED" = 0 ]; then
    ok "Configuration du serveur déjà en place ($conf)"
    return
  fi
  if [ -f "$conf" ]; then
    save_file "$conf"
    local ssl; ssl=$(conf_path "$CONF-le-ssl")
    if [ -f "$ssl" ]; then save_file "$ssl"; rm -f "$ssl"; fi
  else
    CREATED+=("$conf")
  fi
  if [ "$SERVER" = apache ]; then keep_apache_default; write_apache_conf; else write_nginx_conf; fi
  if ! config_test; then
    abort "La configuration du serveur est refusée : rien n'a été changé pour vos autres sites. Détails : $LOG"
  fi
  reload_web
  ok "Configuration ajoutée : $conf"
}

self_test() { # le site répond-il ? (via ce serveur, avant même que le domaine pointe ici)
  local home admin internal
  home=$(curl -s --noproxy '*' -m 15 -H "Host: $DOMAIN" http://127.0.0.1/ 2>/dev/null) || home=''
  admin=$(curl -s --noproxy '*' -o /dev/null -m 15 -w '%{http_code}' -H "Host: $DOMAIN" http://127.0.0.1/admin/ 2>/dev/null) || admin=000
  internal=$(curl -s --noproxy '*' -o /dev/null -m 15 -w '%{http_code}' -H "Host: $DOMAIN" http://127.0.0.1/includes/core.php 2>/dev/null) || internal=000
  if ! grep -q 'data-base=' <<<"$home"; then
    # Déjà en HTTPS (mise à jour) : le port 80 redirige
    home=$(curl -sk --noproxy '*' -m 15 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/" 2>/dev/null) || home=''
    admin=$(curl -sk --noproxy '*' -o /dev/null -m 15 -w '%{http_code}' --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/admin/" 2>/dev/null) || admin=000
    internal=$(curl -sk --noproxy '*' -o /dev/null -m 15 -w '%{http_code}' --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/includes/core.php" 2>/dev/null) || internal=000
  fi
  grep -q 'data-base=' <<<"$home" || return 1
  [ "$admin" = 200 ] || return 1
  [ "$internal" = 403 ] || return 1
}

setup_https() {
  local plugin ssl args=()
  if [ "$HTTPS" = no ]; then info "HTTPS non demandé (--sans-https)."; return; fi
  ssl=$(conf_path "$CONF-le-ssl")
  if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ] && { [ -f "$ssl" ] || grep -q 'ssl_certificate' "$(conf_path "$CONF")" 2>/dev/null; }; then
    ok "HTTPS déjà en place"; HTTPS_ON=1; return
  fi
  if ! command -v certbot >/dev/null 2>&1; then
    warn "HTTPS : certbot n'est pas installé sur ce serveur, le site reste en http:// pour l'instant."
    return
  fi
  if [ "$DNS_OK" = 0 ] || [ -z "$PUBLIC_IP" ] || [ "$(resolve "$DOMAIN")" != "$PUBLIC_IP" ]; then
    warn "HTTPS : le domaine ne pointe pas encore ici. Relancez la commande une fois le DNS en place."
    return
  fi
  if [ "$SERVER" = apache ]; then
    plugin=--apache
    # Sans aucun site HTTPS déclaré en VirtualHost, un nouveau deviendrait le site HTTPS par défaut : on s'abstient
    if ! grep -Eq '^\*:443 ' <<<"$(httpd -S 2>/dev/null || true)"; then
      warn "HTTPS : aucun de vos sites n'utilise encore de VirtualHost HTTPS. Pour ne pas changer leur comportement, lancez vous-même :  sudo certbot --apache -d $DOMAIN"
      return
    fi
  else
    plugin=--nginx
  fi
  args=(--non-interactive --agree-tos --redirect --keep-until-expiring -d "$DOMAIN")
  [ -n "$ALIAS" ] && args+=(-d "$ALIAS")
  if [ ! -d /etc/letsencrypt/accounts ]; then
    if [ -n "$EMAIL" ]; then args+=(-m "$EMAIL"); else args+=(--register-unsafely-without-email); fi
  fi
  save_file "$(conf_path "$CONF")"
  [ -f "$ssl" ] || CREATED+=("$ssl")
  info "Demande du certificat Let's Encrypt…"
  if certbot "$plugin" "${args[@]}" >>"$LOG" 2>&1; then
    HTTPS_ON=1
    ok "HTTPS activé (certificat renouvelé automatiquement par certbot)"
  else
    rollback_conf
    warn "HTTPS : certbot n'a pas réussi (détails dans $LOG). Le site reste disponible en http://."
  fi
}

uninstall() {
  local stamp f removed=0
  stamp=$(date +%Y%m%d-%H%M%S)
  step "Désinstallation du site miam"
  if [ -f "/etc/httpd/conf.d/$CONF.conf" ]; then SERVER=apache; elif [ -f "/etc/nginx/conf.d/$CONF.conf" ]; then SERVER=nginx; else SERVER=''; fi
  mkdir -p "$BACKUP_DIR"; chmod 700 "$BACKUP_DIR"
  if [ -d "$APP_DIR" ]; then
    tar -C "$(dirname "$APP_DIR")" -czf "$BACKUP_DIR/miam-desinstalle-$stamp.tar.gz" "$(basename "$APP_DIR")"
    ok "Sauvegarde complète : $BACKUP_DIR/miam-desinstalle-$stamp.tar.gz"
  fi
  for f in /etc/httpd/conf.d/$CONF.conf /etc/httpd/conf.d/$CONF-le-ssl.conf /etc/httpd/conf.d/$DEFAULT_KEEPER.conf \
           /etc/nginx/conf.d/$CONF.conf /etc/nginx/conf.d/$CONF-le-ssl.conf; do
    [ -f "$f" ] && { cp -p "$f" "$BACKUP_DIR/$(basename "$f").$stamp"; rm -f "$f"; removed=1; }
  done
  if [ "$removed" = 1 ] && [ -n "$SERVER" ]; then
    config_test || die "La configuration du serveur est refusée après retrait (voir $LOG). Les fichiers retirés sont dans $BACKUP_DIR."
    reload_web
    ok "Configuration retirée, serveur rechargé"
  fi
  rm -rf "$APP_DIR"
  ok "Dossier $APP_DIR supprimé"
  printf '\n  Vos autres sites n'"'"'ont pas été modifiés. Le certificat HTTPS éventuel se retire avec :  sudo certbot delete\n\n'
}

main() {
  DOMAIN='' ACTION=install EMAIL="${MIAM_EMAIL:-}" HTTPS=auto
  UPDATE=0 AUTO_DOMAIN=0 DOMAIN_CHANGED=0 DNS_OK=1 HTTPS_ON=0 ADMIN_USER='' GENERATED_PASS='' ALIAS=''
  local arg
  for arg in "$@"; do
    case "$arg" in
      --desinstaller|--uninstall) ACTION=uninstall ;;
      --sans-https|--no-https) HTTPS=no ;;
      --email=*) EMAIL=${arg#*=} ;;
      -h|--help|--aide) usage; exit 0 ;;
      -*) die "Option inconnue : $arg" ;;
      *) DOMAIN=$arg ;;
    esac
  done

  if [ "$ACTION" = uninstall ]; then printf '\n%s miam %s· désinstallation%s\n' "$B" "$N$D" "$N"
  else printf '\n%s miam %s· installation sur votre serveur%s\n' "$B" "$N$D" "$N"; fi
  step "Vérification du serveur"
  check_system
  if [ "$ACTION" = uninstall ]; then uninstall; exit 0; fi

  WORK=$(mktemp -d)
  trap 'rm -rf "$WORK"' EXIT
  detect_server
  setup_php
  PUBLIC_IP=${MIAM_IP:-$(public_ip || true)}

  step "Adresse du site"
  choose_domain

  step "Vos sites actuels"
  snapshot "$WORK/avant.txt"
  if [ -s "$WORK/avant.txt" ]; then
    ok "$(wc -l <"$WORK/avant.txt" | tr -d ' ') adresse(s) testée(s) : elles seront vérifiées à nouveau à la fin"
    while read -r n s; do info "$n → ${s%% *}"; done <"$WORK/avant.txt"
  fi

  step "Installation des fichiers"
  deploy_files
  create_admin

  step "Configuration du serveur web"
  configure_web
  if ! self_test; then
    abort "Le site ne répond pas comme prévu : configuration retirée, vos autres sites sont inchangés. Détails : $LOG et /var/log/${SERVER/apache/httpd}/miam-error.log"
  fi
  ok "Le site répond"
  local changed
  changed=$(compare_snapshots "$WORK/avant.txt")
  if [ -n "$changed" ]; then
    printf '%s' "$changed" >&2
    abort "Un de vos autres sites a répondu différemment : j'ai tout annulé par prudence (voir ci-dessus)."
  fi
  [ -s "$WORK/avant.txt" ] && ok "Vos autres sites répondent exactement comme avant"
  commit_conf

  step "HTTPS"
  setup_https
  if [ "$HTTPS_ON" = 1 ]; then
    changed=$(compare_snapshots "$WORK/avant.txt")
    if [ -n "$changed" ]; then
      rollback_conf
      printf '%s' "$changed" >&2
      warn "Après le HTTPS, un autre site répondait différemment : HTTPS retiré, le site reste en http://."
      HTTPS_ON=0
    fi
  fi

  local url="http://$DOMAIN"
  [ "$HTTPS_ON" = 1 ] && url="https://$DOMAIN"
  printf '\n%s━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━%s\n' "$G" "$N"
  printf '%s  C'"'"'est en ligne !%s\n\n' "$B" "$N"
  printf '  Le site           %s%s%s\n' "$B" "$url" "$N"
  printf '  Espace gestion    %s/admin/\n' "$url"
  [ -n "$ADMIN_USER" ] && printf '  Identifiant       %s\n' "$ADMIN_USER"
  [ -n "$GENERATED_PASS" ] && printf '  Mot de passe      %s%s%s  (à changer dans Réglages)\n' "$B" "$GENERATED_PASS" "$N"
  printf '\n  Fichiers          %s  (textes et mentions légales : config.php)\n' "$APP_DIR"
  printf '  Mettre à jour     relancez la même commande\n'
  if [ "$AUTO_DOMAIN" = 1 ]; then
    printf '\n  %sAdresse automatique :%s elle suit l'"'"'IP du serveur. Pour votre propre domaine,\n' "$Y" "$N"
    printf '  faites-le pointer vers %s puis relancez la commande avec le domaine à la fin.\n' "$PUBLIC_IP"
  fi
  printf '%s━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━%s\n\n' "$G" "$N"
}

# Tout le script est lu avant de commencer (utile avec « curl | bash ») ; le clavier passe par /dev/tty.
main "$@" </dev/null
