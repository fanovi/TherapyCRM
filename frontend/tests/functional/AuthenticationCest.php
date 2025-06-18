<?php

namespace frontend\tests\functional;

use frontend\tests\FunctionalTester;

class AuthenticationCest
{
    public function checkHomepageRequiresLogin(FunctionalTester $I)
    {
        $I->wantTo('verificare che la homepage richieda il login');
        $I->amOnRoute('site/index');
        $I->seeCurrentUrlEquals('/site/login');
        $I->see('Login');
    }

    public function checkAboutPageRequiresLogin(FunctionalTester $I)
    {
        $I->wantTo('verificare che la pagina About richieda il login');
        $I->amOnRoute('site/about');
        $I->seeCurrentUrlEquals('/site/login');
        $I->see('Login');
    }

    public function checkContactPageRequiresLogin(FunctionalTester $I)
    {
        $I->wantTo('verificare che la pagina Contact richieda il login');
        $I->amOnRoute('site/contact');
        $I->seeCurrentUrlEquals('/site/login');
        $I->see('Login');
    }

    public function checkLoginPageIsPublic(FunctionalTester $I)
    {
        $I->wantTo('verificare che la pagina di login sia accessibile senza autenticazione');
        $I->amOnRoute('site/login');
        $I->see('Login');
        $I->dontSeeCurrentUrlEquals('/site/login?redirect=');
    }

    public function checkSignupPageIsPublic(FunctionalTester $I)
    {
        $I->wantTo('verificare che la pagina di registrazione sia accessibile senza autenticazione');
        $I->amOnRoute('site/signup');
        $I->see('Signup');
        $I->dontSeeCurrentUrlEquals('/site/login');
    }

    public function checkErrorPageIsPublic(FunctionalTester $I)
    {
        $I->wantTo('verificare che la pagina di errore sia accessibile senza autenticazione');
        $I->amOnRoute('site/error');
        $I->dontSeeCurrentUrlEquals('/site/login');
    }

    public function checkPasswordResetIsPublic(FunctionalTester $I)
    {
        $I->wantTo('verificare che le pagine di reset password siano accessibili senza autenticazione');
        $I->amOnRoute('site/request-password-reset');
        $I->dontSeeCurrentUrlEquals('/site/login');
    }
} 