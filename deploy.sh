#!/usr/bin/env bash
# Déploiement / mise à jour sur un hébergement mutualisé (alwaysdata).
# Usage, depuis le dossier du projet sur le serveur :  bash deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

echo "→ Récupération du code"
git pull --ff-only

echo "→ Dépendances PHP (sans outils de développement)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Base de données"
php artisan migrate --force

echo "→ Lien public vers les couvertures"
[ -L public/storage ] || php artisan storage:link

echo "→ Caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ Redémarrage des workers éventuels"
php artisan queue:restart

echo "✓ Déploiement terminé"
