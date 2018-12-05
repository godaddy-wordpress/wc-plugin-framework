<?php


class PluginActionLinksCest {

    public function _before( AcceptanceTester $I ) {
    	$I->loginAsAdmin();
    	$I->amOnPluginsPage();
    }

    public function _after( AcceptanceTester $I ) {

    }

    public function try_docs_link( AcceptanceTester $I ) {

		$I->wantTo( 'See the Docs action link' );
		$I->see( 'Docs' );
    }


	public function try_configure_link( AcceptanceTester $I ) {

    	$I->wantTo( 'See the Configure action link' );
		$I->see( 'Configure' );
	}


	public function try_configure_url( AcceptanceTester $I ) {

		$I->wantTo( 'Click the Configure action link and go to the settings page' );
		$I->click( 'Configure' );
		$I->canSeeInCurrentUrl( 'wc-settings' );
	}
}
