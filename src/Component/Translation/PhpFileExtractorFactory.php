<?php

namespace App\Component\Translation;

use Doctrine\Common\Annotations\DocParser;

class PhpFileExtractorFactory
{
    /**
     * @var \Doctrine\Common\Annotations\DocParser
     */
    protected $docParser;

    /**
     * @param \Doctrine\Common\Annotations\DocParser $docParser
     */
    public function __construct(DocParser $docParser)
    {
        $this->docParser = $docParser;
    }

    /**
     * @return \App\Component\Translation\PhpFileExtractor
     */
    public function create()
    {
        $transMethodSpecifications = [
            new TransMethodSpecification('trans', 0, 2),
            new TransMethodSpecification('transChoice', 0, 3),
            new TransMethodSpecification('t', 0, 2),
            new TransMethodSpecification('tc', 0, 3),
        ];

        return new PhpFileExtractor($this->docParser, $transMethodSpecifications);
    }
}
