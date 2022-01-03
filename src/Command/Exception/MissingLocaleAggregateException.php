<?php

declare(strict_types=1);

namespace App\Command\Exception;

use Exception;

class MissingLocaleAggregateException extends Exception
{
    /**
     * @param \App\Command\Exception\MissingLocaleException[] $missingLocaleExceptions
     */
    public function __construct(array $missingLocaleExceptions)
    {
        $missingLocales = [];
        foreach ($missingLocaleExceptions as $missingLocaleException) {
            $missingLocales[] = $missingLocaleException->getLocale();
        }

        $message = sprintf(
            'It looks like your operating system does not support these locales: %s. '
                . 'Please visit docs/installation/native-installation-troubleshooting.md for more details.',
            '"' . implode('", "', $missingLocales) . '"'
        );

        parent::__construct($message, $missingLocaleExceptions[0]->getCode(), $missingLocaleExceptions[0]);
    }
}
