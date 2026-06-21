<?php

declare(strict_types=1);

// Cargar bootstrap del framework, luego restaurar handlers para evitar cascada
require_once __DIR__ . '/bootstrap.php';
ini_set('error_log', __DIR__ . '/../storage/logs/test_errors.log');
restore_error_handler();
restore_exception_handler();

require_once __DIR__ . '/TestBase.php';
require_once __DIR__ . '/Integracion/Modelo/TestCaseDb.php';

$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$cyan = "\033[36m";
$reset = "\033[0m";

$testDirs = [
    __DIR__ . '/Casos',
    __DIR__ . '/Integracion',
];

$total = 0;
$passes = 0;
$failures = 0;
$errors = 0;
$skipped = 0;
$totalAssertions = 0;
$failedTests = [];
$errorTests = [];

function findTestFiles(array $dirs): array
{
    $files = [];
    foreach ($dirs as $dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getRealPath();
            }
        }
    }
    sort($files);
    return $files;
}

function runSeparateProcess(string $file, string $class, string $method): array
{
    $code = sprintf(
        '<?php require_once %s; restore_error_handler(); restore_exception_handler(); require_once %s; require_once %s; require_once %s; ' .
        '$t = new %s; $t->_resetExpectations(); ' .
        'ob_start(); ' .
        'try { $t->setUp(); call_user_func([$t, "%s"]); $t->tearDown(); $salida = ob_get_clean(); ' .
        'if ($t->_expectNoAssertions() && $t->_getAssertionCount() === 0) { echo "OK_NOASSERT"; exit(0); } ' .
        'if ($t->_getAssertionCount() === 0 && !$t->_expectNoAssertions()) { echo "NO_ASSERT"; exit(2); } ' .
        'echo "OK"; exit(0); } ' .
        'catch (AssertionFailed $e) { ob_end_clean(); echo "FAIL:" . $e->getMessage(); exit(1); } ' .
        'catch (RuntimeException $e) { ob_end_clean(); if (str_starts_with($e->getMessage(), "SKIPPED:")) { echo "SKIP:" . $e->getMessage(); exit(3); } throw $e; } ' .
        'catch (Throwable $e) { ob_end_clean(); echo "ERROR:" . get_class($e) . ": " . $e->getMessage(); exit(4); }',
        var_export(__DIR__ . '/bootstrap.php', true),
        var_export(__DIR__ . '/TestBase.php', true),
        var_export(__DIR__ . '/Integracion/Modelo/TestCaseDb.php', true),
        var_export($file, true),
        $class, $method
    );

    $tmpFile = tempnam(sys_get_temp_dir(), 'lt_') . '.php';
    file_put_contents($tmpFile, $code);

    $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
    $proc = proc_open(
        PHP_BINARY . ' ' . escapeshellarg($tmpFile),
        $descriptors,
        $pipes
    );

    if (!is_resource($proc)) {
        unlink($tmpFile);
        return ['status' => 'error', 'message' => 'Could not launch process'];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);
    unlink($tmpFile);

    return match ($exitCode) {
        0 => ['status' => 'pass', 'output' => $stdout],
        1 => ['status' => 'fail', 'message' => trim(substr($stdout, 5))],
        2 => ['status' => 'no_assert'],
        3 => ['status' => 'skip', 'message' => trim(substr($stdout, 5))],
        4 => ['status' => 'error', 'message' => trim(substr($stdout, 6))],
        default => ['status' => 'error', 'message' => "Exit code {$exitCode}: {$stdout}{$stderr}"],
    };
}

$testFiles = findTestFiles($testDirs);
echo "{$cyan}liteTest Runner{$reset}\n";
echo str_repeat('-', 50) . "\n";

foreach ($testFiles as $file) {
    $className = null;
    require_once $file;

    try {
        $classes = get_declared_classes();
        foreach ($classes as $c) {
            $r = new ReflectionClass($c);
            if ($r->getFileName() === $file && !$r->isAbstract()) {
                $className = $c;
                break;
            }
        }
        if ($className === null) continue;
        $ref = new ReflectionClass($className);

        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        $testMethods = array_filter($methods, fn($m) => str_starts_with($m->getName(), 'test'));

        if (empty($testMethods)) continue;

        if ($ref->hasMethod('setUpBeforeClass') && $ref->getMethod('setUpBeforeClass')->isStatic()) {
            $className::setUpBeforeClass();
        }

        foreach ($testMethods as $method) {
            $total++;
            $doc = $method->getDocComment() ?: '';
            $inSeparateProcess = str_contains($doc, '@runInSeparateProcess');

            $instance = new $className();
            $instance->_resetExpectations();

            $exceptionMatched = false;

            if ($inSeparateProcess) {
                $result = runSeparateProcess($file, $className, $method->getName());
            } else {
                ob_start();
                try {
                    $instance->setUp();
                    $method->invoke($instance);
                    $instance->tearDown();
                    $result = ['status' => 'pass'];
                } catch (AssertionFailed $e) {
                    $result = ['status' => 'fail', 'message' => $e->getMessage()];
                } catch (Throwable $e) {
                    if ($e instanceof RuntimeException && str_starts_with($e->getMessage(), 'SKIPPED:')) {
                        $result = ['status' => 'skip', 'message' => $e->getMessage()];
                    } else {
                        $expClass = $instance->_getExpectedExceptionClass();
                        if ($expClass !== null && $e instanceof $expClass) {
                            $exceptionMatched = true;
                            $result = ['status' => 'pass'];
                        } else {
                            $result = ['status' => 'error', 'message' => get_class($e) . ': ' . $e->getMessage()];
                        }
                    }
                } finally {
                    ob_get_clean();
                }
            }

            $assertCount = $instance->_getAssertionCount();

            if ($result['status'] === 'pass' && !$exceptionMatched) {
                $expClass = $instance->_getExpectedExceptionClass();
                if ($expClass !== null) {
                    $result = ['status' => 'fail', 'message' => "Expected exception {$expClass} was not thrown"];
                } elseif ($assertCount === 0 && !$instance->_expectNoAssertions()) {
                    $result = ['status' => 'no_assert'];
                } else {
                    $totalAssertions += max($assertCount, 1);
                    $passes++;
                    echo "{$green}.{$reset}";
                    continue;
                }
            }

            if ($result['status'] === 'no_assert') {
                $totalAssertions++;
                $passes++;
                echo "{$green}.{$reset}";
                continue;
            }

            if ($result['status'] === 'skip') {
                $skipped++;
                echo "{$yellow}S{$reset}";
                continue;
            }

            if ($result['status'] === 'fail') {
                $failures++;
                $totalAssertions += max($assertCount, 1);
                echo "{$red}F{$reset}";
                $failedTests[] = [
                    'test' => basename($file) . '::' . $method->getName(),
                    'message' => $result['message'] ?? '',
                ];
                continue;
            }

            if ($result['status'] === 'error') {
                $errors++;
                echo "{$red}E{$reset}";
                $errorTests[] = [
                    'test' => basename($file) . '::' . $method->getName(),
                    'message' => $result['message'] ?? '',
                ];
                continue;
            }
        }

        if ($ref->hasMethod('tearDownAfterClass') && $ref->getMethod('tearDownAfterClass')->isStatic()) {
            $className::tearDownAfterClass();
        }

        if ($total % 60 === 0) {
            flush();
        }

    } catch (Throwable $e) {
        $errors++;
        echo "{$red}E{$reset}";
        $errorTests[] = [
            'test' => basename($file) . '::(class load)',
            'message' => get_class($e) . ': ' . $e->getMessage(),
        ];
    }
}

echo "\n" . str_repeat('-', 50) . "\n";
echo "{$cyan}Resultados:{$reset}\n";
echo "  Tests:      {$total}\n";
echo "  Assertions: {$totalAssertions}\n";
echo "  Pasados:    {$green}{$passes}{$reset}\n";
if ($failures > 0) echo "  Fallos:     {$red}{$failures}{$reset}\n";
if ($errors > 0) echo "  Errores:    {$red}{$errors}{$reset}\n";
if ($skipped > 0) echo "  Omitidos:   {$yellow}{$skipped}{$reset}\n";

if (!empty($failedTests)) {
    echo "\n{$red}FALLOS:{$reset}\n";
    foreach ($failedTests as $f) {
        echo "  {$f['test']}\n";
        echo "    {$f['message']}\n";
    }
}

if (!empty($errorTests)) {
    echo "\n{$red}ERRORES:{$reset}\n";
    foreach ($errorTests as $e) {
        echo "  {$e['test']}\n";
        echo "    {$e['message']}\n";
    }
}

$exitCode = ($failures > 0 || $errors > 0) ? 1 : 0;
if ($total === 0) { echo "\n{$yellow}No se encontraron tests.{$reset}\n"; $exitCode = 1; }
exit($exitCode);
