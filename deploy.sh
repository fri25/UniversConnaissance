#!/usr/bin/env bash
# Déploiement / mise à jour sur un hébergement mutualisé (alwaysdata).
# Usage, depuis le dossier du projet sur le serveur :  bash deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

echo "→ Récupération du code"
git pull --ff-only

echo "→ Dépendances PHP (sans outils de développement)"
composer install --no-dev --optimize-autoloader --no-interaction

# Vider les caches AVANT de toucher à la base : sinon artisan utiliserait une
# configuration mémorisée (anciens identifiants) au lieu du .env actuel.
echo "→ Lecture du .env actuel"
php artisan optimize:clear

echo "→ Vérification de la connexion à la base de données"
if ! php artisan db:show --no-interaction > /dev/null 2>&1; then
    echo "✗ Connexion à la base impossible avec les valeurs DB_* du fichier .env."
    echo "  Vérifiez DB_HOST, DB_DATABASE, DB_USERNAME et DB_PASSWORD"
    echo "  (mot de passe avec # \$ ou espace : entourez-le de guillemets simples)."
    echo "  Détail de l'erreur :"
    php artisan db:show --no-interaction 2>&1 | grep -m1 "SQLSTATE" | sed 's/^ *//' || true
    exit 1
fi

echo "→ Base de données"
php artisan migrate --force

echo "→ Lien public vers les couvertures"
[ -L public/storage ] || php artisan storage:link

echo "→ Caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ Redémarrage des workers éventuels"
php artisan queue:restart

echo "✓ Déploiement terminé"
