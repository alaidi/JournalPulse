<?php

/**
 * @file plugins/themes/journalPulse/tests/WorkflowStagesTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowStagesTest
 *
 * @brief The terms of the editorial workflow line are measured from the
 *        journal's own editorial decisions.
 */

namespace APP\plugins\themes\journalPulse\tests;

use APP\plugins\themes\journalPulse\JournalPulseThemePlugin;
use PKP\decision\Decision;
use PKP\tests\PKPTestCase;

class WorkflowStagesTest extends PKPTestCase
{
    public function testHistoryIsSplitIntoStages(): void
    {
        $spans = JournalPulseThemePlugin::stageSpans('2026-01-01 10:00:00', [
            [Decision::EXTERNAL_REVIEW, '2026-01-05 12:00:00'],
            [Decision::RECOMMEND_ACCEPT, '2026-01-20 12:00:00'], // a recommendation does not move the manuscript
            [Decision::PENDING_REVISIONS, '2026-02-04 14:00:00'],
            [Decision::PENDING_REVISIONS, '2026-02-05 14:00:00'], // asked again: still the same revision
            [Decision::ACCEPT, '2026-03-01 16:00:00'],
            [Decision::SEND_TO_PRODUCTION, '2026-03-01 16:05:00'],
        ], '2026-04-01');
        $this->assertSame([[0, 4], [1, 30], [2, 25], [3, 0], [4, 30]], self::days($spans));

        $declined = JournalPulseThemePlugin::stageSpans('2026-01-01 10:00:00', [
            [Decision::INITIAL_DECLINE, '2026-01-03 12:00:00'],
        ], null);
        $this->assertSame([[0, 2]], self::days($declined));

        $inReview = JournalPulseThemePlugin::stageSpans('2026-01-01 10:00:00', [
            [Decision::EXTERNAL_REVIEW, '2026-01-02 12:00:00'],
        ], null);
        $this->assertSame([[0, 1], [1, null]], self::days($inReview));
    }

    public function testTermIsTheDayBy80PercentOfManuscriptsHadLeftTheStage(): void
    {
        $spans = [];
        foreach (range(1, 10) as $days) {
            $spans[] = [1, 0, $days * 86400 + 3600]; // left the review on day 2 ... day 11
        }
        $spans[] = [1, 0, null]; // still in review: not counted
        $spans[] = [2, 0, 5 * 86400]; // a single revision is too few to measure

        $terms = JournalPulseThemePlugin::typicalStageDays($spans);
        $this->assertCount(5, $terms);
        $this->assertSame(9, $terms[1]);
        $this->assertSame(JournalPulseThemePlugin::STAGE_DAYS[0], $terms[0]);
        $this->assertSame(JournalPulseThemePlugin::STAGE_DAYS[2], $terms[2]);
    }

    /** [[segment, whole days or null while in it], ...] */
    private static function days(array $spans): array
    {
        return array_map(
            fn (array $span) => [$span[0], $span[2] === null ? null : intdiv($span[2] - $span[1], 86400)],
            $spans
        );
    }
}
