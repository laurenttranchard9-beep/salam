# Miam · site vitrine pour vos cartes de restaurant

Site pour présenter votre service de création de cartes en ligne et de sites pour restaurants, inspiré de l'univers coloré de Ciao Kombucha. Il tourne sur n'importe quel hébergement **Apache + PHP**, sans base MySQL à configurer ni dépendance à installer.

Le dossier à mettre en ligne est **`www/`**. Le dossier `site/` contient une ancienne démo sans rapport.

## Ce qu'il contient

**La vitrine**, en plusieurs pages reliées par une transition douce (le nouvel écran monte comme un rideau) :
- **Accueil** (`/`) : une histoire qui se déroule à la molette. Les deux téléphones de vos réalisations s'écartent, puis la vraie carte d'Aux Saveurs Braisées défile dans un téléphone pendant que cinq points forts s'allument, un texte se révèle mot à mot, les quatre formules passent à l'horizontale en changeant la couleur du fond, les étapes s'empilent comme des cartes, et les réalisations bougent en parallaxe.
- **Formules** (`/formules`) : les quatre formules en « bouteilles » qui s'ouvrent en fiche plein écran, et un tableau comparatif avec un bouton « Choisir » qui présélectionne la formule dans le formulaire.
- **La démo** (`/demo`) : six cuisines dans un téléphone, avec recherche, filtres, « Ma liste », un mode gestion et un aperçu des statistiques.
- **Menus imprimés** (`/menus`) : vos cartes papier. La carte d'Aux Saveurs Braisées se déplie à la molette (fermée, premier volet, grande ouverte, puis retournée pour montrer le dos), et une galerie présente les trois créations (Aux Saveurs Braisées, La Fleur d'Or style bistrot et style ardoise) : chaque feuille se retourne (extérieur / intérieur) et s'agrandit dans une visionneuse pour lire chaque ligne. Un aperçu en éventail renvoie vers cette page depuis l'accueil et les réalisations.
- **Réalisations** (`/realisations`), **Méthode** (`/methode`, étapes et questions fréquentes) et **Contact** (`/contact`, avec l'option « Carte imprimée »).
- Défilement doux à la molette, titres qui montent mot à mot, boutons « magnétiques », bandeau final qui accélère quand on défile. Si l'appareil demande moins d'animations (réglage de Windows, macOS, iOS ou Android), le site passe en mode doux : les scènes pilotées par la molette restent, les effets décoratifs s'arrêtent. Un lien en pied de page permet au visiteur de tout réactiver.

**Les cookies et les statistiques**
- Bandeau « Un petit cookie ? » avec Accepter et Refuser à égalité, choix gardé 6 mois, bouton « Gérer les cookies » en pied de page.
- Sans accord : un compteur anonyme (pages vues, sources, appareils), sans cookie ni identifiant.
- Avec accord : visiteurs uniques, durée, lecture de page, jours et heures, clics sur les boutons, formules consultées, parcours jusqu'à la demande.
- Tout reste sur votre serveur : pas de Google Analytics, rien n'est envoyé ailleurs.

**L'espace gestion** (`/admin/`)
- **Messages** : boîte de réception avec non lus, statuts (nouveau, en cours, traité, archivé, indésirable), recherche, note interne, réponse par email en un clic, export CSV pour Excel.
- **Statistiques** : 7 jours, 30 jours, 90 jours ou 12 mois, courbe avec info-bulles, sources, pages, appareils, heatmap jours × heures, parcours vers une demande.
- **Réglages** : email de notification, téléphone et tarifs affichés sur le site, email de test, exclusion de vos propres visites, changement de mot de passe, état du serveur.
- Email envoyé à chaque nouvelle demande (fonction `mail()` de PHP).
- Mode sombre automatique, utilisable sur téléphone.

## Le logo

Le dossier `logo/` contient le logo « miam » (le mot en dégradé du pied de page) avec la mention « Créateur de site internet, menu et motion design ». Tout le dossier est aussi disponible en un seul fichier : [miam-logo.zip](https://github.com/laurenttranchard9-beep/salam/raw/claude/nice-maxwell-kmxeg2/miam-logo.zip).

| Fichier | Pour quoi faire |
|---|---|
| `miam-logo-fond-sombre.png` / `.svg` | Version principale, sur fond chocolat (3000 px de large) |
| `miam-logo-fond-clair.png` / `.svg` | Sur fond crème : documents, devis, factures |
| `miam-logo-transparent-texte-clair.png` / `.svg` | Fond transparent, à poser sur une photo ou un fond foncé |
| `miam-logo-transparent-texte-fonce.png` / `.svg` | Fond transparent, à poser sur un fond clair |
| `miam-logo-carre.png` / `.svg` | Photo de profil (Instagram, Facebook, Google), 2160 × 2160 |
| `miam-logo-anime.gif` / `.svg` | La version animée (motion design) : les lettres montent une à une, puis la mention apparaît |

Les SVG sont vectoriels (lettres converties en tracés) : ils s'agrandissent sans perte et s'ouvrent sans les polices, pour un imprimeur, une enseigne ou un flocage. Pour les refaire (autre mention, autres couleurs) : modifiez `TAGLINE` ou les couleurs en haut de `tools/make-logo.py`, puis lancez `python3 tools/make-logo.py` (demande `pip install fonttools uharfbuzz brotli`). La mention du pied de page du site se change dans `www/config.php` (`tagline`).

## Prérequis

- Apache 2.4 avec `mod_rewrite` (et si possible `mod_headers`, `mod_expires`, `mod_deflate`), `AllowOverride All`.
- PHP 8.1 ou plus avec l'extension PDO SQLite, activée chez la plupart des hébergeurs (la page Réglages > État du site vérifie tout ça pour vous).

## Télécharger

**[miam-site-apache.zip](https://github.com/laurenttranchard9-beep/salam/raw/claude/nice-maxwell-kmxeg2/miam-site-apache.zip)** contient le dossier `www/` prêt à déposer : décompressez-le directement à la racine de votre site. Si vous modifiez `www/`, refaites l'archive avec `git archive --format=zip -o miam-site-apache.zip HEAD:www`.

## Avec XAMPP (sur votre ordinateur)

1. Ouvrez le panneau XAMPP et démarrez **Apache** (MySQL n'est pas utile).
2. Décompressez `miam-site-apache.zip` dans `C:\xampp\htdocs\miam`. Vérifiez que le fichier `C:\xampp\htdocs\miam\index.php` existe (pas de dossier en trop entre les deux).
3. Ouvrez http://localhost/miam/ pour le site et http://localhost/miam/admin/ pour créer votre compte.

Bon à savoir :
- Si une page « Il manque quelque chose sur le serveur » s'affiche, elle dit quelle ligne activer dans `C:\xampp\php\php.ini`. Redémarrez Apache après la modification.
- Si Apache tourne sur un autre port (par exemple 8080), l'adresse devient http://localhost:8080/miam/.
- XAMPP n'envoie pas d'emails : c'est normal, les messages du formulaire restent visibles dans l'espace gestion.
- Sur Mac ou Linux, rendez le dossier `data` inscriptible : `chmod 777 /Applications/XAMPP/htdocs/miam/data`.
- XAMPP sert à tester sur votre ordinateur : pour que vos clients voient le site, il faut le déposer chez un hébergeur (étapes ci-dessous).

## Sur un serveur Amazon Linux (AWS), en une commande

Connectez-vous au serveur en SSH, puis collez :

```bash
curl -fsSL https://tinyurl.com/miam-install | sudo bash -s -- votre-domaine.fr
```

(le lien court mène à [install.sh](https://raw.githubusercontent.com/laurenttranchard9-beep/salam/claude/nice-maxwell-kmxeg2/install.sh), dans ce dépôt)

Sans nom de domaine (`… | sudo bash`), le site reçoit une adresse automatique du type `http://miam.12-34-56-78.sslip.io`, pratique pour le montrer tout de suite.

Ce que fait la commande (`install.sh`, à lire avant si vous voulez) :
- repère Apache ou Nginx et PHP ; ajoute seulement ce qui manque (extensions `pdo` et `mbstring` de la même version de PHP), et refuse de changer la version de PHP de vos autres sites ;
- installe le site dans `/var/www/miam` : le code appartient à root, seul `data/` est modifiable par PHP ;
- vous demande un identifiant et un mot de passe pour l'espace gestion (ou en génère un s'il n'y a pas de clavier) : personne ne peut créer le compte à votre place depuis le web ;
- ajoute un fichier de configuration à part (`zzz-miam.conf`), chargé en dernier pour ne jamais devenir le site par défaut ;
- teste vos autres sites avant et après (code HTTP, redirection et titre de page) : si l'un d'eux répond différemment, tout est annulé ;
- active le HTTPS avec certbot s'il est déjà installé et que le domaine pointe vers le serveur.

Relancer la même commande **met à jour** le site (messages, statistiques, compte et `config.php` conservés, sauvegarde dans `/root/miam-sauvegardes`). Pour le retirer : `… | sudo bash -s -- --desinstaller`.

## Mise en ligne sur un hébergement mutualisé

1. Ouvrez `www/config.php` et vérifiez : le nom de la marque, votre nom, l'email qui reçoit les notifications, et **les mentions légales** (SIRET, adresse, hébergeur). Les champs vides s'affichent « à compléter » sur le site.
2. Envoyez **le contenu** du dossier `www/` par FTP à la racine de votre site (souvent `www/` ou `public_html/`). N'envoyez pas le dossier `tools/`.
3. Vérifiez que le dossier `data/` est accessible en écriture (droits 755 ou 775 selon l'hébergeur).
4. **Ouvrez tout de suite `https://votre-domaine.fr/admin/`** pour créer votre compte : tant qu'il n'existe pas, la page de création est ouverte à tous.
5. Dans Réglages : envoyez-vous l'email de test, ajoutez votre téléphone et vos tarifs si vous voulez les afficher.
6. Une fois le certificat HTTPS actif, décommentez les deux lignes « Passer en HTTPS » dans `www/.htaccess`.

Le site fonctionne aussi dans un sous-dossier (`https://domaine.fr/cartes/`) sans rien changer.

### Sur un serveur (VPS)

```apache
<VirtualHost *:80>
    ServerName votre-domaine.fr
    DocumentRoot /var/www/miam/www
    <Directory /var/www/miam/www>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Puis `a2enmod rewrite headers expires deflate` et `chown -R www-data: /var/www/miam/www/data`.

## Essayer sur votre ordinateur

```sh
php -S localhost:8000 -t www tools/dev-router.php
```

Ouvrez http://localhost:8000. Pour voir l'espace gestion avec des chiffres, remplissez la base avec des données **fictives** :

```sh
php tools/seed-demo.php --reset
```

Ne lancez jamais ce script en ligne. Pour repartir de zéro, supprimez `www/data/site.sqlite` (ou utilisez « Effacer les statistiques » dans Réglages).

## Au quotidien

- **Mot de passe oublié** : déposez par FTP un fichier vide nommé `reset-admin` dans `data/`, puis ouvrez `/admin/`. Vous recréez le compte, messages et statistiques sont conservés.
- **Sauvegarde** : tout tient dans un fichier, `data/site.sqlite`. Téléchargez-le de temps en temps par FTP.
- **QR codes** : faites-les pointer vers `https://votre-domaine.fr/?utm_source=qr`. Les visites apparaîtront sous « QR code » dans les statistiques, même pour les visiteurs qui refusent les cookies.
- **Vos propres visites** ne sont pas comptées sur les appareils où vous vous êtes connecté à l'espace gestion.

## Personnaliser

| Quoi | Où |
|---|---|
| Nom de la marque, email, mentions légales | `www/config.php` |
| Formules, démo, réalisations, étapes, FAQ, tableau comparatif | `www/includes/content.php` |
| Scènes animées de l'accueil | `www/index.php` et `www/assets/js/home.js` |
| Menus imprimés (textes, galerie) | `print_menus()` dans `www/includes/content.php`, images dans `www/assets/img/menus/` |
| Dépliant qui s'ouvre au défilement | `www/includes/sections/depliant.php` et `www/assets/js/menus.js` |
| Téléphone, email affiché, zone, tarifs | Espace gestion > Réglages |
| Couleurs, typographie, mise en page | `www/assets/css/site.css` |
| Image de partage (réseaux sociaux) | `www/assets/img/og.jpg` (1200 × 630, contient le logo « miam ») |

Le nom « Miam » est une proposition : changez `site_name` dans `config.php`, le logo et les titres suivent. Pensez alors à refaire `og.jpg`.

## Sécurité et vie privée

- Mots de passe chiffrés (bcrypt), session limitée à 2 h d'inactivité, cookie de session `HttpOnly` et `SameSite=Strict`, jeton anti-CSRF sur chaque action, connexions limitées à 8 essais par quart d'heure.
- Formulaire protégé par un champ piège, un jeton signé (pas de robot trop rapide) et une limite de 5 messages par heure et par connexion. Les messages bourrés de liens sont rangés dans « Indésirable ».
- `config.php`, `includes/` et `data/` sont inaccessibles depuis le web. En-têtes de sécurité et politique CSP stricte (aucun script externe).
- Aucune adresse IP n'est enregistrée. Détail des visites effacé après 13 mois, totaux anonymes après 25 mois, messages traités ou archivés après 3 ans.

## Arborescence

```
install.sh                        installation en une commande sur Amazon Linux
www/
  index.php                       accueil (scènes au défilement)
  formules.php, demo.php, realisations.php, menus.php, methode.php, contact.php
  legal.php, 404.php              mentions, confidentialité, page introuvable
  admin/index.php                 espace gestion
  includes/cli/create-admin.php   création du compte en ligne de commande (install.sh)
  api/contact.php, api/track.php  formulaire et mesure d'audience
  includes/                       code PHP (bloqué par .htaccess)
  assets/                         CSS, JavaScript, polices, images
  data/                           base SQLite, créée au premier lancement
  config.php, .htaccess
tools/
  dev-router.php                  serveur local sans Apache
  seed-demo.php                   données fictives pour essayer
```

Polices Bricolage Grotesque et Unbounded sous licence SIL Open Font License, hébergées avec le site. Animations : GSAP et ScrollTrigger (licence standard GSAP, gratuite) et Lenis (licence MIT), également hébergés avec le site dans `assets/js/vendor/`.
