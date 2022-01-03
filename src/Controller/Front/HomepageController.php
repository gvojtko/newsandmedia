<?php

namespace App\Controller\Front;

class HomepageController extends FrontBaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('Front/Content/Homepage/index.html.twig');
    }
}
