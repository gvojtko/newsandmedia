<?php

namespace App\Component\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Exception;
use App\Component\Money\Money;

class MoneyType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'money';
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->getAmount();
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', Money::class]);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        try {
            return Money::create($value);
        } catch (Exception $e) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), 'numeric', $e);
        }
    }

    /**
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return bool
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
