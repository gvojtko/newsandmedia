<?php

declare(strict_types=1);

namespace App\Component\Error;

use App\Component\FlashMessage\FlashMessage;
use App\Model\Customer\User\CurrentCustomerUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;

class LogoutExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface
     */
    protected $flashBag;

    /**
     * @var \App\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flashBag
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(
        FlashBagInterface $flashBag,
        RouterInterface $router,
        ContainerInterface $container
    ) {
        $this->flashBag = $flashBag;
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException'],
        ];
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if (
            $event->getThrowable() instanceof LogoutException
            || $event->getThrowable()->getPrevious() instanceof LogoutException
        ) {
            // check customer logged in
            // $this->currentCustomerUser...

            $redirectUrl = $this->getSafeUrlToRedirect($event->getRequest()->headers->get('referer'));

            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }

    /**
     * @param string|null $url
     * @return string
     */
    protected function getSafeUrlToRedirect(?string $url): string
    {
        if ($url !== null) {
            $urlParse = parse_url($url);
            $domainUrl = $this->container->getParameter('domain_url');
            $domainUrlParse = parse_url($domainUrl);
            $parsedUrl = $urlParse['scheme'] . $urlParse['host'];
            $parsedDomainUrl = $domainUrlParse['scheme'] . $domainUrlParse['host'];

            if ($parsedUrl === $parsedDomainUrl) {
                return $url;
            }
        }

        return $this->router->generate('front_homepage');
    }
}
