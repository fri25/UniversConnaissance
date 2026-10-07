# Univers Connaissance

> Le savoir à portée de clic.

Librairie en ligne d'e-books (PDF / EPUB uniquement) en français : catalogue, fiche produit, tunnel d'achat avec paiement Mobile Money (Chariow), bibliothèque « Mes achats » et back-office.

**Stack** : Laravel 11 · Blade · Tailwind CSS 3 · Alpine.js (Vite) · Breeze · SQLite (dev) / MySQL (prod) · file d'attente `database`.

---

## 1. Installation (développement)

Prérequis : PHP 8.2+ (extensions `pdo_sqlite` ou `pdo_mysql`, `gd`, `zip`, `mbstring`, `fileinfo`), Composer 2, Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # Windows : type nul > database\database.sqlite
php artisan migrate --seed              # 12 e-books de démo, 5 catégories, comptes de test
php artisan storage:link                # expose storage/app/public (couvertures uniquement)
```

Lancer l'application (3 terminaux) :

```bash
php artisan serve        # http://127.0.0.1:8000
npm run dev              # Vite (ou `npm run build` une fois)
php artisan queue:work   # envoie les emails de livraison
```

### Comptes de démonstration

| Rôle   | Email                              | Mot de passe |
|--------|------------------------------------|--------------|
| Admin  | `admin@universconnaissance.test`   | `password`   |
| Client | `client@universconnaissance.test`  | `password`   |

Le client possède déjà deux e-books. En local, `PAYMENT_GATEWAY=fake` remplace la page Chariow par une **page de paiement simulée** (nom, email, téléphone, puis « Payer » ou « Simuler un échec ») : tout le parcours sans connexion (paiement → compte créé → email → téléchargement filigrané) fonctionne sans aucune clé. Les emails sont écrits dans `storage/logs/laravel.log` (`MAIL_MAILER=log`).

### Tests et style

```bash
php artisan test         # 86 tests Feature (SQLite en mémoire)
./vendor/bin/pint        # PSR-12 / style Laravel
```

---

## 2. Configuration Chariow

Le paiement passe par [Chariow](https://chariow.dev) (Mobile Money, carte bancaire). **Le client n'a pas besoin de se connecter** : le bouton « Acheter » l'envoie directement sur la page de paiement Chariow du produit, où il saisit nom, email et téléphone.

1. Dans le tableau de bord Chariow, créez un produit par e-book, **au même prix en FCFA** que sur le site.
   ⚠️ N'y joignez pas le fichier complet (ou mettez une simple note « Votre e-book arrive par email de la part d'Univers Connaissance ») : sinon Chariow livrerait lui-même le fichier, sans filigrane ni limite de téléchargements.
2. Pour chaque e-book, dans **Admin > E-books > Modifier**, renseignez :
   - **ID du produit Chariow** (`prd_…`) : sert à reconnaître le livre quand la vente est notifiée ;
   - **Lien de la page de paiement Chariow** (`https://…`) : destination du bouton « Acheter ».
   Sans ces deux champs, le livre affiche « Bientôt disponible » (badge « Non lié à Chariow » dans l'admin). Colonnes `chariow_product_id` / `chariow_product_url` dans l'import CSV.
3. Créez un **Pulse** (Automatisations > Pulses) :
   - URL : `https://votre-domaine/webhooks/payment`
   - Événements : `successful.sale` (indispensable), `failed.sale`, `abandoned.sale`
4. Facultatif : si Chariow permet une redirection après achat, indiquez `https://votre-domaine/merci?sale={sale_id}` (sinon le client reste sur la page de remerciement Chariow et reçoit notre email).
5. Dans `.env`, puis `php artisan config:clear` :
   ```dotenv
   PAYMENT_GATEWAY=chariow
   CHARIOW_PULSE_SECRET=whsec_xxx
   ```

Fonctionnement :

- `GET /acheter/{slug}` redirige vers le lien Chariow du livre (aucune commande n'est créée avant le paiement). Un client connecté qui possède déjà le livre est envoyé vers « Mes achats ».
- Le **Pulse** `successful.sale` crée tout : signature `x-chariow-signature: sha256=HMAC-SHA256(corps brut, secret)` vérifiée → livre retrouvé par l'ID produit → client retrouvé **par email** (ou compte « invité » créé automatiquement) → commande payée + droit de téléchargement → email de livraison avec lien de téléchargement **utilisable sans connexion** et, pour un nouveau client, lien « Créer mon mot de passe ».
- Traitement **idempotent** : la référence de vente Chariow est unique en base ; les renvois de Chariow (jusqu'à 5) ne créent ni doublon ni second email. Un écart entre le prix Chariow et celui du site est journalisé.
- Les échecs et abandons ne créent rien.

**Changer de prestataire** : implémentez `App\Payments\Contracts\PaymentGateway` (`checkoutUrl`, `bookFor`, `parseWebhook`), puis ajoutez-le dans `App\Payments\PaymentManager` et `config/payment.php`.

---

## 2 bis. Pixel Meta

**Admin → Réglages** : identifiant du pixel et, recommandé, jeton de l'API Conversions (stocké chiffré). Événements : `PageView`, `ViewContent` (fiche e-book), `Search`, `InitiateCheckout` (clic « Acheter »), `Purchase` (page Merci + **envoi serveur** à la confirmation Chariow, même `event_id` pour la déduplication). Le pixel navigateur n'est chargé qu'après acceptation du bandeau cookies ; jamais dans l'admin. Valeurs par défaut possibles via `META_PIXEL_ID` / `META_CAPI_TOKEN` dans `.env`.

---

## 3. Emails (SMTP)

Renseignez `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`. L'email de livraison (`App\Mail\EbookDelivered`) est mis en file : **un worker doit tourner** (`php artisan queue:work`, sous Supervisor/systemd en production). L'admin peut le renvoyer depuis la fiche commande.

---

## 4. Déploiement (production)

> Hébergement gratuit pas à pas : voir **[DEPLOY-ALWAYSDATA.md](DEPLOY-ALWAYSDATA.md)**. Mise à jour sur le serveur : `bash deploy.sh`.

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Check-list :

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine` (utilisé pour signer les liens de téléchargement), HTTPS obligatoire, `SESSION_SECURE_COOKIE=true`.
- MySQL : `DB_CONNECTION=mysql` + identifiants.
- `PAYMENT_GATEWAY=chariow` + clés Chariow, chaque e-book actif lié à son produit Chariow (le prestataire simulé est refusé en production).
- Emails de livraison : soit un worker permanent (`php artisan queue:work` sous Supervisor), soit, sur un mutualisé, une tâche cron `php artisan schedule:run` chaque minute (elle vide la file d'attente).
- Le document root doit être `public/`. Les fichiers complets et extraits sont dans `storage/app/private` : **jamais** servis par URL publique, uniquement via la route protégée.
- Ne pas lancer `db:seed` en production (comptes de démo avec mot de passe `password`).

---

## 5. Architecture

| Élément | Emplacement |
|---|---|
| Routes | `routes/web.php` |
| Paiement (interface, Chariow, simulateur) | `app/Payments` |
| Confirmation idempotente, livraison | `app/Services/OrderPaymentService.php` |
| Liens signés, quota | `app/Services/DownloadService.php` |
| Filigrane PDF (FPDI) | `app/Services/PdfWatermarker.php` |
| Policies (achat, téléchargement, avis) | `app/Policies` |
| FormRequests | `app/Http/Requests` |
| Back-office | `app/Http/Controllers/Admin`, `resources/views/admin` |
| Couleurs / thème | `tailwind.config.js` (échelle `brand` dérivée de #10BAF1), `resources/css/app.css` |

**Sécurité des téléchargements** : URL signée et temporaire (72 h) **+** jeton secret non expiré **+** commande payée **+** quota (`EBOOK_MAX_DOWNLOADS`, incrément atomique). Le lien reçu par email fonctionne sans connexion (achat sans compte) ; un utilisateur connecté avec un autre compte est refusé, et depuis « Mes achats » un lien neuf est régénéré à la demande. Les PDF sont filigranés à la volée (nom, email, n° de commande) ; si un PDF n'est pas importable par FPDI (compression objet PDF ≥ 1.5), il est servi sans filigrane et l'erreur est journalisée — voir « Limites ».

**Rate limiting** : connexion (Breeze, 5 essais), checkout (10/min), webhook (120/min), téléchargements (10/min), avis (5/min).

### Accessibilité des couleurs

Le texte blanc sur le bleu ciel (#10BAF1) n'atteint pas 4,5:1 : les boutons utilisent donc un texte foncé #0B1F2A, sur `brand-400` #38C7F4 pour `btn-primary` (≈ 8,6:1) et sur `brand-500` #10BAF1 pour `btn-solid` (≈ 7,5:1). Les liens restent en `brand-700` sur fond clair (≈ 5,1:1).

### Limites connues

- Le filigrane utilise FPDI gratuit, qui ne lit pas les PDF à flux d'objets compressés (PDF 1.5+). Pour les fichiers d'éditeurs, prévoir l'add-on commercial *FPDI PDF-Parser* ou une conversion préalable (`qpdf --object-streams=disable`, Ghostscript).
- Les EPUB ne sont pas filigranés.
- Laravel 11 n'est plus maintenu : `composer audit` signale des avis de sécurité corrigés uniquement en 12.x/13.x (dont un sur les URL signées temporaires). Le téléchargement ne repose pas uniquement sur la signature, mais une migration vers Laravel 12 est recommandée.
