<?php

/**
 * @covers PagerWhosOnline
 * @group Database
 */
class PagerWhosOnlineTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetNavigationBar() {
		$pager = new PagerWhosOnline();
		$html = $pager->getNavigationBar();


		$this->assertStringStartsWith( 'View (previous', $html );

		preg_match_all( '!<a.*?</a>!', $html, $m, PREG_PATTERN_ORDER );
		$links = $m[0];

		$nums= [20, 50, 100, 250, 500];
		$i = 0;
		foreach ( $links as $a ) {
			$this->assertContains( 'Special:WhosOnline', $a );
			$this->assertContains( "limit=$nums[$i]&amp;offset=", $a );
			$this->assertContains('class="mw-numlink"', $a);
			$this->assertContains(">$nums[$i]<", $a);
			$i+=1;
		}
	}
}


