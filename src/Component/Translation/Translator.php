<?php

namespace App\Component\Translation;

use App\Component\Translation\Exception\InstanceNotInjectedException;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, LegacyTranslatorInterface
{
    protected const DEFAULT_DOMAIN = 'messages';
    public const SOURCE_LOCALE = 'en';

    /**
     * @var \App\Component\Translation\Translator|null
     */
    protected static $self;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $originalTranslator;

    /**
     * @var \Symfony\Component\Translation\TranslatorBagInterface
     */
    protected $originalTranslatorBag;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $identityTranslator;

    /**
     * @var \App\Component\Translation\MessageIdNormalizer
     */
    protected $messageIdNormalizer;

    /**
     * @param \Symfony\Component\Translation\TranslatorInterface $originalTranslator
     * @param \Symfony\Component\Translation\TranslatorBagInterface $originalTranslatorBag
     * @param \Symfony\Component\Translation\TranslatorInterface $identityTranslator
     * @param \App\Component\Translation\MessageIdNormalizer $messageIdNormalizer
     */
    public function __construct(
        TranslatorInterface $originalTranslator,
        TranslatorBagInterface $originalTranslatorBag,
        TranslatorInterface $identityTranslator,
        MessageIdNormalizer $messageIdNormalizer
    ) {
        $this->originalTranslator = $originalTranslator;
        $this->originalTranslatorBag = $originalTranslatorBag;
        $this->identityTranslator = $identityTranslator;
        $this->messageIdNormalizer = $messageIdNormalizer;
    }

    /**
     * Passes trans() call to original translator for logging purposes.
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $normalizedId = $this->messageIdNormalizer->normalizeMessageId($id);
        $resolvedLocale = $this->resolveLocale($locale);
        $resolvedDomain = $this->resolveDomain($domain);

        $catalogue = $this->originalTranslatorBag->getCatalogue($resolvedLocale);

        if ($resolvedLocale === self::SOURCE_LOCALE) {
            if ($catalogue->defines($normalizedId, $resolvedDomain)) {
                $message = $this->originalTranslator->trans(
                    $normalizedId,
                    $parameters,
                    $resolvedDomain,
                    $resolvedLocale
                );
            } else {
                $message = $this->identityTranslator->trans(
                    $normalizedId,
                    $parameters,
                    $resolvedDomain,
                    $resolvedLocale
                );
            }
        } else {
            $message = $this->originalTranslator->trans($normalizedId, $parameters, $resolvedDomain, $resolvedLocale);
        }

        return $message;
    }

    /**
     * Passes transChoice() call to original translator for logging purposes.
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $normalizedId = $this->messageIdNormalizer->normalizeMessageId($id);
        $resolvedLocale = $this->resolveLocale($locale);
        $resolvedDomain = $this->resolveDomain($domain);

        $catalogue = $this->originalTranslatorBag->getCatalogue($resolvedLocale);

        if ($resolvedLocale === self::SOURCE_LOCALE) {
            if ($catalogue->defines($normalizedId, $resolvedDomain)) {
                $message = $this->originalTranslator->transChoice(
                    $normalizedId,
                    $number,
                    $parameters,
                    $resolvedDomain,
                    $resolvedLocale
                );
            } else {
                $message = $this->identityTranslator->transChoice(
                    $normalizedId,
                    $number,
                    $parameters,
                    $resolvedDomain,
                    $resolvedLocale
                );
            }
        } else {
            $message = $this->originalTranslator->transChoice(
                $normalizedId,
                $number,
                $parameters,
                $resolvedDomain,
                $resolvedLocale
            );
        }

        return $message;
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    protected function resolveLocale($locale)
    {
        if ($locale === null) {
            return $this->getLocale();
        }

        return $locale;
    }

    /**
     * @param string|null $domain
     * @return string
     */
    protected function resolveDomain($domain)
    {
        if ($domain === null) {
            return static::DEFAULT_DOMAIN;
        }

        return $domain;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        return $this->originalTranslator->getLocale();
    }

    /**
     * {@inheritDoc}
     */
    public function setLocale($locale)
    {
        $this->originalTranslator->setLocale($locale);
        $this->identityTranslator->setLocale($locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getCatalogue($locale = null)
    {
        return $this->originalTranslatorBag->getCatalogue($locale);
    }

    /**
     * @param \App\Component\Translation\Translator $translator
     */
    public static function injectSelf(TranslatorInterface $translator)
    {
        self::$self = $translator;
    }

    /**
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public static function staticTrans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (self::$self === null) {
            throw new InstanceNotInjectedException();
        }

        return self::$self->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @param string $id
     * @param int $number
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public static function staticTransChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        if (self::$self === null) {
            throw new InstanceNotInjectedException();
        }

        return self::$self->transChoice($id, $number, $parameters, $domain, $locale);
    }
}
