<?php

/**
 * @file plugins/themes/journalMetrics/tests/JournalInfoTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalInfoTest
 *
 * @brief The key figures of the journal information section (frequency,
 *        acceptance rate, review speed) are shown as a large number and the
 *        words after it.
 */

namespace APP\plugins\themes\journalMetrics\tests;

use APP\plugins\themes\journalMetrics\JournalPulseThemePlugin;
use PKP\tests\PKPTestCase;

class JournalInfoTest extends PKPTestCase
{
    public function testFiguresAreSplitIntoNumberAndUnit(): void
    {
        $cases = [
            '90 days' => ['90', 'days'],
            '40%' => ['40%', ''],
            '40 %' => ['40 %', ''],
            '4 issues per year' => ['4', 'issues per year'],
            '2-3 months' => ['2-3', 'months'],
            '1.5 months' => ['1.5', 'months'],
            '٩٠ يوماً' => ['٩٠', 'يوماً'],
            'Quarterly' => ['', 'Quarterly'],
        ];
        foreach ($cases as $value => [$figure, $unit]) {
            $this->assertSame(['figure' => $figure, 'unit' => $unit], JournalPulseThemePlugin::splitFigure($value), $value);
        }
    }
}
