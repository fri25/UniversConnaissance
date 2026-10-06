# Mettre Univers Connaissance en ligne sur alwaysdata (offre gratuite)

Temps estimé : 30 à 45 minutes. Aucune carte bancaire n'est demandée.

> **À savoir avant de commencer**
> - L'offre gratuite d'alwaysdata est prévue pour un **usage personnel** : parfait pour une démo ou des tests. Pour vendre réellement, passez à une offre payante (même interface, un clic) ou à un autre hébergeur.
> - L'espace disque gratuit est limité. Le projet occupe environ **41 Mo** (code + dépendances) : surveillez la jauge dans l'administration et videz le cache de Composer après installation (étape 5).
>
> Dans ce guide, remplacez **`moncompte`** par le nom de compte choisi à l'inscription.

---

## 1. Créer le compte

1. Inscrivez-vous sur [alwaysdata.com](https://www.alwaysdata.com/fr/) et choisissez l'offre **Free**.
2. Le nom du compte devient votre adresse : `https://moncompte.alwaysdata.net`.

## 2. Régler la version de PHP

Administration → **Environnement** (ou *Web → Configuration → Langages*) → **PHP 8.3** (minimum 8.2).

## 3. Créer la base MySQL

Administration → **Bases de données → MySQL** :

1. Créez une base, par exemple `moncompte_uc`.
2. Créez un utilisateur MySQL avec un mot de passe solide et donnez-lui tous les droits sur cette base.
3. Notez l'hôte : `mysql-moncompte.alwaysdata.net`.

## 4. Activer l'accès SSH

Administration → **Accès distant → SSH** : activez la connexion par mot de passe pour votre utilisateur.

Puis, depuis votre ordinateur (PowerShell ou Git Bash) :

```bash
ssh moncompte@ssh-moncompte.alwaysdata.net
```

Tapez votre mot de passe (rien ne s'affiche pendant la saisie, c'est normal). Vous êtes connecté quand l'invite ressemble à :

```
moncompte@ssh1:~$
```

> ⚠️ **Toutes les commandes des étapes 5, 6 et 11 se tapent dans cette fenêtre SSH**, jamais dans un PowerShell ouvert sur votre ordinateur. Si l'invite commence par `PS C:\…`, vous êtes encore sur votre PC : `nano` y est inconnu, et `cp .env.example .env` y écraserait votre `.env` local.

## 5. Installer le projet sur le serveur

```bash
cd ~
git clone https://github.com/fri25/UniversConnaissance.git univers-connaissance
cd univers-connaissance

composer install --no-dev --optimize-autoloader --no-interaction
composer clear-cache                # libère de l'espace disque

cp .env.example .env
php artisan key:generate
nano .env                           # voir l'étape 6
```

> Les fichiers CSS/JS sont déjà compilés dans le dépôt (`public/build`) : inutile d'installer Node sur le serveur.

## 6. Configurer le fichier `.env`

Modifiez au minimum ces lignes avec `nano .env` : déplacez-vous avec les flèches, puis `Ctrl+O` et `Entrée` pour enregistrer, `Ctrl+X` pour quitter.

> Vous préférez un éditeur graphique ? Connectez-vous en **SFTP** avec FileZilla ou WinSCP (hôte `ssh-moncompte.alwaysdata.net`, port 22, mêmes identifiants que SSH), ouvrez `univers-connaissance/.env`, modifiez-le puis enregistrez. Activez l'affichage des fichiers cachés, car `.env` commence par un point.

```dotenv
APP_NAME="Univers Connaissance"
# "demo" = site public avec paiement simulé possible ; "production" quand Chariow est configuré
APP_ENV=demo
APP_DEBUG=false
APP_URL=https://moncompte.alwaysdata.net

DB_CONNECTION=mysql
DB_HOST=mysql-moncompte.alwaysdata.net
DB_PORT=3306
DB_DATABASE=moncompte_uc
DB_USERNAME=moncompte_xxx
DB_PASSWORD=votre_mot_de_passe

SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database

# Emails : voir l'étape 9 (laisser "log" en attendant)
MAIL_MAILER=log

# Paiement : "fake" pour la démo, "chariow" pour encaisser (voir l'étape 10)
PAYMENT_GATEWAY=fake
```

Puis initialisez la base :

```bash
php artisan migrate --force
php artisan storage:link

# Démo uniquement : 12 e-books + comptes de test (mot de passe "password")
php artisan db:seed --force

bash deploy.sh   # met en cache la configuration ; à relancer après chaque mise à jour
```

> ⚠️ Si le site sert à de vraies ventes, **ne lancez pas `db:seed`** : créez votre compte via « Rejoindre », puis passez-le administrateur :
> `php artisan tinker --execute="App\Models\User::where('email','vous@exemple.com')->update(['is_admin'=>true]);"`

## 7. Pointer le site vers le dossier `public`

Administration → **Web → Sites** → modifiez le site `moncompte.alwaysdata.net` :

- **Type** : PHP
- **Répertoire racine** : `/univers-connaissance/public`
- Enregistrez, puis ouvrez `https://moncompte.alwaysdata.net`.

Le HTTPS est fourni automatiquement.

## 8. Tâche planifiée (emails de livraison)

Les emails partent via une file d'attente traitée par le planificateur Laravel.

Administration → **Avancé → Tâches planifiées** → *Ajouter* :

- **Type** : commande
- **Commande** : `cd $HOME/univers-connaissance && php artisan schedule:run`
- **Fréquence** : `* * * * *` (chaque minute)

Si la fréquence d'une minute est refusée, prenez la plus courte proposée : les emails partiront simplement avec quelques minutes de délai.

## 9. Emails (SMTP alwaysdata)

1. Administration → **Emails → Adresses** : créez par exemple `noreply@moncompte.alwaysdata.net` (ou une adresse sur votre propre domaine).
2. Dans `.env` :

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_HOST=smtp-moncompte.alwaysdata.net
   MAIL_PORT=587
   MAIL_USERNAME=noreply@moncompte.alwaysdata.net
   MAIL_PASSWORD=mot_de_passe_de_la_boite
   MAIL_FROM_ADDRESS="noreply@moncompte.alwaysdata.net"
   ```

3. `bash deploy.sh`, puis faites un achat de test : l'email doit arriver dans la minute.

## 10. Brancher Chariow (paiements réels)

Suivez la section « Configuration Chariow » du `README.md`. Spécifique à alwaysdata :

- URL du Pulse : `https://moncompte.alwaysdata.net/webhooks/payment`
- Dans `.env` : `PAYMENT_GATEWAY=chariow`, `CHARIOW_API_KEY`, `CHARIOW_PULSE_SECRET`, puis `APP_ENV=production`
- `bash deploy.sh`

## 11. Mettre à jour le site

Sur votre ordinateur : `git push`. Puis sur le serveur :

```bash
ssh moncompte@ssh-moncompte.alwaysdata.net
cd ~/univers-connaissance && bash deploy.sh
```

> Si vous modifiez le CSS ou les vues, lancez `npm run build` sur votre ordinateur **avant** le commit, pour que `public/build` soit à jour.

---

## En cas de problème

| Symptôme | Piste |
|---|---|
| Page blanche ou erreur 500 | `tail -n 50 ~/univers-connaissance/storage/logs/laravel.log` |
| Page « Forbidden » ou liste de fichiers | La racine du site n'est pas `/univers-connaissance/public` (étape 7) |
| Pas de style (CSS) | `public/build` absent : faites `npm run build` en local, puis commit et push |
| Couvertures invisibles | `php artisan storage:link` |
| Emails non reçus | Vérifiez la tâche planifiée (étape 8), puis `php artisan queue:work --stop-when-empty` pour tester |
| `nano` non reconnu / erreur PowerShell | Vous êtes sur votre PC : connectez-vous d’abord en SSH (étape 4) |
| Modification du `.env` sans effet | `bash deploy.sh` (la configuration est mise en cache) |
| Disque plein | `composer clear-cache` ; supprimez les vieux logs dans `storage/logs` |
