<?php

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Qubit
 */
class QubitRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        sfConfig::set('app_siteBaseUrl', 'https://atom.example.test');
    }

    /*
     * Relative application targets should pass through unchanged when they are
     * already local and do not contain any disallowed characters.
     */
    public function testFilterRedirectTargetAllowsInternalRoute()
    {
        $target = 'actor/1';

        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetAllowsRootRelativeUrl()
    {
        $target = '/index.php/actor/1?sortDir=asc&sort=lastUpdated';

        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    /*
     * Absolute URLs are allowed only when they match the configured site base
     * URL. Matching values are returned unchanged; mismatches are rejected.
     */
    public function testFilterRedirectTargetAllowsSameOriginAbsoluteUrl()
    {
        $target = 'https://atom.example.test/index.php/actor/1?sortDir=asc&sort=lastUpdated';

        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetRejectsExternalAbsoluteUrl()
    {
        $target = 'https://external.example.test/index.php/actor/1';

        $this->assertNull(Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetRejectsAbsoluteUrlWhenConfiguredBaseUrlIsMissing()
    {
        $target = 'https://atom.example.test/index.php/actor/1';
        sfConfig::set('app_siteBaseUrl', null);

        $this->assertNull(Qubit::filterRedirectTarget($target));
    }

    /*
     * Scheme-based and protocol-relative targets are never safe redirect
     * values because they can resolve outside the local application.
     */
    public function testFilterRedirectTargetRejectsUnsafeSchemesAndProtocolRelativeUrls()
    {
        $schemeTarget = 'javascript:alert(1)';
        $protocolRelativeTarget = '//external.example.test/path';

        $this->assertNull(Qubit::filterRedirectTarget($schemeTarget));
        $this->assertNull(Qubit::filterRedirectTarget($protocolRelativeTarget));
    }

    /*
     * Permissive slug characters supported by routing should remain valid when
     * they appear inside same-site absolute URLs.
     */
    public function testFilterRedirectTargetAllowsSameOriginAbsoluteUrlWithPermissiveUnicodeSlug()
    {
        $target = 'https://atom.example.test/index.php/Tést-SLÜG';

        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetAllowsSameOriginAbsoluteUrlWithPermissivePunctuationSlug()
    {
        $target = 'https://atom.example.test/index.php/a_-~:,=*@b';

        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetAllowsSameOriginAbsoluteUrlWithColonInPermissiveSlug()
    {
        $target = 'https://atom.example.test/index.php/abc:def';

        // Verify that a colon inside a same-origin slug is preserved as part of
        // the allowed redirect target rather than being treated as an external URI scheme.
        $this->assertSame($target, Qubit::filterRedirectTarget($target));
    }

    /*
     * Malformed values are rejected early. Empty strings and control characters
     * are invalid, while query-only and fragment-only values are preserved.
     */
    public function testFilterRedirectTargetRejectsControlCharacters()
    {
        $target = "actor/1\nLocation: https://external.example.test";

        $this->assertNull(Qubit::filterRedirectTarget($target));
    }

    public function testFilterRedirectTargetAllowsQueryOnlyAndFragmentOnlyTargets()
    {
        $this->assertNull(Qubit::filterRedirectTarget(''));
        $this->assertSame('?sortDir=asc&sort=lastUpdated', Qubit::filterRedirectTarget('?sortDir=asc&sort=lastUpdated'));
        $this->assertSame('#content', Qubit::filterRedirectTarget('#content'));
    }
}
