# Integrazione Calendario React in Yii2

Questa guida spiega come integrare il componente calendario React nelle view Yii2.

## 🚀 Setup Iniziale

### 1. Build dell'applicazione React

```bash
cd frontend/web/calendar-app
npm run build
```

Questo creerà la cartella `dist/` con i file compilati.

### 2. Configurazione Yii2

Assicurati che il web server serva i file statici dalla cartella `frontend/web/calendar-app/dist/`.

## 📋 Integrazione nelle View

### Opzione 1: View Dedicata (Consigliata)

Crea una view dedicata per il calendario:

```php
<?php
// views/calendar/index.php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Calendario Terapie';
?>

<div class="calendar-index">
    <!-- Container per React Calendar - SCHERMO INTERO -->
    <div id="calendar-root"></div>
</div>

<?php
// Configurazione per React
$calendarConfig = [
    'userRole' => Yii::$app->user->identity->role, // manager, therapist, admin
    'therapistId' => Yii::$app->user->identity->therapist_id ?? null,
    'apiBaseUrl' => Url::to(['/api']),
    'locale' => 'it'
];

// Passa configurazione a JavaScript
$this->registerJs("
    window.CALENDAR_CONFIG = " . Json::encode($calendarConfig) . ";
", \yii\web\View::POS_HEAD);

// Carica assets React
$this->registerJsFile('/calendar-app/dist/assets/index.js', [
    'type' => 'module',
    'position' => \yii\web\View::POS_END
]);

$this->registerCssFile('/calendar-app/dist/assets/index.css');
?>

<style>
/* CSS per integrazione schermo intero */
.calendar-index {
    margin: 0;
    padding: 0;
    width: 100%;
    height: calc(100vh - 60px); /* Sottrai altezza header Yii2 */
}

#calendar-root {
    width: 100%;
    height: 100%;
}

/* Rimuovi padding dal layout Yii2 se necessario */
.content-wrapper {
    padding: 0 !important;
}
</style>
```

### Opzione 2: Integrazione in Layout Esistente

```php
<?php
// views/dashboard/index.php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Dashboard';
?>

<div class="dashboard-index">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Calendario Appuntamenti</h3>
                </div>
                <div class="box-body" style="padding: 0;">
                    <!-- Container per React Calendar -->
                    <div id="calendar-root" style="height: 600px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Configurazione e assets come sopra...
?>
```

## 🎛️ Configurazione Parametri

### Parametri Disponibili

```javascript
window.CALENDAR_CONFIG = {
  userRole: "manager", // 'manager', 'therapist', 'admin'
  therapistId: 123, // ID terapista (solo se userRole = 'therapist')
  apiBaseUrl: "/api", // Base URL per le API
  locale: "it", // Lingua interfaccia
};
```

### Esempi per Diversi Ruoli

#### Manager (Sharon)

```php
$calendarConfig = [
    'userRole' => 'manager',
    'therapistId' => null,
    'apiBaseUrl' => Url::to(['/api']),
    'locale' => 'it'
];
```

#### Terapista

```php
$calendarConfig = [
    'userRole' => 'therapist',
    'therapistId' => Yii::$app->user->identity->therapist_id,
    'apiBaseUrl' => Url::to(['/api']),
    'locale' => 'it'
];
```

#### Amministratore

```php
$calendarConfig = [
    'userRole' => 'admin',
    'therapistId' => null,
    'apiBaseUrl' => Url::to(['/api']),
    'locale' => 'it'
];
```

## 🎨 Personalizzazione CSS

### CSS per Layout Yii2 Standard

```css
/* Rimuovi padding/margin dal container principale */
.content-wrapper {
  padding: 0 !important;
  margin: 0 !important;
}

/* Container calendario */
#calendar-root {
  width: 100%;
  height: calc(100vh - 120px); /* Sottrai header + breadcrumb */
  background: #f8fafc;
}

/* Per AdminLTE */
.main-footer {
  display: none; /* Nascondi footer se necessario */
}
```

### CSS per Layout con Sidebar

```css
/* Se hai una sidebar, aggiusta la larghezza */
.content-wrapper {
  margin-left: 230px; /* Larghezza sidebar */
}

#calendar-root {
  width: calc(100% - 230px);
  height: calc(100vh - 120px);
}
```

## 🔧 Controller Yii2

### Controller Base

```php
<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class CalendarController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Solo utenti autenticati
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // Verifica permessi utente
        $user = Yii::$app->user->identity;

        if (!in_array($user->role, ['manager', 'therapist', 'admin'])) {
            throw new \yii\web\ForbiddenHttpException('Accesso negato');
        }

        return $this->render('index');
    }

    public function actionManager()
    {
        // Solo per manager
        if (Yii::$app->user->identity->role !== 'manager') {
            throw new \yii\web\ForbiddenHttpException('Accesso negato');
        }

        return $this->render('manager');
    }

    public function actionTherapist()
    {
        // Solo per terapisti
        if (Yii::$app->user->identity->role !== 'therapist') {
            throw new \yii\web\ForbiddenHttpException('Accesso negato');
        }

        return $this->render('therapist');
    }
}
```

## 📱 Responsive Design

Il calendario è già ottimizzato per mobile. Per layout Yii2:

```css
/* Mobile responsive */
@media (max-width: 768px) {
  .content-wrapper {
    margin-left: 0 !important;
    padding: 0 !important;
  }

  #calendar-root {
    width: 100% !important;
    height: calc(100vh - 100px) !important;
  }

  /* Nascondi sidebar su mobile */
  .main-sidebar {
    display: none;
  }
}
```

## 🔄 Aggiornamenti

### Dopo modifiche al codice React:

1. **Build dell'app:**

   ```bash
   cd frontend/web/calendar-app
   npm run build
   ```

2. **Clear cache Yii2:**

   ```bash
   php yii cache/flush-all
   ```

3. **Refresh browser** (Ctrl+F5)

## 🐛 Troubleshooting

### Problema: Calendario non si carica

**Soluzione:**

1. Verifica che i file `dist/assets/index.js` e `dist/assets/index.css` esistano
2. Controlla la console browser per errori JavaScript
3. Verifica che `window.CALENDAR_CONFIG` sia definito

### Problema: API 404

**Soluzione:**

1. Verifica che il controller API sia configurato correttamente
2. Controlla le route in `config/web.php`
3. Verifica che `apiBaseUrl` sia corretto

### Problema: Layout rotto

**Soluzione:**

1. Aggiungi CSS personalizzato per il tuo layout
2. Verifica che non ci siano conflitti CSS
3. Usa `!important` se necessario per sovrascrivere stili Yii2

## 📚 Esempi Completi

Vedi il file `public/yii2-integration-example.html` per esempi completi di integrazione.

## 🔐 Sicurezza

- ✅ Autenticazione gestita da Yii2
- ✅ Autorizzazione basata su ruoli
- ✅ CSRF protection nelle API
- ✅ Validazione input lato server
- ✅ Rate limiting sulle API
