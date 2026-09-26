<?php
declare(strict_types=1);

/*
 * Les textes du site : formules, démo, réalisations, méthode, FAQ.
 * Modifiez-les ici ; la page d'accueil et le formulaire se mettent à jour seuls.
 * Les tarifs affichés se règlent dans l'espace gestion > Réglages.
 */

/** Les formules (« la gamme »). La clé sert d'identifiant dans le formulaire et les statistiques. */
function offers(): array
{
    return [
        'carte' => [
            'name' => 'La Carte',
            'kicker' => 'Pour démarrer',
            'pitch' => 'Votre carte en ligne, claire sur tous les téléphones.',
            'description' => "Toute votre carte, rangée par catégories, qui s'ouvre en scannant le QR code posé sur vos tables. Vos clients cherchent un plat, filtrent ce qui est végétarien ou épicé, et voient toujours les bons prix.",
            'features' => [
                'Carte complète, rangée par catégories',
                'Recherche de plat et filtres (végé, épicé, sans gluten…)',
                'QR code prêt à imprimer pour vos tables',
                'Allergènes et mentions obligatoires',
                'Mise en ligne sur votre nom de domaine',
            ],
            'colors' => ['#ffd84d', '#ff9f1c', '#3b2500'],
            'shot' => null,
        ],
        'gestion' => [
            'name' => 'Carte + Gestion',
            'kicker' => 'Vous gardez la main',
            'pitch' => "Un prix change, un plat est épuisé : c'est à jour en quelques secondes.",
            'description' => "La Carte, avec un espace gestion rien qu'à vous. Depuis votre téléphone, vous modifiez un prix, ajoutez le plat du jour, masquez ce qui est épuisé. Et vous savez quand votre carte est consultée.",
            'features' => [
                'Tout ce que contient La Carte',
                'Espace gestion : plats, prix, catégories',
                'Stocks : un plat épuisé est signalé ou masqué',
                'Statistiques de consultation : jours, heures, appareils',
                'Bandeau cookies conforme aux recommandations de la CNIL',
            ],
            'colors' => ['#f0532d', '#b3151f', '#ffffff'],
            'shot' => 'asb-mobile.webp',
            'example' => 'Aux Saveurs Braisées',
        ],
        'site' => [
            'name' => 'Le Site Resto',
            'kicker' => 'La vitrine complète',
            'pitch' => 'Carte, formules, horaires et accès : tout votre restaurant au même endroit.',
            'description' => "Un site à votre image, pensé d'abord pour le téléphone : on vous trouve, on voit tout de suite si vous êtes ouvert, on vous appelle en un geste. Pour la vente à emporter, vos clients notent leurs plats dans « Ma liste » avant de téléphoner.",
            'features' => [
                "Page d'accueil à l'image du restaurant",
                'Ouvert ou fermé, affiché en direct selon vos horaires',
                'Bouton « Appeler » et adresse en un geste',
                '« Ma liste » pour préparer une commande à emporter',
                'Fiche restaurant, adresse et horaires lisibles par Google',
            ],
            'colors' => ['#8a5cf6', '#3d1f8c', '#ffffff'],
            'shot' => 'fdo-mobile.webp',
            'example' => "La Fleur d'Or",
        ],
        'sur-mesure' => [
            'name' => 'Sur mesure',
            'kicker' => 'Une idée en tête ?',
            'pitch' => 'Traduction, ardoise du jour, plusieurs cartes… on en parle.',
            'description' => "Une carte en anglais pour les touristes, une ardoise qui change chaque midi, une carte des vins à part, plusieurs établissements : dites-moi ce dont vous avez besoin, je vous propose une solution qui colle à votre façon de travailler.",
            'features' => [
                'Carte traduite (anglais, espagnol…)',
                'Menu du jour ou ardoise modifiable',
                'Plusieurs cartes : midi, soir, vins, desserts',
                'Plusieurs établissements',
                'Reprise ou refonte de votre site actuel',
            ],
            'colors' => ['#7be3b5', '#1f9d6f', '#1c1410'],
            'shot' => null,
        ],
    ];
}

/** Texte de prix affiché pour une formule (réglable dans l'espace gestion). */
function offer_price(string $key): string
{
    $price = trim((string) setting('price_' . $key, ''));
    return $price !== '' ? $price : 'Sur devis';
}

function business_types(): array
{
    return ['Restaurant', 'Pizzeria', 'Brasserie ou bistrot', 'Bar', 'Boulangerie ou pâtisserie', 'Food truck', 'Traiteur', 'Autre'];
}

function menu_sizes(): array
{
    return ['Moins de 30 plats', 'De 30 à 100 plats', 'Plus de 100 plats', 'Je ne sais pas'];
}

function contact_options(): array
{
    return [
        'qr' => 'QR codes pour les tables',
        'domaine' => 'Nom de domaine',
        'traduction' => 'Carte traduite',
        'emporter' => 'Vente à emporter',
        'photos' => 'Photos des plats',
    ];
}

/** Les réalisations (vrais sites). */
function projects(): array
{
    return [
        [
            'key' => 'asb',
            'name' => 'Aux Saveurs Braisées',
            'kind' => 'Cuisine à la braise : viandes, poissons et fruits de mer',
            'url' => 'https://auxsaveursbraisee.fr/',
            'domain' => 'auxsaveursbraisee.fr',
            'offer' => 'gestion',
            'points' => [
                'Toute la carte, avec recherche de plat',
                'Filtres par étiquette pour trouver vite',
                'Espace gestion : produits, catégories, stocks',
                'Statistiques de consultation, avec consentement',
            ],
            'desktop' => 'asb-desktop.webp',
            'mobile' => 'asb-mobile-long.webp',
            'colors' => ['#1d1512', '#f08a3c'],
        ],
        [
            'key' => 'fdo',
            'name' => "La Fleur d'Or",
            'kind' => 'Chinois, thaï, japonais et bar à sushis, à Grenade (31)',
            'url' => 'https://www.fleurdor31.fr/',
            'domain' => 'fleurdor31.fr',
            'offer' => 'site',
            'points' => [
                '181 plats et 39 boissons, rangés par cuisine',
                'Formules du midi et du soir',
                'Ouvert ou fermé, affiché en direct',
                'Appel en un geste et « Ma liste » pour la vente à emporter',
            ],
            'desktop' => 'fdo-desktop.webp',
            'mobile' => 'fdo-mobile-long.webp',
            'colors' => ['#b5412c', '#f4b93a'],
        ],
    ];
}

function method_steps(): array
{
    return [
        ['title' => 'On en parle', 'text' => "Vous m'envoyez votre carte actuelle, même en photo ou en PDF, et on fait le point sur ce dont vous avez besoin.", 'color' => '#ffd84d'],
        ['title' => 'Je prépare votre carte', 'text' => "Je saisis vos plats, je mets en page à vos couleurs, et vous recevez un lien pour l'essayer sur votre propre téléphone.", 'color' => '#ff7a45'],
        ['title' => 'Vous validez, on met en ligne', 'text' => "On ajuste ensemble ce qui doit l'être, puis je mets la carte en ligne et je prépare le QR code à imprimer.", 'color' => '#8a5cf6'],
        ['title' => 'La carte vit avec vous', 'text' => "Selon la formule, vous changez vos plats et vos prix vous-même, ou vous m'envoyez un message et je m'en occupe.", 'color' => '#34c38f'],
    ];
}

function faq(): array
{
    return [
        ['q' => 'Mes clients doivent-ils installer une application ?', 'a' => "Non. La carte s'ouvre dans le navigateur du téléphone, en scannant le QR code de la table ou en tapant l'adresse du site. Rien à télécharger, rien à créer."],
        ['q' => 'Est-ce que je peux changer mes prix moi-même ?', 'a' => "Oui, avec la formule Carte + Gestion ou Le Site Resto. Vous vous connectez à votre espace gestion depuis votre téléphone ou votre ordinateur : prix, plats, catégories, ruptures de stock. La carte est à jour dès que vous enregistrez."],
        ['q' => "J'ai déjà un site. Vous pouvez faire seulement la carte ?", 'a' => "Oui. La carte peut avoir sa propre adresse (par exemple carte.votre-restaurant.fr) et votre site actuel renvoie simplement vers elle."],
        ['q' => "Et si je n'ai pas de photos de mes plats ?", 'a' => "Ce n'est pas un problème : une carte bien mise en page se lit très bien sans photo. Si vous en avez, on les ajoute. Vous pouvez aussi en ajouter plus tard."],
        ['q' => 'Les cookies et le RGPD, ça se passe comment ?', 'a' => "La mesure d'audience ne démarre qu'après l'accord du visiteur, qui peut refuser aussi facilement qu'accepter. Pas de publicité, rien n'est revendu, et le cookie expire au bout de 13 mois au plus, comme le recommande la CNIL."],
        ['q' => 'Combien de temps faut-il pour que ma carte soit en ligne ?', 'a' => "Ça dépend surtout de la taille de votre carte et des options choisies. On fixe la date de mise en ligne ensemble dès le premier échange."],
        ['q' => "Qui s'occupe du nom de domaine et de l'hébergement ?", 'a' => "Je peux m'en charger de A à Z. Si vous avez déjà un nom de domaine ou un hébergement, on les utilise."],
        ['q' => 'Combien ça coûte ?', 'a' => "Le prix dépend de la formule, de la taille de la carte et des options. Décrivez votre projet dans le formulaire ci-dessous : je vous réponds avec un devis détaillé."],
    ];
}

/**
 * Cartes de démonstration pour la section « Goûtez la démo ».
 * Restaurants fictifs, prix en centimes.
 */
function demo_cuisines(): array
{
    return [
        'pizzeria' => [
            'label' => 'Pizzeria',
            'name' => 'Bella Nonna',
            'line' => 'Pizzas au feu de bois',
            'colors' => ['#e8412c', '#1f7a3f', '#fff4e6'],
            'menu' => [
                'Pizzas' => [
                    ['Margherita', 'Tomate, mozzarella fior di latte, basilic', 1100, ['Végé']],
                    ['Diavola', 'Tomate, mozzarella, spianata piquante', 1350, ['Épicé']],
                    ['Quattro formaggi', 'Mozzarella, gorgonzola, parmesan, chèvre', 1400, ['Végé']],
                    ['Regina', 'Tomate, jambon, champignons frais', 1300, []],
                ],
                'Antipasti' => [
                    ['Burrata crémeuse', 'Tomates anciennes, huile d\'olive', 950, ['Végé']],
                    ['Bruschetta', 'Tomates, ail, basilic', 700, ['Végé']],
                ],
                'Dolci' => [
                    ['Tiramisu', 'Recette de la maison', 650, ['Maison']],
                    ['Panna cotta', 'Coulis de fruits rouges', 550, []],
                ],
            ],
        ],
        'sushi' => [
            'label' => 'Sushi',
            'name' => 'Kumo',
            'line' => 'Bar à sushis',
            'colors' => ['#ff7f6e', '#1c2f4d', '#fff1ec'],
            'menu' => [
                'Makis' => [
                    ['California saumon', 'Saumon, avocat, sésame · 8 pièces', 790, []],
                    ['Maki concombre', 'Concombre, riz vinaigré · 6 pièces', 450, ['Végé']],
                    ['Spicy thon', 'Thon, sauce piquante · 6 pièces', 690, ['Épicé']],
                ],
                'Sushis' => [
                    ['Sushi saumon', '2 pièces', 480, []],
                    ['Sushi thon', '2 pièces', 550, []],
                ],
                'Plateaux' => [
                    ['Plateau mix', '20 pièces : makis, sushis, sashimis', 2290, []],
                    ['Chirashi saumon', 'Riz, saumon, avocat', 1650, []],
                ],
                'À côté' => [
                    ['Soupe miso', 'Tofu, algues, ciboule', 350, ['Végé']],
                    ['Gyoza poulet', '5 pièces', 690, []],
                ],
            ],
        ],
        'braise' => [
            'label' => 'Grill',
            'name' => 'Le Brasero',
            'line' => 'Cuisine à la braise',
            'colors' => ['#ff6a1a', '#2b1a14', '#fff1e4'],
            'menu' => [
                'Viandes' => [
                    ['Entrecôte 300 g', 'Frites maison, sauce au poivre', 2400, []],
                    ['Côte de bœuf 1 kg', 'Pour deux personnes', 6200, []],
                    ['Brochette de poulet', 'Marinade citron et paprika', 1650, ['Épicé']],
                ],
                'Poissons' => [
                    ['Dorade à la braise', 'Légumes grillés', 1950, []],
                    ['Gambas grillées', 'Beurre persillé', 2100, []],
                ],
                'Accompagnements' => [
                    ['Frites maison', 'Coupées au couteau', 400, ['Végé', 'Maison']],
                    ['Légumes grillés', 'Selon le marché', 500, ['Végé']],
                ],
            ],
        ],
        'creperie' => [
            'label' => 'Crêperie',
            'name' => 'Ker Avel',
            'line' => 'Galettes et crêpes',
            'colors' => ['#f2a93b', '#5a3218', '#fff6e0'],
            'menu' => [
                'Galettes' => [
                    ['Complète', 'Jambon, œuf, emmental', 950, []],
                    ['Forestière', 'Champignons, crème, lardons', 1100, []],
                    ['Chèvre miel', 'Chèvre, miel, noix, salade', 1050, ['Végé']],
                ],
                'Crêpes' => [
                    ['Beurre sucre', 'La classique', 400, ['Végé']],
                    ['Caramel beurre salé', 'Caramel fait maison', 550, ['Végé', 'Maison']],
                    ['Suzette', 'Flambée au Grand Marnier', 750, []],
                ],
                'Boissons' => [
                    ['Bolée de cidre', 'Brut ou doux', 350, []],
                ],
            ],
        ],
        'burger' => [
            'label' => 'Burger',
            'name' => 'Smash Club',
            'line' => 'Burgers smashés',
            'colors' => ['#ffc928', '#d7261e', '#fff8dc'],
            'menu' => [
                'Burgers' => [
                    ['Classic smash', 'Double steak, cheddar, oignons', 1190, []],
                    ['Spicy', 'Jalapeños, sauce piquante maison', 1290, ['Épicé']],
                    ['Veggie', 'Galette de pois chiches, avocat', 1250, ['Végé']],
                ],
                'À côté' => [
                    ['Frites', 'Sel de Guérande', 350, ['Végé']],
                    ['Onion rings', '8 pièces', 450, ['Végé']],
                ],
                'Milkshakes' => [
                    ['Vanille', 'Glace artisanale', 500, []],
                    ['Cacahuète caramel', 'Le préféré de la maison', 550, ['Maison']],
                ],
            ],
        ],
        'brunch' => [
            'label' => 'Brunch',
            'name' => 'Petit Matin',
            'line' => 'Café et brunch',
            'colors' => ['#5fc995', '#1e5b46', '#ecfff5'],
            'menu' => [
                'Brunch' => [
                    ['Avocado toast', 'Pain au levain, œuf poché', 1100, ['Végé']],
                    ['Pancakes', "Sirop d'érable, fruits frais", 900, ['Végé']],
                    ['Œufs Bénédicte', 'Jambon, sauce hollandaise', 1350, []],
                    ['Granola bowl', 'Yaourt, fruits, granola maison', 850, ['Végé', 'Maison']],
                ],
                'Boissons' => [
                    ['Latte', 'Lait entier ou avoine', 420, []],
                    ["Jus d'orange pressé", 'Pressé à la minute', 500, ['Végé']],
                ],
            ],
        ],
    ];
}
