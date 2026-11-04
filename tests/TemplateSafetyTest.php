<?php

/**
 * @file plugins/themes/journalPulse/tests/TemplateSafetyTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemplateSafetyTest
 *
 * @brief Static checks on the templates and the source files.
 */

namespace APP\plugins\themes\journalPulse\tests;

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

    public function testKeywordsAreReadThroughTheTheme(): void
    {
        // OJS 3.5.0-3 returns keywords as strings and 3.5.0.5 as ['name' => ...] entries:
        // read directly, the entries print "Array", and $keyword.name is a fatal TypeError
        // on 3.5.0-3. getKeywords() handles both.
        foreach ($this->templates() as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringNotContainsString("getLocalizedData('keywords')", $source, basename($file));
            $this->assertStringNotContainsString('$keyword.name', $source, basename($file));
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
