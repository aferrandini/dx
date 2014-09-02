<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Issue;

class DefaultController extends Controller
{
    /**
     * @Route("/", name = "homepage")
     */
    public function indexAction()
    {
        $issues = $this->getDoctrine()->getManager()->getRepository('AppBundle:Issue');
        $new = $issues->findByStatus(Issue::STATUS_NEW);
        $discussing = $issues->findByStatus(Issue::STATUS_DISCUSSING);
        $wip = $issues->findByStatus(Issue::STATUS_WIP);
        $finished = $issues->findByStatus(Issue::STATUS_FINISHED);

        return $this->render("index.html.twig", array(
            'new'        => $new,
            'discussing' => $discussing,
            'wip'        => $wip,
            'finished'   => $finished,
        ));
    }
}
