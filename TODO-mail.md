# Configuration Email Matricule Owner - Étapes

## Status: Configurer pour envoi réel

### 1. [ ] Local dev (LOG emails)
```
# Ajoutez dans .env
MAIL_MAILER=log
```
Test register → Voir email dans storage/logs/laravel.log

### 2. [ ] Gmail PROD (App Password)
1. Activez 2FA Google Account
2. https://myaccount.google.com/apppasswords → "Mail" app → Generate
3. .env:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@gmail.com
MAIL_PASSWORD=16chars-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre@gmail.com
MAIL_FROM_NAME="Orizon - Matricule"
```
4. `php artisan config:clear && php artisan config:cache`

### 3. [ ] Test
```
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

### 4. [x] Option Mailtrap/SendGrid (gratuit)
- Mailtrap: signup → SMTP credentials → .env
- `php artisan queue:work` si QUEUE_CONNECTION=database

**Current: Étape 1 (local log)**
