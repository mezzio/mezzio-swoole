<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use ArrayIterator;
use Iterator;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * PhpUnit 10 removed withConsecutive() without alternative for ordered invocation assertions.
 *
 * Assertions in willReturnCallback() could potentially be suppressed if
 * tested code catches exceptions. Asserting arguments in with() using callback
 * has downside in that callback only returns boolean, provides no message, and it can not use
 * assertions.
 *
 * This constraint is stateful and as such it is NOT reusable. Should not be used for new tests.
 */
final class ConsecutiveConstraint extends Constraint
{
    /** @var Iterator<array-key, Constraint> */
    private Iterator $constraints;
    private bool $initial = true;

    /**
     * @param Constraint[] $consecutive
     */
    public function __construct(array $consecutive)
    {
        $this->constraints = new ArrayIterator($consecutive);
    }

    protected function matches(mixed $other): bool
    {
        if (! $this->initial) {
            $this->constraints->next();
        }
        $this->initial = false;
        $constraint    = $this->constraints->current();
        if ($constraint === null) {
            return false;
        }
        /**
         * @var bool $return returnResult is set to true.
         */
        $return = $constraint->evaluate($other, '', true);
        return $return;
    }

    public function toString(): string
    {
        $constraint = $this->constraints->current();
        if ($constraint === null) {
            return 'Consecutive constraints exhausted';
        }
        /** @psalm-suppress InternalMethod  */
        return $constraint->toString();
    }
}
