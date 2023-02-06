<?php

/**
 * @covers PagerWhosOnline
 * @group Database
 */
class PagerWhosOnlineTest extends MediaWikiIntegrationTestCase {

	public function testGetNavigationBar() {
		$pager = new PagerWhosOnline();
		$html = $pager->getNavigationBar();

		// @phpcs:ignore Generic.Files.LineLength.TooLong
		$this->assertStringStartsWith( '<div class="mw-pager-navigation-bar">View (<span class="mw-prevlink">previous', $html );

		preg_match_all( '!<a.*?</a>!', $html, $m, PREG_PATTERN_ORDER );
		$links = $m[0];

		$nums = [ 20, 100, 250, 500 ];
		$i = 0;
		foreach ( $links as $a ) {
			$this->assertStringContainsString( 'Special:WhosOnline', $a );
			$this->assertStringContainsString( "limit=$nums[$i]&amp;offset=", $a );
			$this->assertStringContainsString( 'class="mw-numlink"', $a );
			$this->assertStringContainsString( ">$nums[$i]<", $a );
			$i += 1;
		}
	}
}
