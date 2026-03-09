# Projet INNOV — CROUS Canteen PWA

Application web/PWA de cantine avec :
- sondage de présence,
- feedbacks étudiants (note globale + note par plat),
- vote des menus (1 choix midi + 1 choix soir, au moins 1 choix),
- classement hebdomadaire des participations,
- vue personnel (KPI + feedbacks + recettes populaires + dashboard feedback avec diagrammes).

## Prérequis

- macOS, Linux ou Windows
- PHP 8+ installé (`php -v`)
- `make` installé

Si PHP n'est pas installé sur macOS :

```bash
brew install php
```

## Lancer le projet (méthode recommandée)

Depuis la racine du projet :

```bash
make run
```

Puis ouvre :

- http://localhost:8000/rak-canteen-pwa.html

Pour arrêter le serveur :

- `Ctrl + C` dans le terminal

## Autres commandes utiles

Afficher l'aide :

```bash
make help
```

Lancer sur un autre port :

```bash
make run PORT=8080
```

Lancer l'ancienne page formulaire (`index.php`) :

```bash
make run-index
```

## Nouvelles fonctionnalités

- Branding CROUS (interface, manifest, pages de formulaire).
- Couleurs de présence plus contrastées (midi / soir / les deux).
- Classement des participations visible côté étudiant et personnel.
- Classement hebdomadaire (total = présence + vote menu + feedback de la semaine en cours).
- Côté étudiant : affichage Top 10 du classement.
- Côté personnel : vue feedback ergonomique avec KPI + barres + camembert de répartition des notes.
- Remise à zéro depuis l'interface personnel : feedbacks + votes menu (session) + classement.
- Notification locale du dimanche pour annoncer le nouveau sondage (si permission accordée).

## Brancher les plats sur une API externe

Le menu hebdomadaire est chargé via `Fichier racines/menu-source.php`.

1. Ouvrir `Fichier racines/menu-source-config.json`
2. Mettre l'URL source dans `remoteMenuUrl` (exemple: `https://services.imt-atlantique.fr/rak/pagemenu.php`)

Formats supportés par `menu-source.php` :

- JSON direct (`weekLabel` + `dishes`)
- HTML (page source) parsée automatiquement en plats

Exemple JSON supporté :

```json
{
	"weekLabel": "Semaine du 16 mars",
	"dishes": [
		{"emoji": "🍛", "name": "Cari poulet", "desc": "Riz et legumes", "meal": "midi", "type": "non-vege", "day": "lundi"}
	]
}
```

Si l'URL externe est vide, invalide, ou indisponible, l'app utilise automatiquement `Fichier racines/menu-week.json`.

Comportement semaine :

- Feedback et notation des plats: semaine en cours (`?week=current`)
- Vote menu et présence: semaine suivante (`?week=next`)

Affichage des plats :

- Regroupement par service (`Midi` / `Soir`)
- Puis par jour
- Puis par catégorie (`Plats non vege` / `Plats vege`)

## Notifications du dimanche

Une notification locale est affichée le dimanche pour les étudiants connectés :

- titre : `Nouveau sondage CROUS`
- fréquence : 1 fois par semaine et par appareil (anti-doublon via `localStorage`)

Limite actuelle :

- la notification est déclenchée quand l'app est ouverte le dimanche (ce n'est pas un push serveur).

## Lancer sans Makefile (manuel)

```bash
php -S localhost:8000 -t "Fichier racines"
```

Puis ouvre :

- http://localhost:8000/rak-canteen-pwa.html

## Test sur téléphone (même Wi‑Fi)

1. Lance le serveur en mode réseau local (LAN) :

```bash
make run-lan
```

2. Depuis le téléphone (connecté au même Wi‑Fi), ouvre :

- http://10.129.195.25:8000/rak-canteen-pwa.html #adapter en fonction de l'IP de votre ordinateur

3. Si la page ne s'ouvre pas :

- vérifier que le Mac et le téléphone sont sur le même réseau,
- autoriser PHP dans le pare-feu macOS si demandé.

## Identifiants de démo

- Étudiant : `etudiant` / `rak2025`
- Personnel : `personnel` / `rak2025`

## Endpoints utiles

- `feedback.php` : lecture et enregistrement des feedbacks.
- `participation.php` : suivi des participations hebdomadaires.
- `reset-stats.php` : remise à zéro des présences (staff).
- `reset-feedbacks.php` : remise à zéro feedbacks + classement (staff).

## Checklist de test rapide

- Ouvrir l’app via serveur local (pas en double-clic fichier).
- Vérifier l’installation PWA (menu navigateur → Installer l’application).
- En mode étudiant : envoyer présence.
- En mode étudiant : envoyer un feedback global.
- En mode étudiant : noter des plats (optionnel, pas obligatoire pour tous).
- En mode étudiant : voter menu sur au moins un service (midi et/ou soir).
- Recharger puis passer en mode personnel : vérifier KPI, feedbacks, recettes et classement.
- Vérifier que les listes plats sont bien organisées par jour dans chaque onglet `Midi/Soir`.
- Vérifier la vue feedback staff : KPI + barres + camembert.
- Vérifier le classement : total hebdomadaire et Top 10 côté étudiant.
- Tester hors-ligne : couper internet puis recharger (service worker/cache).
- Si la version ne se met pas à jour : vider données du site/service worker puis recharger.