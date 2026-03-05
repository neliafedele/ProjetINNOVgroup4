# Projet INNOV — RAK Canteen PWA

Application web/PWA de cantine avec :
- sondage de présence,
- feedbacks étudiants,
- vote des menus,
- vue personnel (KPI + feedbacks + recettes populaires).

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

- http://10.129.195.25:8000/rak-canteen-pwa.html

3. Si la page ne s'ouvre pas :

- vérifier que le Mac et le téléphone sont sur le même réseau,
- autoriser PHP dans le pare-feu macOS si demandé.

## Identifiants de démo

- Étudiant : `etudiant` / `rak2025`
- Personnel : `personnel` / `rak2025`

## Checklist de test rapide

- Ouvrir l’app via serveur local (pas en double-clic fichier).
- Vérifier l’installation PWA (menu navigateur → Installer l’application).
- En mode étudiant : envoyer présence, feedback, et vote menu.
- Recharger puis passer en mode personnel : vérifier KPI, feedbacks et recettes.
- Tester hors-ligne : couper internet puis recharger (service worker/cache).
- Si la version ne se met pas à jour : vider données du site/service worker puis recharger.