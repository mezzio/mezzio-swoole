<?php

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception;

use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const E_WARNING;

trait ValidateRegexTrait
{
    private function isValidRegex(string $regex): bool
    {
        set_error_handler(static function ($errno) {
            return $errno === E_WARNING;
        });
        $isValid = preg_match($regex, '') !== false;
        restore_error_handler();
        return $isValid;
    }

    /**
     * @throws Exception\InvalidArgumentException If any regexp is invalid.
     */
    private function validateRegexList(array $regexList, string $type): void
    {
        foreach ($regexList as $regex) {
            if (! $this->isValidRegex($regex)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'The %s regex "%s" is invalid',
                    $type,
                    $regex
                ));
            }
        }
    }
}
