<?php

/**
 * @file plugins/themes/journalPulse/tests/KeywordsTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class KeywordsTest
 *
 * @brief Keywords reach the templates as plain strings, whether OJS returns
 *        them as strings (3.5.0-3) or as ['name' => ...] entries (3.5.0.5).
 */

namespace APP\plugins\themes\journalPulse\tests;

use APP\plugins\themes\journalPulse\JournalPulseThemePlugin;
use APP\publication\Publication;
use PKP\tests\PKPTestCase;

class KeywordsTest extends PKPTestCase
{
    public function testKeywordsAreStringsWhateverShapeOjsReturns(): void
    {
        $cases = [
            'strings (OJS 3.5.0-3)' => [['aa', 'asd'], ['aa', 'asd']],
            'entries (OJS 3.5.0.5)' => [[['name' => 'TSH'], ['name' => 'LDL-C']], ['TSH', 'LDL-C']],
            'no keywords' => [null, []],
            'empty keywords are left out' => [['', ['name' => ''], 'x'], ['x']],
        ];
        foreach ($cases as $label => [$returned, $expected]) {
            $publication = $this->createMock(Publication::class);
            $publication->method('getLocalizedData')->with('keywords')->willReturn($returned);
            $this->assertSame($expected, JournalPulseThemePlugin::getKeywords($publication), $label);
        }
    }
}
