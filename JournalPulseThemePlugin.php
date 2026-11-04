<?php

/**
 * @file plugins/themes/journalPulse/JournalPulseThemePlugin.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalPulseThemePlugin
 *
 * @brief Child theme of the OJS 3.5 default theme. It adds a numbers-first
 *  home page (usage statistics, editorial workflow, list of the latest issue),
 *  a usage block on article pages and search-engine markup
 *  (canonical links, schema.org JSON-LD).
 */

namespace APP\plugins\themes\journalPulse;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector as IssueCollector;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Collector as SubmissionCollector;
use APP\submission\Submission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PKP\core\PKPApplication;
use PKP\core\PKPSessionGuard;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\plugins\Hook;
use PKP\plugins\ThemePlugin;
use PKP\decision\Collector as DecisionCollector;
use PKP\decision\Decision;
use PKP\security\Role;

class JournalPulseThemePlugin extends ThemePlugin
{
    /** Default accent colour (passes WCAG AA contrast with white text) */
    public const DEFAULT_ACCENT = '#C2410C';

    /** Seconds to keep per-article usage numbers in the cache */
    public const STATS_CACHE_TTL = 3600;

    /** Seconds to keep the current editorial stage in the cache */
    public const STAGE_CACHE_TTL = 600;

    /** Seconds to keep the measured terms of the workflow stages in the cache */
    public const STAGE_DAYS_CACHE_TTL = 86400;

    /**
     * Term of each segment of the workflow line, in calendar days, for the
     * stages the journal has too little history to measure (see getStageDays())
     */
    public const STAGE_DAYS = [14, 90, 30, 20, 20];

    /**
     * Segment of the workflow line that a decision moves the manuscript into
     * (null: out of the line). Recommendations and the rare decisions whose
     * target depends on the history (revert a decline, back from copyediting,
     * cancel a review round) leave it where it is.
     */
    private const DECISION_STAGE = [
        Decision::REVERT_INITIAL_DECLINE => 0,
        Decision::EXTERNAL_REVIEW => 1,
        Decision::NEW_EXTERNAL_ROUND => 1,
        Decision::PENDING_REVISIONS => 2,
        Decision::RESUBMIT => 2,
        Decision::ACCEPT => 3,
        Decision::SKIP_EXTERNAL_REVIEW => 3,
        Decision::BACK_FROM_PRODUCTION => 3,
        Decision::SEND_TO_PRODUCTION => 4,
        Decision::DECLINE => null,
        Decision::INITIAL_DECLINE => null,
    ];

    /**
     * Journal information fields filled in the theme options:
     * option => [label, rich text, description of the field (optional)]
     */
    public const INFO_OPTIONS = [
        'infoIndexing' => ['plugins.themes.journalPulse.info.indexing', true, 'plugins.themes.journalPulse.option.info.onePerLine'],
        'infoFrequency' => ['plugins.themes.journalPulse.info.frequency', false],
        'infoPublicationDates' => ['plugins.themes.journalPulse.info.publicationDates', true, 'plugins.themes.journalPulse.option.info.onePerLine'],
        'infoEditorInChief' => ['plugins.themes.journalPulse.info.editorInChief', true],
        'infoManagingEditor' => ['plugins.themes.journalPulse.info.managingEditor', true],
        'infoOrganizedBy' => ['plugins.themes.journalPulse.info.organizedBy', false],
        'infoCitationAnalysis' => ['plugins.themes.journalPulse.info.citationAnalysis', true, 'plugins.themes.journalPulse.option.info.onePerLine'],
        'infoAcceptanceRate' => ['plugins.themes.journalPulse.info.acceptanceRate', false],
        'infoReviewSpeed' => ['plugins.themes.journalPulse.info.reviewSpeed', false],
        'infoSubmissionNote' => ['plugins.themes.journalPulse.info.submissionNote', true],
        'infoDownloads' => ['plugins.themes.journalPulse.info.downloads', true, 'plugins.themes.journalPulse.option.info.downloads'],
        'infoVideoGuides' => ['plugins.themes.journalPulse.info.videoGuides', true, 'plugins.themes.journalPulse.option.info.videoGuides'],
    ];

    /**
     * @copydoc ThemePlugin::isActive()
     */
    public function isActive()
    {
        if (PKPSessionGuard::isSessionDisable()) {
            return true;
        }
        return parent::isActive();
    }

    /**
     * Initialize the theme's styles, scripts and hooks. This is run on the
     * currently active theme and its parent themes.
     */
    public function init()
    {
        $this->setParent('defaultthemeplugin');

        // Theme options (Settings > Website > Appearance)
        $this->addOption('accentColour', 'FieldColor', [
            'label' => __('plugins.themes.journalPulse.option.accent.label'),
            'description' => __('plugins.themes.journalPulse.option.accent.description'),
            'default' => self::DEFAULT_ACCENT,
        ]);
        $this->addOption('heroTagline', 'FieldText', [
            'label' => __('plugins.themes.journalPulse.option.tagline.label'),
            'description' => __('plugins.themes.journalPulse.option.tagline.description'),
            'isMultilingual' => true,
            'default' => '',
        ]);
        // OJS only records that a journal is open access, not which model, so the journal chooses
        $this->addOption('openAccessModel', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.journalPulse.option.openAccess.label'),
            'description' => __('plugins.themes.journalPulse.option.openAccess.description'),
            'options' => [
                ['value' => 'open', 'label' => __('plugins.themes.journalPulse.eyebrow.open')],
                ['value' => 'gold', 'label' => __('plugins.themes.journalPulse.eyebrow.gold')],
                ['value' => 'diamond', 'label' => __('plugins.themes.journalPulse.eyebrow.diamond')],
            ],
            'default' => 'open',
        ]);
        $this->addOption('showHeroStats', 'FieldOptions', [
            'label' => __('plugins.themes.journalPulse.option.heroStats.label'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.journalPulse.option.heroStats.option'),
                ],
            ],
            'default' => true,
        ]);
        $this->addOption('showWorkflowStrip', 'FieldOptions', [
            'label' => __('plugins.themes.journalPulse.option.workflow.label'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.journalPulse.option.workflow.option'),
                ],
            ],
            'default' => true,
        ]);

        $this->addOption('showWorkflowCurrent', 'FieldOptions', [
            'label' => __('plugins.themes.journalPulse.option.workflowCurrent.label'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.journalPulse.option.workflowCurrent.option'),
                ],
            ],
            'default' => true,
        ]);

        $this->addOption('showJournalInfo', 'FieldOptions', [
            'label' => __('plugins.themes.journalPulse.option.info.label'),
            'description' => __('plugins.themes.journalPulse.option.info.description'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.journalPulse.option.info.option'),
                ],
            ],
            'default' => true,
        ]);
        foreach (self::INFO_OPTIONS as $name => $field) {
            $this->addOption($name, $field[1] ? 'FieldRichTextarea' : 'FieldText', [
                'label' => __('plugins.themes.journalPulse.option.info.field', ['field' => __($field[0])]),
                'isMultilingual' => true,
                'default' => '',
            ] + (isset($field[2]) ? ['description' => __($field[2])] : []));
        }

        // Re-skin the parent theme through its LESS variables, so that every
        // page (login, search, archive, forms) follows the new look.
        $this->removeStyle('font');
        $accent = $this->getAccentColour();
        $this->appendParentLessVariables(join("\n", [
            '@bg-base: #FFFFFF;',
            '@text-bg-base: #141414;',
            '@bg-base-border-color: #E4E4E4;',
            '@bg-border-color: #E4E4E4;',
            '@text: rgba(20,20,20,0.92);',
            '@text-light: rgba(20,20,20,0.68);',
            '@primary: #141414;',
            '@primary-lift: ' . $accent . ';',
            '@font: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;',
            '@font-heading: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;',
            '@screen-lg-desktop: 1320px;',
            '@screen-lg-desktop-container: 1280px;',
        ]));

        // Accent colour and the theme stylesheet (fonts are imported in the stylesheet)
        $this->addStyle('journalPulseAccent', ':root{--jm-accent:' . $accent . ';}', ['inline' => true]);
        // OJS versions stylesheet URLs with its own version (?v=3.5.0.3), which stays the same when the
        // theme is upgraded, so browsers would keep the old stylesheet with the new templates
        $this->addStyle('journalPulse', 'styles/journalPulse.css?v=' . filemtime(__DIR__ . '/styles/journalPulse.css'));

        // Search-engine markup (canonical links, JSON-LD)
        Hook::add('TemplateManager::display', $this->onTemplateDisplay(...));
    }

    /**
     * Moves journals over from the theme's former name; see JournalPulseMigration.
     */
    public function getInstallMigration()
    {
        return new JournalPulseMigration();
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.themes.journalPulse.name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.themes.journalPulse.description');
    }

    //
    // Methods used from Smarty templates through $activeTheme
    //

    /**
     * Usage numbers for one article: abstract-page views, PDF views and HTML views.
     * Results are cached for an hour, because they are read for every article
     * in the issue list on the home page.
     */
    public function getArticleStats(int $submissionId): array
    {
        $empty = ['views' => 0, 'pdf' => 0, 'html' => 0];
        $contextId = Application::get()->getRequest()->getContext()?->getId();
        if (!$contextId) {
            return $empty;
        }
        return Cache::remember(
            "jm_stats_{$contextId}_{$submissionId}",
            self::STATS_CACHE_TTL,
            function () use ($submissionId, $contextId, $empty): array {
                try {
                    $totals = app('publicationStats')->getTotalsByType($submissionId, $contextId, null, null);
                    return [
                        'views' => (int) ($totals['abstract'] ?? 0),
                        'pdf' => (int) ($totals['pdf'] ?? 0),
                        'html' => (int) ($totals['html'] ?? 0),
                    ];
                } catch (\Throwable $e) {
                    return $empty;
                }
            }
        );
    }

    /**
     * The keywords of a publication as plain strings. OJS 3.5.0-3 returns each
     * keyword as a string and 3.5.0.5 as ['name' => ...], which a template
     * printing it directly shows as "Array".
     */
    public static function getKeywords(Publication $publication): array
    {
        $keywords = [];
        foreach ($publication->getLocalizedData('keywords') ?? [] as $keyword) {
            $name = (string) (is_array($keyword) ? ($keyword['name'] ?? '') : $keyword);
            if ($name !== '') {
                $keywords[] = $name;
            }
        }
        return $keywords;
    }

    /**
     * Usage numbers for all articles of an issue ($publishedSubmissions is the
     * list of sections assigned by the issue handler), plus issue totals and
     * the percentage of each article relative to the largest value.
     */
    public function getIssueStats($publishedSubmissions): array
    {
        $rows = [];
        $views = $pdf = $count = $max = 0;
        $firstPage = null;
        $lastPage = null;
        foreach ((array) $publishedSubmissions as $section) {
            foreach (($section['articles'] ?? []) as $submission) {
                $pages = (string) $submission->getCurrentPublication()?->getData('pages');
                if (preg_match('/^\s*(\d+)\s*[-–—]\s*(\d+)\s*$/u', $pages, $m)) {
                    $firstPage = $firstPage === null ? (int) $m[1] : min($firstPage, (int) $m[1]);
                    $lastPage = $lastPage === null ? (int) $m[2] : max($lastPage, (int) $m[2]);
                }
                $stats = $this->getArticleStats((int) $submission->getId());
                $rows[$submission->getId()] = $stats;
                $views += $stats['views'];
                $pdf += $stats['pdf'];
                $max = max($max, $stats['views'], $stats['pdf']);
                $count++;
            }
        }
        foreach ($rows as &$row) {
            $row['viewsPct'] = $max > 0 ? round($row['views'] / $max * 100, 1) : 0;
            $row['pdfPct'] = $max > 0 ? round($row['pdf'] / $max * 100, 1) : 0;
        }
        unset($row);

        $pageCount = ($firstPage !== null && $lastPage >= $firstPage) ? $lastPage - $firstPage + 1 : 0;

        return [
            'articles' => $rows,
            'views' => $views,
            'pdf' => $pdf,
            'count' => $count,
            'max' => $max,
            'pages' => $pageCount,
            'firstPage' => $firstPage,
            'lastPage' => $lastPage,
        ];
    }

    /**
     * The most read articles of the latest published issues. Returns plain
     * arrays (title, authors, url, pdfUrl, views, pdf), cached for an hour.
     */
    public function getMostRead(int $limit = 3, int $issuesCount = 2): array
    {
        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return [];
        }
        $contextId = (int) $context->getId();
        $locale = Locale::getLocale();

        return Cache::remember(
            "jm_mostread_{$contextId}_{$locale}_{$limit}_{$issuesCount}",
            self::STATS_CACHE_TTL,
            function () use ($contextId, $limit, $issuesCount): array {
                try {
                    $request = Application::get()->getRequest();
                    $dispatcher = $request->getDispatcher();
                    $issues = Repo::issue()->getCollector()
                        ->filterByContextIds([$contextId])
                        ->filterByPublished(true)
                        ->orderBy(IssueCollector::ORDERBY_DATE_PUBLISHED)
                        ->limit($issuesCount)
                        ->getMany();

                    $rows = [];
                    foreach ($issues as $issue) {
                        $submissions = Repo::submission()->getCollector()
                            ->filterByContextIds([$contextId])
                            ->filterByIssueIds([$issue->getId()])
                            ->filterByStatus([Submission::STATUS_PUBLISHED])
                            ->getMany();
                        foreach ($submissions as $submission) {
                            $publication = $submission->getCurrentPublication();
                            if (!$publication) {
                                continue;
                            }
                            $stats = $this->getArticleStats((int) $submission->getId());
                            $path = $publication->getData('urlPath') ?: $submission->getId();
                            $authors = [];
                            foreach ($publication->getData('authors') ?? [] as $author) {
                                $authors[] = $author->getFullName();
                            }
                            $pdfUrl = null;
                            foreach ($publication->getData('galleys') ?? [] as $galley) {
                                if ($galley->isPdfGalley()) {
                                    $pdfUrl = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', [$path, $galley->getBestGalleyId()]);
                                    break;
                                }
                            }
                            $rows[] = [
                                'title' => trim(strip_tags((string) $publication->getLocalizedTitle(null, 'text'))),
                                'authors' => join(', ', $authors),
                                'issue' => $issue->getIssueIdentification(),
                                'url' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', [$path]),
                                'pdfUrl' => $pdfUrl,
                                'views' => $stats['views'],
                                'pdf' => $stats['pdf'],
                            ];
                        }
                    }
                    usort($rows, fn ($a, $b) => [$b['views'], $b['pdf']] <=> [$a['views'], $a['pdf']]);
                    return array_slice(array_values(array_filter($rows, fn ($r) => $r['views'] > 0)), 0, $limit);
                } catch (\Throwable $e) {
                    return [];
                }
            }
        );
    }

    /**
     * Editorial stage shown on the workflow line.
     *
     * For a logged-in author it is the stage of their own latest submission
     * that is still in progress. For everybody else it is the stage of the most
     * recently submitted manuscript in progress; only the stage is public,
     * never the title or the author.
     *
     * @return array|null ['index' => 0..4, 'key' => string, 'mine' => bool]; the
     *  index follows the five segments of the workflow strip: screening,
     *  review, revision, copyediting, proofreading.
     */
    public function getCurrentStage(): ?array
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()?->getId();
        if (!$contextId) {
            return null;
        }

        // The author's own submission first (not cached: it depends on the user)
        $user = PKPSessionGuard::isSessionDisable() ? null : $request->getUser();
        if ($user) {
            $own = $this->findStage((int) $contextId, [(int) $user->getId()]);
            if ($own) {
                return $own + ['mine' => true];
            }
        }

        $stage = Cache::remember(
            "jm_stage_{$contextId}",
            self::STAGE_CACHE_TTL,
            fn (): array => $this->findStage((int) $contextId, null) ?? []
        );
        return $stage ? $stage + ['mine' => false] : null;
    }

    /**
     * Stage of the newest complete submission in progress, optionally of the
     * given authors only.
     *
     * @param int[]|null $authorIds
     */
    private function findStage(int $contextId, ?array $authorIds): ?array
    {
        try {
            $collector = Repo::submission()->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByStatus([Submission::STATUS_QUEUED, Submission::STATUS_SCHEDULED])
                ->orderBy(SubmissionCollector::ORDERBY_DATE_SUBMITTED)
                ->limit(30);
            if ($authorIds) {
                $collector->assignedTo($authorIds, [Role::ROLE_ID_AUTHOR]);
            }
            foreach ($collector->getMany() as $submission) {
                // Drafts that the author has not finished sending are not "in progress".
                // filterByIncomplete(false) does not exclude them, so check here.
                if ((string) $submission->getData('submissionProgress') !== '') {
                    continue;
                }
                $stage = $this->resolveStage($submission);
                return $stage ? $stage + $this->stageProgress($submission, $stage['index']) : null;
            }
        } catch (\Throwable $e) {
            // no marker is better than a broken page
        }
        return null;
    }

    /**
     * Where the manuscript is within the typical term of its current stage.
     *
     * The stage starts at the submission date (screening) or at the date of the
     * latest editorial decision (sending to review, requesting revisions,
     * acceptance, sending to production).
     *
     * @return array ['day' => int, 'total' => int, 'percent' => int (1..100), 'over' => bool]
     */
    private function stageProgress(Submission $submission, int $index): array
    {
        $total = $this->getStageDays()[$index] ?? 30;
        $start = $submission->getData('dateSubmitted');
        if ($index > 0) {
            try {
                $decision = Repo::decision()->getCollector()
                    ->filterBySubmissionIds([$submission->getId()])
                    ->orderBy(DecisionCollector::ORDERBY_DATE_DECIDED, DecisionCollector::ORDER_DIR_DESC)
                    ->getMany()
                    ->first();
                if ($decision && $decision->getData('dateDecided')) {
                    $start = $decision->getData('dateDecided');
                }
            } catch (\Throwable $e) {
                // fall back to the submission date
            }
        }
        $startTime = $start ? strtotime((string) $start) : false;
        $day = $startTime ? self::dayNumber($startTime, time()) : 1;
        // Same numbers as the "Day {day} of {total}" label: day 2 of 2 is 100%, not 50%
        $percent = (int) round($day / $total * 100);
        return [
            'day' => $day,
            'total' => $total,
            'percent' => min(100, max(1, $percent)),
            'over' => $day > $total,
        ];
    }

    /**
     * Term of each segment of the workflow line, in days, measured from the
     * journal's own manuscripts of the last two years: the day of the stage by
     * which 80% of them had left it (the share OJS uses for its "days to first
     * decision"). Recalculated once a day.
     *
     * @return int[] five numbers, in the order of the workflow line
     */
    public function getStageDays(): array
    {
        $contextId = Application::get()->getRequest()->getContext()?->getId();
        if (!$contextId) {
            return self::STAGE_DAYS;
        }
        return Cache::remember(
            "jm_stage_days_{$contextId}",
            self::STAGE_DAYS_CACHE_TTL,
            function () use ($contextId): array {
                try {
                    return self::typicalStageDays($this->journalStageSpans((int) $contextId));
                } catch (\Throwable $e) {
                    return self::STAGE_DAYS;
                }
            }
        );
    }

    /**
     * Stage spans of the journal's manuscripts submitted in the last two years
     *
     * @return array[] see stageSpans()
     */
    private function journalStageSpans(int $contextId): array
    {
        $decisions = DB::table('submissions as s')
            ->join('edit_decisions as d', 'd.submission_id', '=', 's.submission_id')
            ->where('s.context_id', $contextId)
            ->where('s.date_submitted', '>=', date('Y-m-d', strtotime('-2 years')))
            ->orderBy('d.date_decided')
            ->orderBy('d.edit_decision_id')
            ->get(['s.submission_id', 's.date_submitted', 'd.decision', 'd.date_decided'])
            ->groupBy('submission_id');
        $published = DB::table('publications as p')
            ->join('submissions as s', 's.submission_id', '=', 'p.submission_id')
            ->where('s.context_id', $contextId)
            ->where('p.status', Submission::STATUS_PUBLISHED)
            ->groupBy('p.submission_id')
            ->selectRaw('p.submission_id, MIN(p.date_published) AS date_published')
            ->pluck('date_published', 'submission_id');

        $spans = [];
        foreach ($decisions as $submissionId => $rows) {
            array_push($spans, ...self::stageSpans(
                (string) $rows->first()->date_submitted,
                $rows->map(fn ($row) => [(int) $row->decision, (string) $row->date_decided])->all(),
                $published[$submissionId] ?? null
            ));
        }
        return $spans;
    }

    /**
     * Splits the history of one manuscript into segments of the workflow line.
     * A segment starts with the decision that moved the manuscript into it (the
     * submission itself for screening) and ends with the next decision that
     * moved it out; production ends at publication.
     *
     * @param array $decisions [[decision, date], ...] in date order
     * @return array[] [[segment index, start, end], ...] as Unix times; end is
     *  null while the manuscript is still in the segment
     */
    public static function stageSpans(string $dateSubmitted, array $decisions, ?string $datePublished): array
    {
        $spans = [];
        $index = 0;
        $start = strtotime($dateSubmitted);
        foreach ($decisions as [$decision, $date]) {
            if (!array_key_exists($decision, self::DECISION_STAGE) || self::DECISION_STAGE[$decision] === $index) {
                continue;
            }
            $time = strtotime($date);
            if ($index !== null) {
                $spans[] = [$index, $start, $time];
            }
            [$index, $start] = [self::DECISION_STAGE[$decision], $time];
        }
        if ($index !== null) {
            $spans[] = [$index, $start, $index === 4 && $datePublished ? strtotime($datePublished) : null];
        }
        return $spans;
    }

    /**
     * Term of each segment from the finished spans: the day of the stage by
     * which 80% of the manuscripts had left it, or the default term while
     * fewer than 5 manuscripts have.
     *
     * @param array[] $spans see stageSpans()
     * @return int[]
     */
    public static function typicalStageDays(array $spans): array
    {
        $days = array_fill(0, count(self::STAGE_DAYS), []);
        foreach ($spans as [$index, $start, $end]) {
            if ($end !== null) {
                $days[$index][] = self::dayNumber($start, $end);
            }
        }
        $typical = self::STAGE_DAYS;
        foreach ($days as $index => $list) {
            if (count($list) >= 5) {
                sort($list);
                $typical[$index] = $list[(int) ceil(count($list) * 0.8) - 1];
            }
        }
        return $typical;
    }

    /** Day of a stage at the given time; the day it started is day 1 */
    private static function dayNumber(int $start, int $time): int
    {
        return max(0, (int) floor(($time - $start) / 86400)) + 1;
    }

    private function resolveStage(Submission $submission): ?array
    {
        // Workflow stage ids of OJS: 1 submission, 2 internal review,
        // 3 external review, 4 copyediting, 5 production
        $stageId = (int) $submission->getData('stageId');
        switch ($stageId) {
            case 1:
                return ['index' => 0, 'key' => 'screening'];
            case 2:
            case 3:
                $round = DAORegistry::getDAO('ReviewRoundDAO')->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);
                $status = $round ? (int) $round->getStatus() : 0;
                // 1: revisions requested, 2: resubmit for review
                if (in_array($status, [1, 2], true)) {
                    return ['index' => 2, 'key' => 'revision'];
                }
                return ['index' => 1, 'key' => 'review'];
            case 4:
                return ['index' => 3, 'key' => 'copyediting'];
            case 5:
                return ['index' => 4, 'key' => 'proofreading'];
        }
        return null;
    }

    /**
     * Links for the language switch. They use the core "setLocale" operation
     * (the same one the language block uses): it stores the language in the
     * session and in a cookie and redirects to the same page with the new
     * language in the address, so one click is enough.
     */
    public function getLocaleLinks(): array
    {
        $request = Application::get()->getRequest();
        $locales = $request->getContext()?->getSupportedLocales() ?? [];
        if (count($locales) < 2) {
            return [];
        }

        $router = $request->getRouter();
        $current = Locale::getLocale();
        $source = $request->getCompleteUrl();

        $links = [];
        foreach ($locales as $locale) {
            $code = strtolower((string) strtok($locale, '_'));
            $links[] = [
                'locale' => $locale,
                'label' => ['uk' => 'UA'][$code] ?? strtoupper($code),
                'lang' => str_replace('_', '-', $locale),
                'current' => $locale === $current,
                'url' => $router->url($request, null, 'user', 'setLocale', [$locale], ['source' => $source]),
            ];
        }
        return $links;
    }

    /**
     * A multilingual theme option (tagline, journal information) in the current
     * language, else in the journal's primary language.
     */
    public function getLocalizedText(string $name): string
    {
        $value = $this->getOption($name);
        if (is_array($value)) {
            $value = $this->getBestLocalizedData($value);
        }
        return trim((string) $value);
    }

    /**
     * The journal information section as groups of rows in display order:
     * group => [['label' => locale key, 'value' => string, 'html' => bool], ...].
     * Title, ISSNs and the DOI prefix come from the journal settings, the rest
     * from the theme options; empty rows and groups are left out. Rows of the
     * "figures" group also carry the parts of splitFigure().
     */
    public function getJournalInfo(): array
    {
        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return [];
        }
        $option = fn (string $name): array => [self::INFO_OPTIONS[$name][0], $this->getLocalizedText($name), self::INFO_OPTIONS[$name][1]];
        $groups = [
            'title' => [
                ['plugins.themes.journalPulse.info.journalTitle', $context->getLocalizedName(), false],
            ],
            'identity' => [
                ['plugins.themes.journalPulse.info.onlineIssn', $context->getData('onlineIssn'), false],
                ['plugins.themes.journalPulse.info.printIssn', $context->getData('printIssn'), false],
                ['plugins.themes.journalPulse.info.doiPrefix', $context->getData('enableDois') ? $context->getData('doiPrefix') : '', false],
            ],
            'figures' => [$option('infoFrequency'), $option('infoAcceptanceRate'), $option('infoReviewSpeed')],
            'dates' => [$option('infoPublicationDates')],
            'indexing' => [$option('infoIndexing'), $option('infoCitationAnalysis')],
            'people' => [$option('infoEditorInChief'), $option('infoManagingEditor'), $option('infoOrganizedBy')],
        ];
        $info = [];
        foreach ($groups as $group => $rows) {
            foreach ($rows as [$label, $value, $html]) {
                $value = trim((string) $value);
                // A cleared rich-text field can still hold empty tags such as <p></p>
                if (trim(strip_tags($value)) !== '') {
                    $info[$group][] = ['label' => $label, 'value' => $value, 'html' => $html]
                        + ($group === 'figures' ? self::splitFigure($value) : []);
                }
            }
        }
        return $info;
    }

    /**
     * A key figure split into its leading number and the words after it, so
     * "90 days" is shown as a large "90" and a small "days": ['figure' => '90',
     * 'unit' => 'days']. A value that does not start with a number keeps all
     * of its text in 'unit'.
     */
    public static function splitFigure(string $value): array
    {
        if (preg_match('/^(\p{Nd}(?:[\p{Nd}.,٫٬–-]*\p{Nd})?(?:\s?[%٪])?)\s*(.*)$/su', $value, $m)) {
            return ['figure' => $m[1], 'unit' => $m[2]];
        }
        return ['figure' => '', 'unit' => $value];
    }

    /**
     * The accent colour chosen in the theme options (validated hex value).
     */
    public function getAccentColour(): string
    {
        $colour = (string) $this->getOption('accentColour');
        return preg_match('/^#[0-9a-fA-F]{6}$/', $colour) ? $colour : self::DEFAULT_ACCENT;
    }

    //
    // Hooks
    //

    /**
     * Adds canonical links and schema.org markup to the <head> of the home page,
     * issue pages and article pages.
     *
     * @hook TemplateManager::display [[$templateMgr, &$template, &$output]]
     */
    public function onTemplateDisplay(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !is_string($template)) {
            return Hook::CONTINUE;
        }
        $dispatcher = $request->getDispatcher();
        $options = ['contexts' => ['frontend']];

        if ($template === 'frontend/pages/indexJournal.tpl') {
            $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, null);
            $this->addCanonical($templateMgr, $url);
            $templateMgr->addHeader('jmJsonLd', $this->jsonLdScript($this->buildJournalJsonLd($context, $url)), $options);
        } elseif ($template === 'frontend/pages/issue.tpl') {
            $issue = $templateMgr->getTemplateVars('issue');
            if ($issue) {
                $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'issue', 'view', [$issue->getBestIssueId()]);
                $this->addCanonical($templateMgr, $url);
            }
        } elseif ($template === 'frontend/pages/article.tpl') {
            $article = $templateMgr->getTemplateVars('article');
            $publication = $templateMgr->getTemplateVars('publication');
            $issue = $templateMgr->getTemplateVars('issue');
            if ($article && $publication) {
                $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', [$article->getBestId()]);
                $this->addCanonical($templateMgr, $url);
                $templateMgr->addHeader(
                    'jmJsonLd',
                    $this->jsonLdScript($this->buildArticleJsonLd($context, $article, $publication, $issue, $url)),
                    $options
                );
            }
        }
        return Hook::CONTINUE;
    }

    //
    // Helpers
    //

    /**
     * Add the LESS variables to the parent theme's main stylesheet. They are
     * appended, so they win over the variables the parent sets from its options.
     */
    private function appendParentLessVariables(string $variables): void
    {
        $style = &$this->getStyle('stylesheet');
        if (empty($style)) {
            return;
        }
        $existing = $style['addLessVariables'] ?? '';
        if (is_array($existing)) {
            $existing = join("\n", $existing);
        }
        $style['addLessVariables'] = trim($existing . "\n" . $variables);
    }

    private function addCanonical($templateMgr, string $url): void
    {
        $templateMgr->addHeader(
            'jmCanonical',
            '<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />',
            ['contexts' => ['frontend']]
        );
    }

    private function jsonLdScript(array $data): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
        return '<script type="application/ld+json">' . json_encode($data, $flags) . '</script>';
    }

    private function buildPeriodical($context): array
    {
        $issn = array_values(array_filter([
            $context->getData('onlineIssn'),
            $context->getData('printIssn'),
        ]));
        $periodical = [
            '@type' => 'Periodical',
            'name' => $context->getLocalizedName(),
        ];
        if ($issn) {
            $periodical['issn'] = count($issn) === 1 ? $issn[0] : $issn;
        }
        return $periodical;
    }

    private function buildPublisher($context): ?array
    {
        $name = $context->getData('publisherInstitution');
        return $name ? ['@type' => 'Organization', 'name' => $name] : null;
    }

    private function buildJournalJsonLd($context, string $url): array
    {
        $data = ['@context' => 'https://schema.org'] + $this->buildPeriodical($context) + ['url' => $url];
        if ($publisher = $this->buildPublisher($context)) {
            $data['publisher'] = $publisher;
        }
        if ($context->getData('publishingMode') == Journal::PUBLISHING_MODE_OPEN) {
            $data['isAccessibleForFree'] = true;
        }
        return $data;
    }

    private function buildArticleJsonLd($context, $article, $publication, $issue, string $url): array
    {
        $authors = [];
        foreach ($publication->getData('authors') ?? [] as $author) {
            $person = ['@type' => 'Person', 'name' => $author->getFullName()];
            if ($affiliation = $author->getLocalizedAffiliationNamesAsString(null, ', ')) {
                $person['affiliation'] = ['@type' => 'Organization', 'name' => $affiliation];
            }
            if ($orcid = $author->getData('orcid')) {
                $person['sameAs'] = $orcid;
            }
            $authors[] = $person;
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ScholarlyArticle',
            'headline' => trim(strip_tags((string) $publication->getLocalizedFullTitle(null, 'text'))),
            'url' => $url,
            'mainEntityOfPage' => $url,
        ];
        if ($authors) {
            $data['author'] = $authors;
        }
        if ($datePublished = $publication->getData('datePublished')) {
            $data['datePublished'] = date('Y-m-d', strtotime((string) $datePublished));
        }
        if ($abstract = trim(strip_tags(html_entity_decode((string) $publication->getLocalizedData('abstract'))))) {
            $data['abstract'] = $abstract;
        }
        $keywords = [];
        foreach ((array) $publication->getLocalizedData('keywords') as $keyword) {
            if (!empty($keyword['name'])) {
                $keywords[] = $keyword['name'];
            }
        }
        if ($keywords) {
            $data['keywords'] = join(', ', $keywords);
        }
        if ($locale = $publication->getData('locale')) {
            $data['inLanguage'] = str_replace('_', '-', $locale);
        }
        if ($doiUrl = $publication->getData('doiObject')?->getData('resolvingUrl')) {
            $data['sameAs'] = $doiUrl;
            $data['identifier'] = ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doiUrl)];
        }
        if ($pages = trim((string) $publication->getData('pages'))) {
            $parts = preg_split('/\s*[-–—]\s*/u', $pages);
            $data['pageStart'] = $parts[0];
            if (count($parts) > 1 && $parts[1] !== '') {
                $data['pageEnd'] = $parts[1];
            }
        }
        if ($licenseUrl = $publication->getData('licenseUrl')) {
            $data['license'] = $licenseUrl;
        }
        if ($publisher = $this->buildPublisher($context)) {
            $data['publisher'] = $publisher;
        }
        if ($context->getData('publishingMode') == Journal::PUBLISHING_MODE_OPEN) {
            $data['isAccessibleForFree'] = true;
        }

        // Journal > volume > issue > article
        $periodical = $this->buildPeriodical($context);
        $partOf = $periodical;
        if ($issue) {
            if ($issue->getVolume()) {
                $partOf = ['@type' => 'PublicationVolume', 'volumeNumber' => (string) $issue->getVolume(), 'isPartOf' => $periodical];
            }
            $issuePart = ['@type' => 'PublicationIssue', 'isPartOf' => $partOf];
            if ($issue->getNumber()) {
                $issuePart['issueNumber'] = (string) $issue->getNumber();
            }
            if ($issue->getYear()) {
                $issuePart['datePublished'] = (string) $issue->getYear();
            }
            $partOf = $issuePart;
        }
        $data['isPartOf'] = $partOf;

        return $data;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\themes\journalPulse\JournalPulseThemePlugin', '\JournalPulseThemePlugin');
}
