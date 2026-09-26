# Miam · site vitrine pour vos cartes de restaurant

Site pour présenter votre service de création de cartes en ligne et de sites pour restaurants, inspiré de l'univers coloré de Ciao Kombucha. Il tourne sur n'importe quel hébergement **Apache + PHP**, sans base MySQL à configurer ni dépendance à installer.

Le dossier à mettre en ligne est **`www/`**. Le dossier `site/` contient une ancienne démo sans rapport.

## Ce qu'il contient

**La vitrine** (`www/index.php`)
- Hero façon Ciao : dégradé plein écran, vos deux réalisations dans des téléphones inclinés qui flottent et suivent la souris.
- Rubans défilants, puis **la gamme** : quatre formules présentées comme des bouteilles. Chacune s'ouvre en fiche plein écran avec son prix et un bouton qui présélectionne la formule dans le formulaire.
- **Une démo interactive** : six cuisines (pizzeria, sushi, grill…), une carte dans un téléphone avec recherche, filtres et « Ma liste », un mode « Côté gestion » où l'on change un prix ou coupe un plat, et un aperçu des statistiques.
- **Les réalisations** : Aux Saveurs Braisées et La Fleur d'Or, avec des captures réelles (ordinateur et téléphone qui défile).
- La méthode en quatre étapes, une FAQ, le **formulaire de contact**, les mentions légales et la page confidentialité.

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

## Prérequis

- Apache 2.4 avec `mod_rewrite` (et si possible `mod_headers`, `mod_expires`, `mod_deflate`), `AllowOverride All`.
- PHP 8.1 ou plus avec l'extension PDO SQLite, activée chez la plupart des hébergeurs (la page Réglages > État du site vérifie tout ça pour vous).

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
| Formules, démo, réalisations, étapes, FAQ | `www/includes/content.php` |
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
www/
  index.php, legal.php, 404.php   pages publiques
  admin/index.php                 espace gestion
  api/contact.php, api/track.php  formulaire et mesure d'audience
  includes/                       code PHP (bloqué par .htaccess)
  assets/                         CSS, JavaScript, polices, images
  data/                           base SQLite, créée au premier lancement
  config.php, .htaccess
tools/
  dev-router.php                  serveur local sans Apache
  seed-demo.php                   données fictives pour essayer
```

Polices Bricolage Grotesque et Unbounded sous licence SIL Open Font License, hébergées avec le site.
