<?php

namespace frontend\tests\functional;

use frontend\tests\FunctionalTester;
use common\fixtures\UserFixture;

class HomeCest
{
    /**
     * Load fixtures before db transaction begin
     */
    public function _fixtures()
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
        ];
    }

    public function checkHomeRedirectsToLoginWhenNotAuthenticated(FunctionalTester $I)
    {
        $I->wantTo('verificare che la homepage reindirizza al login quando non autenticato');
        $I->amOnRoute('site/index');
        $I->seeCurrentUrlEquals('/site/login');
        $I->see('Login');
    }

    public function checkHomeWorksAfterLogin(FunctionalTester $I)
    {
        $I->wantTo('verificare che la homepage funzioni dopo il login');
        
        // Effettua il login
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'erau',
            'LoginForm[password]' => 'password_0',
        ]);
        
        // Ora dovrebbe poter accedere alla homepage
        $I->amOnRoute('site/index');
        $I->dontSeeCurrentUrlEquals('/site/login');
        $I->see('My Application');
        $I->seeLink('About');
        
        // Verifica che possa navigare verso About
        $I->click('About');
        $I->see('This is the About page.');
    }
}