<?php

/** Laravel Framework */

declare(strict_types=1);

namespace Wakebit\CycleBridge\Tests\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;
use function mb_strlen;
use function mb_strpos;

final class SeeInOrder extends Constraint
{
    /**
     * The string under validation.
     *
     * @var string
     */
    protected $content;

    /**
     * The last value that failed to pass validation.
     *
     * @var string
     */
    protected $failedValue;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Determine if the rule passes validation.
     *
     * @param array $values
     */
    public function matches($values): bool
    {
        $position = 0;

        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }

            $valuePosition = mb_strpos($this->content, $value, $position);

            if ($valuePosition === false || $valuePosition < $position) {
                $this->failedValue = $value;

                return false;
            }

            $position = $valuePosition + mb_strlen($value);
        }

        return true;
    }

    /**
     * Get the description of the failure.
     *
     * @param array $values
     */
    public function failureDescription($values): string
    {
        return sprintf(
            'Failed asserting that \'%s\' contains "%s" in specified order.',
            $this->content,
            $this->failedValue
        );
    }

    /**
     * Get a string representation of the object.
     */
    public function toString(): string
    {
        return (new ReflectionClass($this))->name;
    }
}
