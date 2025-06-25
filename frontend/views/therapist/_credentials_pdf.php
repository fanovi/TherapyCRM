<?php
/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $password string */
/* @var $generatedAt string */

use yii\helpers\Html;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Credenziali di Accesso Terapista - TherapyCRM</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
            border-bottom: 3px solid #465fff;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #465fff;
        }
        .header h2 {
            margin: 10px 0 0 0;
            font-size: 24px;
            color: #666;
        }
        .header h3 {
            margin: 5px 0 0 0;
            font-size: 18px;
            color: #888;
            font-weight: normal;
        }
        .credentials { 
            border: 2px solid #465fff; 
            padding: 25px; 
            margin: 30px 0; 
            background-color: #f8f9ff;
            border-radius: 8px;
        }
        .credentials h3 {
            margin-top: 0;
            color: #465fff;
            font-size: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .field { 
            margin: 20px 0; 
            padding: 12px;
            border-bottom: 1px dotted #ccc;
        }
        .field:last-child {
            border-bottom: none;
        }
        .label { 
            font-weight: bold; 
            color: #333;
            display: inline-block;
            width: 160px;
            font-size: 14px;
        }
        .value {
            font-size: 16px;
            color: #000;
            font-weight: 600;
        }
        .instructions {
            background-color: #e8f4fd;
            border: 1px solid #b8e0ff;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .instructions h4 {
            margin-top: 0;
            color: #0066cc;
            font-size: 18px;
        }
        .instructions ol {
            margin: 15px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
            font-size: 14px;
        }
        .footer { 
            margin-top: 50px; 
            font-size: 12px; 
            color: #666; 
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .footer p {
            margin: 5px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 30px 0;
            text-align: center;
        }
        .warning p {
            margin: 0;
            font-weight: bold;
            color: #856404;
        }
        .therapist-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TherapyCRM</h1>
        <h2>Credenziali di Accesso</h2>
        <div class="therapist-badge">🩺 ACCOUNT TERAPISTA</div>
    </div>
    
    <div class="credentials">
        <h3>Dati Account Terapista</h3>
        <div class="field">
            <span class="label">Nome:</span>
            <span class="value"><?= Html::encode($user->profile ? $user->profile->first_name . ' ' . $user->profile->last_name : 'N/A') ?></span>
        </div>
        <div class="field">
            <span class="label">Email/Username:</span>
            <span class="value"><?= Html::encode($user->email) ?></span>
        </div>
        <div class="field">
            <span class="label">Password:</span>
            <span class="value"><?= Html::encode($password) ?></span>
        </div>
        <div class="field">
            <span class="label">Ruolo:</span>
            <span class="value">Terapista</span>
        </div>
    </div>
    
    <div class="warning">
        <p>🔐 PRIMO ACCESSO OBBLIGATORIO: Questa password è temporanea e DEVE essere cambiata al primo login</p>
    </div>
    
    <div class="instructions">
        <h4>Istruzioni per l'accesso (Terapista):</h4>
        <ol>
            <li>Scarica l'app mobile "TherapyCRM" dal tuo store</li>
            <li>Inserisci i dati di accesso sopra riportati</li>
            <li><strong>Al primo accesso dovrai obbligatoriamente cambiare la password</strong></li>
            <li>Scegli una nuova password sicura (minimo 8 caratteri)</li>
            <li>L'app ti guiderà automaticamente nel processo di cambio password</li>
            <li>Conserva le nuove credenziali in luogo sicuro</li>
            <li>Come terapista avrai accesso a:</li>
            <ul>
                <li>Calendario dei tuoi appuntamenti</li>
                <li>Dati dei pazienti a te assegnati</li>
                <li>Sistema di notifiche</li>
                <li>Gestione delle tue sessioni terapeutiche</li>
            </ul>
            <li>Contatta l'amministratore in caso di problemi di accesso</li>
        </ol>
    </div>
    
    <div class="warning">
        <p>⚠️ SICUREZZA: Tutti i token di accesso precedenti sono stati revocati. Non condividere mai questi dati con terzi.</p>
    </div>
    
    <div class="footer">
        <p>Documento generato il: <?= Html::encode($generatedAt) ?></p>
        <p>© TherapyCRM - Sistema di Gestione Terapeutica</p>
        <p>Questo documento contiene informazioni riservate - Account Terapista</p>
    </div>
</body>
</html> 