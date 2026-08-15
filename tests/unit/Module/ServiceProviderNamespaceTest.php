<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Module;

use PHPUnit\Framework\TestCase;

/**
 * The namespaces mod_livingword hands to Joomla's service-provider factories.
 *
 * Written after the module rendered nothing at all for a release — not its
 * wrapper div, not its empty-state message, no exception, nothing in any log.
 * services/provider.php passed the dispatcher factory a namespace that already
 * carried the client segment, and ModuleDispatcherFactory::createDispatcher()
 * appends that segment itself, so it looked for a Site\Site\Dispatcher\Dispatcher
 * that does not exist. class_exists() said no, the factory fell back to core's
 * ModuleDispatcher, and that one looks for a legacy mod_livingword.php entry file
 * we do not ship — it returns having emitted nothing. Every step is silent.
 *
 * The trap is that the two factories take namespaces of *different depths*, so
 * "make them consistent" is the wrong instinct and reintroduces the bug in the
 * other direction. Both halves are asserted here.
 *
 * These read the real files rather than booting Joomla: the CMS is not on the
 * unit-test autoloader, and the bug lives in a string, not in behaviour.
 *
 * @since  __DEPLOY_VERSION__
 */
class ServiceProviderNamespaceTest extends TestCase
{
    /**
     * Repository root — the tests bootstrap points JPATH_BASE here.
     */
    private const ROOT = JPATH_BASE;

    private function provider(): string
    {
        return file_get_contents(self::ROOT . '/mod_livingword/services/provider.php');
    }

    /**
     * The single argument given to a service provider in provider.php.
     */
    private function namespaceArgumentFor(string $factory): string
    {
        $pattern = '/new\s+' . preg_quote($factory, '/') . "\(\s*'([^']+)'\s*\)/";

        $this->assertMatchesRegularExpression(
            $pattern,
            $this->provider(),
            $factory . ' is not registered with a literal namespace in services/provider.php'
        );

        preg_match($pattern, $this->provider(), $matches);

        // The file writes namespaces as escaped literals ('\\CWM\\...').
        return stripslashes($matches[1]);
    }

    /**
     * The class a PHP file declares, read from its namespace and class lines.
     */
    private function declaredClassIn(string $relativePath): string
    {
        $source = file_get_contents(self::ROOT . '/' . $relativePath);

        preg_match('/^namespace\s+([^;]+);/m', $source, $ns);
        preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $source, $class);

        return '\\' . trim($ns[1]) . '\\' . $class[1];
    }

    /**
     * ModuleDispatcherFactory takes the BASE namespace, because
     * createDispatcher() appends '\Site' (or '\Administrator') itself before
     * '\Dispatcher\Dispatcher'. Core does the same: mod_articles_news passes
     * '\Joomla\Module\ArticlesNews', not '...\ArticlesNews\Site'.
     */
    public function testTheDispatcherNamespaceResolvesToOurDispatcher(): void
    {
        $base = $this->namespaceArgumentFor('ModuleDispatcherFactory');

        // libraries/src/Dispatcher/ModuleDispatcherFactory.php, verbatim.
        $resolved = '\\' . trim($base, '\\') . '\\Site\\Dispatcher\\Dispatcher';

        $this->assertSame(
            $this->declaredClassIn('mod_livingword/src/Dispatcher/Dispatcher.php'),
            $resolved,
            'Joomla would look for ' . $resolved . ', which no file declares, and fall back '
            . 'to core ModuleDispatcher — the module then renders nothing, silently.'
        );
    }

    /**
     * HelperFactory appends nothing but the helper name, so it takes the FULL
     * namespace down to \Site\Helper.
     */
    public function testTheHelperNamespaceResolvesToOurHelper(): void
    {
        $base = $this->namespaceArgumentFor('HelperFactory');

        // libraries/src/Helper/HelperFactory.php: namespace . '\\' . $name.
        $resolved = '\\' . trim($base, '\\') . '\\LivingwordHelper';

        $this->assertSame(
            $this->declaredClassIn('mod_livingword/src/Helper/LivingwordHelper.php'),
            $resolved,
            'getHelper(\'LivingwordHelper\') would throw for ' . $resolved . '.'
        );
    }

    /**
     * The fallback path that made the original bug invisible: core's
     * ModuleDispatcher runs modules/mod_livingword/mod_livingword.php and
     * returns quietly when it is absent. We ship the dispatcher pattern and no
     * such entry file, so there is no safety net to catch a namespace slip —
     * which is the whole reason the assertions above exist.
     */
    public function testWeShipNoLegacyEntryFileToFallBackOn(): void
    {
        $this->assertFileDoesNotExist(
            self::ROOT . '/mod_livingword/mod_livingword.php',
            'An entry file here would mask a broken dispatcher namespace instead of exposing it.'
        );
    }
}
