<?php

namespace App\Component\Router\FriendlyUrl;

class UrlListData
{
    public const FIELD_DOMAIN = 'domain';
    public const FIELD_SLUG = 'slug';

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrl[][]
     */
    public $toDelete;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrl[]
     */
    public $mainFriendlyUrls;

    /**
     * @var array[]
     *
     * Format:
     * [
     *     [
     *         'slug' => 'slug-for-the-first-domain',
     *     ],
     *     ...
     * ]
     * @see \App\Component\Router\FriendlyUrl\FriendlyUrlFacade::saveUrlListFormData()
     */
    public $newUrls;

    public function __construct()
    {
        $this->toDelete = [];
        $this->mainFriendlyUrls = [];
        $this->newUrls = [];
    }
}
