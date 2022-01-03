<?php

namespace App\Component\Router\Security\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target("METHOD")
 */
class CsrfProtection extends Annotation
{
}
