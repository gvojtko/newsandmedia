<?php

namespace App\Controller\Front;

use Exception;
use App\Environment;
use App\Component\Environment\EnvironmentType;
use App\Component\Error\ErrorPagesFacade;
use App\Component\Error\ExceptionController;
use App\Component\Error\ExceptionListener;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Tracy\BlueScreen;
use Tracy\Debugger;

class ErrorController extends FrontBaseController
{
    /**
     * @var \App\Component\Error\ExceptionController
     */
    private $exceptionController;

    /**
     * @var \App\Component\Error\ExceptionListener
     */
    private $exceptionListener;

    /**
     * @var \App\Component\Error\ErrorPagesFacade
     */
    private $errorPagesFacade;

    /**
     * @param \App\Component\Error\ExceptionController $exceptionController
     * @param \App\Component\Error\ExceptionListener $exceptionListener
     * @param \App\Component\Error\ErrorPagesFacade $errorPagesFacade
     */
    public function __construct(
        ExceptionController $exceptionController,
        ExceptionListener $exceptionListener,
        ErrorPagesFacade $errorPagesFacade
    ) {
        $this->exceptionController = $exceptionController;
        $this->exceptionListener = $exceptionListener;
        $this->errorPagesFacade = $errorPagesFacade;
    }

    /**
     * @param int $code
     */
    public function errorPageAction($code)
    {
        $this->exceptionController->setDebug(false);
        $this->exceptionController->setShowErrorPagePrototype();

        throw new \App\Component\Error\Exception\FakeHttpException($code);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Debug\Exception\FlattenException $exception
     * @param \Symfony\Component\HttpKernel\Log\DebugLoggerInterface $logger
     */
    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null
    ) {
        if ($this->exceptionController->isShownErrorPagePrototype()) {
            return $this->createErrorPagePrototypeResponse($request, $exception, $logger);
        } elseif ($this->exceptionController->getDebug()) {
            return $this->createExceptionResponse($request, $exception, $logger);
        } else {
            return $this->createErrorPageResponse($exception->getStatusCode());
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Debug\Exception\FlattenException $exception
     * @param \Symfony\Component\HttpKernel\Log\DebugLoggerInterface $logger
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createErrorPagePrototypeResponse(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger
    ) {
        // Same as in \Symfony\Bundle\TwigBundle\Controller\PreviewErrorController
        $format = $request->getRequestFormat();

        $code = $exception->getStatusCode();

        return $this->render('Front/Content/Error/error.' . $format . '.twig', [
            'status_code' => $code,
            'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
            'exception' => $exception,
            'logger' => $logger,
        ]);
    }

    /**
     * @param int $statusCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createErrorPageResponse($statusCode)
    {
        $errorPageStatusCode = $this->errorPagesFacade->getErrorPageStatusCodeByStatusCode($statusCode);
        $errorPageContent = $this->errorPagesFacade->getErrorPageContentByStatusCode(
            $errorPageStatusCode
        );

        return new Response($errorPageContent, $errorPageStatusCode);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Debug\Exception\FlattenException $exception
     * @param \Symfony\Component\HttpKernel\Log\DebugLoggerInterface $logger
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createExceptionResponse(Request $request, FlattenException $exception, DebugLoggerInterface $logger)
    {
        $lastException = $this->exceptionListener->getLastException();
        if ($lastException !== null) {
            return $this->getPrettyExceptionResponse($lastException);
        }

        return $this->exceptionController->showAction($request, $exception, $logger);
    }

    /**
     * @param \Exception $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getPrettyExceptionResponse(Exception $exception)
    {
        Debugger::$time = time();
        $blueScreen = new BlueScreen();
        $blueScreen->info = [
            'PHP ' . PHP_VERSION,
        ];

        ob_start();
        $blueScreen->render($exception);
        $blueScreenHtml = ob_get_contents();
        ob_end_clean();

        return new Response($blueScreenHtml);
    }
}
