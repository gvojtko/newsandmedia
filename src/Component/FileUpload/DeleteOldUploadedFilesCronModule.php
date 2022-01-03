<?php

declare(strict_types=1);

namespace App\Component\FileUpload;

use App\Component\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class DeleteOldUploadedFilesCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \App\Component\FileUpload\FileUpload
     */
    protected $fileUpload;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     */
    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function run(): void
    {
        $count = $this->fileUpload->deleteOldUploadedFiles();

        $this->logger->info($count . ' files were deleted.');
    }
}
