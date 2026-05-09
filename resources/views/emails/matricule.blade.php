<!DOCTYPE html>
<html>
<head>
    <title>Votre Matricule Orizon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .matricule { background: #fef3c7; padding: 20px; border-left: 5px solid #f59e0b; margin: 20px 0; font-size: 24px; font-weight: bold; text-align: center; border-radius: 5px; }
        .content { padding: 30px 20px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; border-top: 1px solid #eee; }
        .btn { background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Bienvenue sur Orizon !</h1>
        <p>Félicitations {{ $user->first_name }}, votre compte Orizon est prêt !</p>
    </div>

    <div class="content">
        <h2>Votre Matricule Officiel</h2>
        <div class="matricule">
            {{ $matricule }}
        </div>
        
        <p><strong>Ce matricule est unique et servira à :</strong></p>
        <ul>
            <li>Identifier vos biens immobiliers</li>
            <li>Effectuer des transactions sécurisées</li>
            <li>Accéder à votre tableau de bord</li>
        </ul>

        <p>Connectez-vous dès maintenant :</p>
        <a href="{{ env('APP_URL', 'http://localhost:8000') }}/login" class="btn">Accéder à Orizon</a>
    </div>

    <div class="footer">
        <p>👋 Orizon Immobilier | La référence immobilière</p>
        <p>Vous avez reçu cet email car votre compte a été créé/validé.</p>
    </div>
</body>
</html>

