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
        $new = $issues->findByStatus(Issue::STATUS_NEW, array('createdAt' => 'DESC'));
        $discussing = $issues->findByStatus(Issue::STATUS_DISCUSSING, array('createdAt' => 'DESC'));
        $wip = $issues->findByStatus(Issue::STATUS_WIP, array('createdAt' => 'DESC'));
        $finished = $issues->findByStatus(Issue::STATUS_FINISHED, array('createdAt' => 'DESC'));

        return $this->render("index.html.twig", array(
            'new'        => $new,
            'discussing' => $discussing,
            'wip'        => $wip,
            'finished'   => $finished,
        ));
    }
}
