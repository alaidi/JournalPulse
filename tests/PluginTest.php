<?php

/**
 * @file plugins/themes/journalPulse/tests/PluginTest.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginTest
 *
 * @brief The plugin classes compiled against the PKP classes of the
 *        installation: an override whose return type differs from its parent is
 *        a fatal error that php -l does not catch.
 */

namespace APP\plugins\themes\journalPulse\tests;

use APP\core\Application;
use APP\plugins\themes\journalPulse\JournalPulseMigration;
use Illuminate\Support\Facades\DB;
use PKP\tests\PKPTestCase;
use ReflectionClass;
use ReflectionNamedType;

class PluginTest extends PKPTestCase
{
    /** @return string[] */
    protected function classes(): array
    {
        return [
            \APP\plugins\themes\journalPulse\JournalPulseThemePlugin::class,
            JournalPulseMigration::class,
        ];
    }

    public function testEveryClassLoadsAgainstThisPkpVersion(): void
    {
        foreach ($this->classes() as $class) {
            $this->assertTrue(class_exists($class), "{$class} does not load.");
        }
    }

    public function testOverriddenMethodsDeclareCompatibleReturnTypes(): void
    {
        $this->assertNotEmpty($this->classes());
        foreach ($this->classes() as $class) {
            $reflection = new ReflectionClass($class);
            $parent = $reflection->getParentClass();
            if ($parent === false) {
                continue;
            }
            foreach ($reflection->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $reflection->getName() || !$parent->hasMethod($method->getName())) {
                    continue;
                }
                $parentType = $parent->getMethod($method->getName())->getReturnType();
                if ($parentType === null) {
                    continue;
                }
                $type = $method->getReturnType();
                $covariant = $type instanceof ReflectionNamedType && $parentType instanceof ReflectionNamedType
                    && !$type->isBuiltin() && !$parentType->isBuiltin() && is_a($type->getName(), $parentType->getName(), true);
                $this->assertTrue(
                    $type !== null && ((string) $type === (string) $parentType || $covariant || ($type instanceof ReflectionNamedType && '?' . $type->getName() === (string) $parentType)),
                    sprintf('%s::%s() must declare a return type compatible with %s.', $reflection->getShortName(), $method->getName(), $parentType)
                );
            }
        }
    }

    public function testNoInheritedPropertyIsRedeclaredWithAType(): void
    {
        // A typed redeclaration of an untyped parent property ($pluginPath...) is fatal.
        $this->assertNotEmpty($this->classes());
        foreach ($this->classes() as $class) {
            $reflection = new ReflectionClass($class);
            $parent = $reflection->getParentClass();
            if ($parent === false) {
                continue;
            }
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $reflection->getName() || !$parent->hasProperty($property->getName())) {
                    continue;
                }
                $this->assertSame((string) $parent->getProperty($property->getName())->getType(), (string) $property->getType(), "{$class}::\${$property->getName()}");
            }
        }
    }

    public function testInstallMovesJournalsOverFromTheFormerName(): void
    {
        $dao = Application::getContextDAO();
        DB::beginTransaction(); // rolled back: the test leaves no rows behind
        try {
            // context_id must be a journal (foreign key), or NULL for site-wide settings
            $journalId = DB::table($dao->tableName)->value($dao->primaryKeyColumn);
            foreach (array_unique([$journalId, null]) as $contextId) {
                DB::table('plugin_settings')->insert([
                    'plugin_name' => JournalPulseMigration::FORMER_NAME, 'context_id' => $contextId,
                    'setting_name' => 'migrationTest', 'setting_value' => '#123456', 'setting_type' => 'string',
                ]);
            }
            if ($journalId) {
                DB::table($dao->settingsTableName)->updateOrInsert(
                    [$dao->primaryKeyColumn => $journalId, 'locale' => '', 'setting_name' => 'themePluginPath'],
                    ['setting_value' => JournalPulseMigration::FORMER_PATH]
                );
            }

            (new JournalPulseMigration())->up();
            (new JournalPulseMigration())->up(); // OJS runs it on every upgrade: nothing is copied twice

            foreach (array_unique([$journalId, null]) as $contextId) {
                $this->assertSame(['#123456'], DB::table('plugin_settings')
                    ->where('plugin_name', 'journalpulsethemeplugin')->where('context_id', $contextId)
                    ->where('setting_name', 'migrationTest')->pluck('setting_value')->all());
            }
            if ($journalId) {
                $this->assertSame('journalPulse', DB::table($dao->settingsTableName)
                    ->where($dao->primaryKeyColumn, $journalId)->where('setting_name', 'themePluginPath')
                    ->value('setting_value'));
            }
        } finally {
            DB::rollBack();
        }
    }
}
