<?php

namespace App\Component\Router\FriendlyUrl;

class FriendlyUrlDataFactory implements FriendlyUrlDataFactoryInterface
{
    /**
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData
     */
    protected function createInstance(): FriendlyUrlData
    {
        return new FriendlyUrlData();
    }

    /**
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData
     */
    public function create(): FriendlyUrlData
    {
        return $this->createInstance();
    }

    /**
     * @param int $id
     * @param string $name
     * @return \App\Component\Router\FriendlyUrl\FriendlyUrlData
     */
    public function createFromIdAndName(int $id, string $name): FriendlyUrlData
    {
        $friendlyUrlData = $this->createInstance();
        $friendlyUrlData->id = $id;
        $friendlyUrlData->name = $name;

        return $friendlyUrlData;
    }
}
