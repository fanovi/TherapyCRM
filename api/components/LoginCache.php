<?php

namespace api\components;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\AccountPatient;

/**
 * Componente per il caching ottimizzato dei dati di login
 */
class LoginCache extends Component
{
    /**
     * Durata cache in secondi (default: 15 minuti)
     */
    public $cacheDuration = 900;
    
    /**
     * Prefisso per le chiavi di cache
     */
    public $cachePrefix = 'login_';
    
    /**
     * Ottiene i dati utente dalla cache o dal database
     */
    public function getUserData($email)
    {
        $cacheKey = $this->cachePrefix . 'user_' . md5($email);
        
        $userData = Yii::$app->cache->get($cacheKey);
        
        if ($userData === false) {
            // Dati non in cache, recupera dal database
            $user = User::find()
                ->with('profile')
                ->where(['email' => $email])
                ->andWhere(['status' => User::STATUS_ACTIVE])
                ->one();
            
            if (!$user) {
                return null;
            }
            
            $userData = [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'password_hash' => $user->password_hash,
                    'status' => $user->status,
                    'auth_key' => $user->auth_key
                ],
                'profile' => $user->profile ? [
                    'id' => $user->profile->id,
                    'first_name' => $user->profile->first_name,
                    'last_name' => $user->profile->last_name,
                    'fiscal_code' => $user->profile->fiscal_code,
                    'phone' => $user->profile->phone,
                    'address' => $user->profile->address
                ] : null
            ];
            
            // Salva in cache con tag dependency per invalidazione
            Yii::$app->cache->set(
                $cacheKey, 
                $userData, 
                $this->cacheDuration,
                new TagDependency(['tags' => ['user_' . $user->id]])
            );
            
            Yii::info("User data cached for email: $email", __METHOD__);
        }
        
        return $userData;
    }
    
    /**
     * Ottiene i dati del terapista dalla cache
     */
    public function getTherapistData($userId)
    {
        $cacheKey = $this->cachePrefix . 'therapist_' . $userId;
        
        $therapistData = Yii::$app->cache->get($cacheKey);
        
        if ($therapistData === false) {
            $therapist = Therapist::find()
                ->with(['specialization', 'specializations'])
                ->where(['user_id' => $userId])
                ->one();

            if (!$therapist) {
                return null;
            }

            $specializationsList = [];
            foreach ($therapist->specializations as $s) {
                $specializationsList[] = [
                    'id' => (int)$s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                ];
            }

            $therapistData = [
                'id' => $therapist->id,
                'user_id' => $therapist->user_id,
                'specialization_id' => $therapist->specialization_id,
                'weekly_hours_contract' => $therapist->weekly_hours_contract,
                'calendar_color' => $therapist->calendar_color,
                'is_active' => $therapist->is_active,
                'specialization' => $therapist->specialization ? [
                    'id' => $therapist->specialization->id,
                    'name' => $therapist->specialization->name
                ] : null,
                // Lista N specializzazioni del terapista (la primary è anche
                // riflessa nel campo "specialization" sopra per backward-compat).
                'specializations' => $specializationsList,
            ];
            
            Yii::$app->cache->set(
                $cacheKey, 
                $therapistData, 
                $this->cacheDuration,
                new TagDependency(['tags' => ['therapist_' . $userId, 'user_' . $userId]])
            );
        }
        
        return $therapistData;
    }
    
    /**
     * Ottiene i dati dell'account paziente dalla cache
     */
    public function getAccountPatientData($userId)
    {
        $cacheKey = $this->cachePrefix . 'account_patient_' . $userId;
        
        $accountData = Yii::$app->cache->get($cacheKey);
        
        if ($accountData === false) {
            $accountPatients = AccountPatient::find()
                ->with('patient')
                ->where(['user_id' => $userId])
                ->all();
            
            if (empty($accountPatients)) {
                return null;
            }
            
            $patients = [];
            foreach ($accountPatients as $ap) {
                if ($ap->patient) {
                    $patients[] = [
                        'account_patient_id' => $ap->id,
                        'patient_id' => $ap->patient->id,
                        'patient_name' => $ap->patient->first_name . ' ' . $ap->patient->last_name,
                        'relationship' => $ap->relationship_type ?? 'self',
                        'has_parental_authority' => (bool) $ap->has_parental_authority
                    ];
                }
            }
            
            $accountData = [
                'user_id' => $userId,
                'patients' => $patients
            ];
            
            // Tag con tutti i pazienti per invalidazione
            $tags = ['account_patient_' . $userId, 'user_' . $userId];
            foreach ($patients as $patient) {
                $tags[] = 'patient_' . $patient['patient_id'];
            }
            
            Yii::$app->cache->set(
                $cacheKey, 
                $accountData, 
                $this->cacheDuration,
                new TagDependency(['tags' => $tags])
            );
        }
        
        return $accountData;
    }
    
    /**
     * Invalida la cache per un utente specifico
     */
    public function invalidateUserCache($userId)
    {
        TagDependency::invalidate(Yii::$app->cache, ['user_' . $userId]);
        Yii::info("Cache invalidated for user: $userId", __METHOD__);
    }
    
    /**
     * Invalida la cache per un terapista
     */
    public function invalidateTherapistCache($userId)
    {
        TagDependency::invalidate(Yii::$app->cache, ['therapist_' . $userId]);
        Yii::info("Cache invalidated for therapist: $userId", __METHOD__);
    }
    
    /**
     * Invalida la cache per un account paziente
     */
    public function invalidateAccountPatientCache($userId)
    {
        TagDependency::invalidate(Yii::$app->cache, ['account_patient_' . $userId]);
        Yii::info("Cache invalidated for account patient: $userId", __METHOD__);
    }
    
    /**
     * Pulisce tutta la cache di login
     */
    public function clearAll()
    {
        $keys = Yii::$app->cache->get($this->cachePrefix . 'keys') ?: [];
        foreach ($keys as $key) {
            Yii::$app->cache->delete($key);
        }
        Yii::$app->cache->delete($this->cachePrefix . 'keys');
        
        Yii::info("All login cache cleared", __METHOD__);
    }
}