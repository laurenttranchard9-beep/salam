# Site test : Salam

Landing page de démonstration en français pour **Salam**, une application fictive qui retient les notifications Slack, Teams et Gmail pendant les plages de concentration et les livre en un seul résumé.

Construite avec le skill `landing-page-design` (installé dans `.claude/skills`). Aucune dépendance, aucun build.

## Lancer en local

```sh
cd site
python3 -m http.server 8000
```

Puis ouvrir http://localhost:8000.

## Fichiers

| Fichier | Rôle |
|---|---|
| `index.html` | La page, avec son CSS et son JavaScript intégrés |
| `legal.html` | Confidentialité et conditions d’utilisation |
| `404.html` | Page d’erreur personnalisée |
| `favicon.svg`, `og.png` | Icône et image de partage |
| `fonts/` | Geist et Geist Mono, hébergées localement (licence OFL) |

Les formulaires sont simulés : rien n’est envoyé ni enregistré. La page est en `noindex` car la marque, les chiffres et les témoignages sont inventés.
