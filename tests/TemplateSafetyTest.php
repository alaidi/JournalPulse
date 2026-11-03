<?php

/**
 * @file plugins/themes/JournalMetrics/tests/TemplateSafetyTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemplateSafetyTest
 *
 * @brief Static checks on the templates and the source files.
 */

namespace APP\plugins\themes\JournalMetrics\tests;

use PKP\tests\PKPTestCase;

class TemplateSafetyTest extends PKPTestCase
{
    /** @return string[] */
    protected function templates(): array
    {
        return glob(dirname(__DIR__) . '/templates/frontend/*/*.tpl') ?: [];
    }

    public function testFormsPostWithACsrfToken(): void
    {
        $this->assertNotEmpty($this->templates());
        foreach ($this->templates() as $file) {
            $source = (string) file_get_contents($file);
            if (preg_match('/<form\b[^>]*method="post"/i', $source)) {
                $this->assertStringContainsString('{csrf}', $source, basename($file) . ' posts without a CSRF token.');
            }
        }
    }

    public function testTranslationsInAttributesAreEscaped(): void
    {
        // {translate key="x"|escape} escapes the key, not the translation.
        foreach ($this->templates() as $file) {
            $this->assertSame(0, preg_match('/\{translate key="[^"]+"\|escape\}/', (string) file_get_contents($file)), basename($file));
        }
    }

    public function testKeywordsAreReadAsStrings(): void
    {
        // OJS 3.5 keywords are plain strings; $keyword.name (the 3.6 shape) is a fatal TypeError.
        foreach ($this->templates() as $file) {
            $this->assertStringNotContainsString('$keyword.name', (string) file_get_contents($file), basename($file));
        }
    }

    public function testNoCoreTemplateIsReplaced(): void
    {
        foreach (glob(dirname(__DIR__) . '/*.php') as $file) {
            $source = (string) file_get_contents($file);
            $this->assertSame(0, preg_match("/Hook(Registry)?::(add|register)\\(\\s*'TemplateResource::getFilename'/", $source), basename($file) . ' replaces a core template.');
        }
    }
}
