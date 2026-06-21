<?php

declare(strict_types=1);

class AssertionFailed extends RuntimeException {}

abstract class TestBase
{
    private int $assertionCount = 0;
    private int $status = 0;
    private ?string $statusMessage = null;
    protected ?string $expectedException = null;
    protected ?string $expectedExceptionMessage = null;
    protected bool $expectNoAssertions = false;

    const STATUS_UNKNOWN = 0;
    const STATUS_PASSED = 1;
    const STATUS_SKIPPED = 2;
    const STATUS_FAILURE = 3;

    public function setUp(): void {}
    public function tearDown(): void {}

    public function _getAssertionCount(): int { return $this->assertionCount; }
    public function _isSkipped(): bool { return $this->status === self::STATUS_SKIPPED; }
    public function _getSkipReason(): ?string { return $this->statusMessage; }
    public function _expectNoAssertions(): bool { return $this->expectedException !== null || $this->expectNoAssertions; }
    public function _resetExpectations(): void { $this->expectedException = null; $this->expectNoAssertions = false; $this->status = self::STATUS_UNKNOWN; $this->statusMessage = null; }
    public function _getExpectedExceptionClass(): ?string { return $this->expectedException; }

    protected function expectException(string $exception): void
    {
        $this->expectedException = $exception;
    }

    protected function expectExceptionMessage(string $message): void
    {
        $this->expectedExceptionMessage = $message;
    }

    protected function expectExceptionMessageMatches(string $pattern): void
    {
        $this->expectedExceptionMessage = $pattern;
    }

    protected function expectNotToPerformAssertions(): void
    {
        $this->expectNoAssertions = true;
    }

    protected function addToAssertionCount(int $count): void
    {
        $this->assertionCount += $count;
    }

    protected function fail(string $message = ""): void
    {
        throw new AssertionFailed($message);
    }

    protected function markTestSkipped(string $message = ""): void
    {
        $this->status = self::STATUS_SKIPPED;
        $this->statusMessage = $message;
        throw new RuntimeException("SKIPPED: " . $message);
    }

    protected function markTestIncomplete(string $message = ""): void
    {
        $this->markTestSkipped($message);
    }

    private function export(mixed $value): string
    {
        if (is_null($value)) return "null";
        if (is_bool($value)) return $value ? "true" : "false";
        if (is_string($value)) return "\"" . $value . "\"";
        if (is_array($value)) return "Array(" . count($value) . ")";
        if (is_object($value)) return $value::class;
        return (string)$value;
    }

    private function convertFormatToRegex(string $format): string
    {
        $regex = preg_quote($format, "/");
        $regex = str_replace(
            ["%s", "%d", "%f", "%i", "%x"],
            ["[^\\n]*", "\\d+", "\\d+\\.?\\d*", "[+-]?\\d+", "[0-9a-fA-F]+"],
            $regex
        );
        return "/" . $regex . "/";
    }

    // ---- Truthiness ----
    protected function assertTrue(mixed $condition, string $message = ""): void
    {
        $this->assertionCount++;
        if ($condition !== true)
            throw new AssertionFailed($message ?: "assertTrue() failed");
    }

    protected function assertFalse(mixed $condition, string $message = ""): void
    {
        $this->assertionCount++;
        if ($condition !== false)
            throw new AssertionFailed($message ?: "assertFalse() failed");
    }

    protected function assertNull(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if ($value !== null)
            throw new AssertionFailed($message ?: "assertNull() failed");
    }

    protected function assertNotNull(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if ($value === null)
            throw new AssertionFailed($message ?: "assertNotNull() failed");
    }

    protected function assertEmpty(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!empty($value))
            throw new AssertionFailed($message ?: "assertEmpty() failed");
    }

    protected function assertNotEmpty(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (empty($value))
            throw new AssertionFailed($message ?: "assertNotEmpty() failed");
    }

    protected function assertNotFalse(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if ($value === false)
            throw new AssertionFailed($message ?: "assertNotFalse() failed");
    }

    // ---- Equality ----
    protected function assertSame(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($expected !== $actual)
            throw new AssertionFailed($message ?: sprintf(
                "assertSame() failed: expected %s, got %s",
                $this->export($expected), $this->export($actual)
            ));
    }

    protected function assertNotSame(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($expected === $actual)
            throw new AssertionFailed($message ?: "assertNotSame() failed");
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($expected != $actual)
            throw new AssertionFailed($message ?: sprintf(
                "assertEquals() failed: expected %s, got %s",
                $this->export($expected), $this->export($actual)
            ));
    }

    // ---- Types ----
    protected function assertIsString(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_string($value))
            throw new AssertionFailed($message ?: "assertIsString() failed");
    }

    protected function assertIsBool(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_bool($value))
            throw new AssertionFailed($message ?: "assertIsBool() failed");
    }

    protected function assertIsInt(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_int($value))
            throw new AssertionFailed($message ?: "assertIsInt() failed");
    }

    protected function assertIsFloat(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_float($value))
            throw new AssertionFailed($message ?: "assertIsFloat() failed");
    }

    protected function assertIsArray(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_array($value))
            throw new AssertionFailed($message ?: "assertIsArray() failed");
    }

    protected function assertIsCallable(mixed $value, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_callable($value))
            throw new AssertionFailed($message ?: "assertIsCallable() failed");
    }

    protected function assertInstanceOf(string $class, mixed $object, string $message = ""): void
    {
        $this->assertionCount++;
        if (!($object instanceof $class))
            throw new AssertionFailed($message ?: sprintf("assertInstanceOf(%s) failed", $class));
    }

    // ---- Strings ----
    protected function assertStringContainsString(string $needle, string $haystack, string $message = ""): void
    {
        $this->assertionCount++;
        if (!str_contains($haystack, $needle))
            throw new AssertionFailed($message ?: "assertStringContainsString() failed");
    }

    protected function assertStringNotContainsString(string $needle, string $haystack, string $message = ""): void
    {
        $this->assertionCount++;
        if (str_contains($haystack, $needle))
            throw new AssertionFailed($message ?: "assertStringNotContainsString() failed");
    }

    protected function assertStringStartsWith(string $prefix, string $string, string $message = ""): void
    {
        $this->assertionCount++;
        if (!str_starts_with($string, $prefix))
            throw new AssertionFailed($message ?: "assertStringStartsWith() failed");
    }

    protected function assertStringEndsWith(string $suffix, string $string, string $message = ""): void
    {
        $this->assertionCount++;
        if (!str_ends_with($string, $suffix))
            throw new AssertionFailed($message ?: "assertStringEndsWith() failed");
    }

    protected function assertMatchesRegularExpression(string $pattern, string $string, string $message = ""): void
    {
        $this->assertionCount++;
        if (@preg_match($pattern, $string) !== 1)
            throw new AssertionFailed($message ?: "assertMatchesRegularExpression() failed");
    }

    protected function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ""): void
    {
        $this->assertionCount++;
        if (@preg_match($pattern, $string) === 1)
            throw new AssertionFailed($message ?: "assertDoesNotMatchRegularExpression() failed");
    }

    protected function assertStringMatchesFormat(string $format, string $string, string $message = ""): void
    {
        $this->assertionCount++;
        $regex = $this->convertFormatToRegex($format);
        if (@preg_match($regex, $string) !== 1)
            throw new AssertionFailed($message ?: sprintf("assertStringMatchesFormat(%s) failed", $format));
    }

    // ---- Arrays ----
    protected function assertCount(int $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        $count = is_countable($actual) ? count($actual) : 0;
        if ($count !== $expected)
            throw new AssertionFailed($message ?: sprintf(
                "assertCount() failed: expected %d, got %d", $expected, $count
            ));
    }

    protected function assertContains(mixed $needle, mixed $haystack, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_array($haystack) || !in_array($needle, $haystack, true))
            throw new AssertionFailed($message ?: "assertContains() failed");
    }

    protected function assertNotContains(mixed $needle, mixed $haystack, string $message = ""): void
    {
        $this->assertionCount++;
        if (is_array($haystack) && in_array($needle, $haystack, true))
            throw new AssertionFailed($message ?: "assertNotContains() failed");
    }

    protected function assertArrayHasKey(mixed $key, mixed $array, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_array($array) || !array_key_exists($key, $array))
            throw new AssertionFailed($message ?: sprintf("assertArrayHasKey(%s) failed", $this->export($key)));
    }

    protected function assertArrayNotHasKey(mixed $key, mixed $array, string $message = ""): void
    {
        $this->assertionCount++;
        if (is_array($array) && array_key_exists($key, $array))
            throw new AssertionFailed($message ?: sprintf("assertArrayNotHasKey(%s) failed", $this->export($key)));
    }

    protected function assertJson(string $actual, string $message = ""): void
    {
        $this->assertionCount++;
        json_decode($actual);
        if (json_last_error() !== JSON_ERROR_NONE)
            throw new AssertionFailed($message ?: "assertJson() failed: " . json_last_error_msg());
    }

    // ---- Files ----
    protected function assertFileExists(string $filename, string $message = ""): void
    {
        $this->assertionCount++;
        if (!file_exists($filename))
            throw new AssertionFailed($message ?: sprintf("assertFileExists(%s) failed", $filename));
    }

    protected function assertFileDoesNotExist(string $filename, string $message = ""): void
    {
        $this->assertionCount++;
        if (file_exists($filename))
            throw new AssertionFailed($message ?: sprintf("assertFileDoesNotExist(%s) failed", $filename));
    }

    protected function assertDirectoryExists(string $directory, string $message = ""): void
    {
        $this->assertionCount++;
        if (!is_dir($directory))
            throw new AssertionFailed($message ?: sprintf("assertDirectoryExists(%s) failed", $directory));
    }

    // ---- Comparison ----
    protected function assertGreaterThan(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($actual <= $expected)
            throw new AssertionFailed($message ?: sprintf(
                "assertGreaterThan() failed: %s <= %s",
                $this->export($actual), $this->export($expected)
            ));
    }

    protected function assertGreaterThanOrEqual(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($actual < $expected)
            throw new AssertionFailed($message ?: sprintf(
                "assertGreaterThanOrEqual() failed: %s < %s",
                $this->export($actual), $this->export($expected)
            ));
    }

    protected function assertLessThan(mixed $expected, mixed $actual, string $message = ""): void
    {
        $this->assertionCount++;
        if ($actual >= $expected)
            throw new AssertionFailed($message ?: sprintf(
                "assertLessThan() failed: %s >= %s",
                $this->export($actual), $this->export($expected)
            ));
    }
}
