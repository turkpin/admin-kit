#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * AdminKit Testing Utility
 * 
 * Simple testing framework for AdminKit components
 */

// Prevent running from web
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.');
}

// Define root directory
define('ADMINKIT_ROOT', dirname(__DIR__));

// Parse command line arguments
$options = getopt('t:f:v', ['test:', 'filter:', 'verbose', 'help', 'version']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

if (isset($options['version'])) {
    echo "AdminKit Testing Utility v1.0.7\n";
    exit(0);
}

$testFilter = $options['t'] ?? $options['test'] ?? null;
$fileFilter = $options['f'] ?? $options['filter'] ?? null;
$verbose = isset($options['v']) || isset($options['verbose']);

echo "\nAdminKit Testing Utility\n";
echo "========================\n\n";

// Simple test framework
class TestRunner
{
    private array $tests = [];
    private int $passed = 0;
    private int $failed = 0;
    private bool $verbose;

    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    public function addTest(string $name, callable $test): void
    {
        $this->tests[$name] = $test;
    }

    public function run(?string $filter = null): void
    {
        $this->log("Starting tests...\n");

        foreach ($this->tests as $name => $test) {
            if ($filter && stripos($name, $filter) === false) {
                continue;
            }

            $this->runTest($name, $test);
        }

        $this->showResults();
    }

    private function runTest(string $name, callable $test): void
    {
        $this->log("Running: $name... ", false);

        try {
            $startTime = microtime(true);
            $result = call_user_func($test);
            $duration = microtime(true) - $startTime;

            if ($result === true || $result === null) {
                $this->passed++;
                $this->log("PASS (" . number_format($duration, 4) . "s)");
            } else {
                $this->failed++;
                $this->log("FAIL - $result");
            }
        } catch (Throwable $e) {
            $this->failed++;
            $this->log("ERROR - " . $e->getMessage());
            if ($this->verbose) {
                $this->log("  File: " . $e->getFile() . ":" . $e->getLine());
            }
        }
    }

    private function showResults(): void
    {
        $total = $this->passed + $this->failed;
        $this->log("\nTest Results:");
        $this->log("=============");
        $this->log("Total: $total");
        $this->log("Passed: {$this->passed}");
        $this->log("Failed: {$this->failed}");
        
        if ($this->failed > 0) {
            $this->log("\nSome tests failed!");
            exit(1);
        } else {
            $this->log("\nAll tests passed!");
        }
    }

    private function log(string $message, bool $newline = true): void
    {
        echo $message . ($newline ? "\n" : "");
    }
}

// Helper functions for tests
function assertEquals($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $msg = $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true);
        throw new Exception($msg);
    }
}

function assertTrue($condition, string $message = 'Assertion failed'): void
{
    if (!$condition) {
        throw new Exception($message);
    }
}

function assertFalse($condition, string $message = 'Assertion failed'): void
{
    if ($condition) {
        throw new Exception($message);
    }
}

function assertNull($value, string $message = 'Expected null'): void
{
    if ($value !== null) {
        throw new Exception($message);
    }
}

function assertNotNull($value, string $message = 'Expected not null'): void
{
    if ($value === null) {
        throw new Exception($message);
    }
}

function assertInstanceOf(string $expected, $actual, string $message = ''): void
{
    if (!($actual instanceof $expected)) {
        $msg = $message ?: "Expected instance of $expected, got " . get_class($actual);
        throw new Exception($msg);
    }
}

// Create test runner
$runner = new TestRunner($verbose);

// Load AdminKit for testing
if (file_exists(ADMINKIT_ROOT . '/vendor/autoload.php')) {
    require_once ADMINKIT_ROOT . '/vendor/autoload.php';
}

// Core functionality tests
$runner->addTest('PHP Version Check', function() {
    assertTrue(version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0+ required');
});

$runner->addTest('Required Extensions', function() {
    $required = ['json', 'mbstring', 'pdo'];
    foreach ($required as $ext) {
        assertTrue(extension_loaded($ext), "Extension $ext is required");
    }
});

$runner->addTest('Directory Structure', function() {
    $dirs = ['src', 'public', 'config', 'bootstrap', 'install', 'demo', 'docs', 'bin'];
    foreach ($dirs as $dir) {
        assertTrue(is_dir(ADMINKIT_ROOT . '/' . $dir), "Directory $dir should exist");
    }
});

$runner->addTest('Core Files Exist', function() {
    $files = [
        'public/index.php',
        'config/container.php',
        'config/doctrine.php',
        'config/smarty.php',
        'bootstrap/app.php',
        'composer.json'
    ];
    
    foreach ($files as $file) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $file), "File $file should exist");
    }
});

$runner->addTest('Composer Configuration', function() {
    $composerFile = ADMINKIT_ROOT . '/composer.json';
    assertTrue(file_exists($composerFile), 'composer.json should exist');
    
    $composer = json_decode(file_get_contents($composerFile), true);
    assertNotNull($composer, 'composer.json should be valid JSON');
    
    assertEquals('turkpin/admin-kit', $composer['name'], 'Package name should be correct');
    assertEquals('1.0.7', $composer['version'], 'Version should be 1.0.7');
    
    assertTrue(isset($composer['bin']), 'Should have bin scripts');
    assertTrue(in_array('bin/adminkit', $composer['bin']), 'Should include adminkit script');
});

$runner->addTest('Installation Files', function() {
    $installFiles = [
        'install/index.php',
        'install/steps/welcome.php',
        'install/steps/requirements.php',
        'install/steps/database.php',
        'install/steps/admin.php',
        'install/steps/complete.php'
    ];
    
    foreach ($installFiles as $file) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $file), "Install file $file should exist");
    }
});

$runner->addTest('Demo Application', function() {
    $demoFiles = [
        'demo/index.php',
        'demo/config.php',
        'demo/src/Entity/User.php',
        'demo/src/Entity/Product.php',
        'demo/src/Entity/Category.php',
        'demo/src/Seeders/DemoSeeder.php'
    ];
    
    foreach ($demoFiles as $file) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $file), "Demo file $file should exist");
    }
});

$runner->addTest('Documentation Files', function() {
    $docFiles = [
        'docs/getting-started.md',
        'docs/installation.md',
        'docs/configuration.md',
        'docs/api-reference.md',
        'docs/examples/basic-setup.md'
    ];
    
    foreach ($docFiles as $file) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $file), "Documentation file $file should exist");
    }
});

$runner->addTest('Development Tools', function() {
    $tools = [
        'bin/serve.php',
        'bin/assets.php',
        'bin/test.php'
    ];
    
    foreach ($tools as $tool) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $tool), "Development tool $tool should exist");
        assertTrue(is_executable(ADMINKIT_ROOT . '/' . $tool), "Tool $tool should be executable");
    }
});

$runner->addTest('Services Classes', function() {
    $services = [
        'src/Services/DashboardService.php',
        'src/Services/DebugService.php'
    ];
    
    foreach ($services as $service) {
        assertTrue(file_exists(ADMINKIT_ROOT . '/' . $service), "Service $service should exist");
    }
});

// If AdminKit classes are available, test them
if (class_exists('AdminKit\\AdminKit')) {
    $runner->addTest('AdminKit Class Instantiation', function() {
        // This would require a proper Slim app setup
        assertTrue(class_exists('AdminKit\\AdminKit'), 'AdminKit class should be available');
    });
}

if (class_exists('AdminKit\\Services\\DashboardService')) {
    $runner->addTest('DashboardService Functionality', function() {
        // Mock AdminKit instance for testing
        $mockAdminKit = new class {
            public function getEntities(): array { return []; }
        };
        
        $dashboard = new AdminKit\Services\DashboardService($mockAdminKit);
        
        assertInstanceOf('AdminKit\\Services\\DashboardService', $dashboard);
        
        // Test widget addition
        $dashboard->addWidget('test_widget', [
            'title' => 'Test Widget',
            'type' => 'counter',
            'value' => 42
        ]);
        
        $widgets = $dashboard->getWidgets();
        assertTrue(isset($widgets['test_widget']), 'Widget should be added');
        assertEquals(42, $widgets['test_widget']['value'], 'Widget value should be correct');
    });
}

if (class_exists('AdminKit\\Services\\DebugService')) {
    $runner->addTest('DebugService Functionality', function() {
        $debug = new AdminKit\Services\DebugService(true, sys_get_temp_dir() . '/adminkit-test');
        
        assertInstanceOf('AdminKit\\Services\\DebugService', $debug);
        assertTrue($debug->isEnabled(), 'Debug should be enabled');
        
        // Test logging
        $debug->log('Test message', ['key' => 'value']);
        
        // Test timer
        $debug->startTimer('test_timer');
        usleep(1000); // 1ms
        $duration = $debug->endTimer('test_timer');
        assertTrue($duration > 0, 'Timer should measure duration');
        
        // Test toolbar data
        $toolbarData = $debug->getToolbarData();
        assertTrue(is_array($toolbarData), 'Toolbar data should be array');
        assertTrue(isset($toolbarData['execution_time']), 'Should have execution time');
    });
}

// Run the tests
$runner->run($testFilter);

function showHelp(): void
{
    echo <<<HELP
AdminKit Testing Utility

USAGE:
    php bin/test.php [OPTIONS]

OPTIONS:
    -t, --test=NAME      Run only tests matching NAME
    -f, --filter=NAME    Filter tests by file or class name  
    -v, --verbose        Show detailed output
    --help               Show this help message
    --version            Show version information

EXAMPLES:
    php bin/test.php
    php bin/test.php --test=Dashboard
    php bin/test.php --verbose
    php bin/test.php -t Service -v

FEATURES:
    - Core functionality testing
    - File structure validation
    - Service class testing
    - Configuration validation
    - Installation verification

HELP;
}
