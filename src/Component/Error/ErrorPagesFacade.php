<?php

declare(strict_types=1);

namespace App\Component\Error;

use App\Kernel;
use App\Component\Environment\EnvironmentType;
use App\Component\Error\Exception\BadErrorPageStatusCodeException;
use App\Component\Error\Exception\ErrorPageNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ErrorPagesFacade
{
    protected const PAGE_STATUS_CODE_404 = Response::HTTP_NOT_FOUND;
    protected const PAGE_STATUS_CODE_410 = Response::HTTP_GONE;
    protected const PAGE_STATUS_CODE_500 = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * @var string
     */
    protected $errorPagesDir;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \App\Component\Error\ErrorIdProvider
     */
    protected $errorIdProvider;

    /**
     * @param string $errorPagesDir
     * @param RouterInterface $router
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param \App\Component\Error\ErrorIdProvider $errorIdProvider
     */
    public function __construct(
        $errorPagesDir,
        RouterInterface $router,
        Filesystem $filesystem,
        ErrorIdProvider $errorIdProvider
    ) {
        $this->errorPagesDir = $errorPagesDir;
        $this->router = $router;
        $this->filesystem = $filesystem;
        $this->errorIdProvider = $errorIdProvider;
    }

    public function generateAllErrorPagesForProduction()
    {
        $this->generateAndSaveErrorPage(static::PAGE_STATUS_CODE_404);
        $this->generateAndSaveErrorPage(static::PAGE_STATUS_CODE_410);
        $this->generateAndSaveErrorPage(static::PAGE_STATUS_CODE_500);
    }

    /**
     * @param int $statusCode
     * @return string
     */
    public function getErrorPageContentByStatusCode( $statusCode)
    {
        $errorPageContent = file_get_contents($this->getErrorPageFilename($statusCode));
        if ($errorPageContent === false) {
            throw new ErrorPageNotFoundException($statusCode);
        }

        $errorPageContent = str_replace('{{ERROR_ID}}', $this->errorIdProvider->getErrorId(), $errorPageContent);

        return $errorPageContent;
    }

    /**
     * @param int $statusCode
     * @return int
     */
    public function getErrorPageStatusCodeByStatusCode($statusCode)
    {
        switch ($statusCode) {
            case Response::HTTP_NOT_FOUND:
            case Response::HTTP_FORBIDDEN:
                return static::PAGE_STATUS_CODE_404;
            case Response::HTTP_GONE:
                return static::PAGE_STATUS_CODE_410;
            default:
                return static::PAGE_STATUS_CODE_500;
        }
    }

    /**
     * @param int $statusCode
     */
    protected function generateAndSaveErrorPage($statusCode)
    {
        $errorPageUrl = $this->router->generate(
            'front_error_page_format',
            [
                '_format' => 'html',
                'code' => $statusCode,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $errorPageContent = $this->getUrlContent($errorPageUrl, $statusCode);

        $this->filesystem->dumpFile(
            $this->getErrorPageFilename($statusCode),
            $errorPageContent
        );
    }

    /**
     * @param int $statusCode
     * @return string
     */
    protected function getErrorPageFilename($statusCode)
    {
        return $this->errorPagesDir . $statusCode . 'html';
    }

    /**
     * @param string $errorPageUrl
     * @param int $expectedStatusCode
     * @return string
     */
    protected function getUrlContent($errorPageUrl, $expectedStatusCode)
    {
        $errorPageKernel = new Kernel(EnvironmentType::PRODUCTION, false);

        $errorPageFakeRequest = Request::create($errorPageUrl);

        $errorPageResponse = $errorPageKernel->handle($errorPageFakeRequest);
        $errorPageKernel->terminate($errorPageFakeRequest, $errorPageResponse);

        if ($expectedStatusCode !== $errorPageResponse->getStatusCode()) {
            throw new BadErrorPageStatusCodeException(
                $errorPageUrl,
                $expectedStatusCode,
                $errorPageResponse->getStatusCode()
            );
        }

        return $errorPageResponse->getContent();
    }
}
