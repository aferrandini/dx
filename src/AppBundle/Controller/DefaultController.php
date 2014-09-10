<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity\Issue;

class DefaultController extends Controller
{
    /**
     * @Route("/", name = "homepage")
     */
    public function indexAction(Request $request)
    {
        $filter = $request->get('filter', '');

        $new = $this->getRepository()->findNewIssues($filter);
        $discussing = $this->getRepository()->findDiscussingIssues($filter);
        $wip = $this->getRepository()->findWipIssues($filter);
        $finished = $this->getRepository()->findFinishedIssues($filter);

        if (!$this->get('session')->has('githubUsername')) {
            $loggedUser = $this->getUser();

            if ($loggedUser = $this->getUser()) {
                $sensioLabsConnectUser = $this->get('sensiolabs_connect.api')->getRoot()->getUser($loggedUser->getUsername());
                $this->get('session')->set('githubUsername', $sensioLabsConnectUser->get('githubUsername'));
            }
        }

        return $this->render("index.html.twig", array(
            'new'        => $new,
            'discussing' => $discussing,
            'wip'        => $wip,
            'finished'   => $finished,
            'filter'     => $filter
        ));
    }

    /**
     * @Route("/assign", name = "assign")
     * @Method({"POST"})
     */
    public function assignIssueAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $issue = $this->getRepository()->find($request->get('issue_id'));
        $issue->setAssignedTo(trim($request->get('assignee')));
        $issue->setStatus(Issue::STATUS_WIP);

        $em->persist($issue);
        $em->flush();

        return $this->redirect($this->generateUrl('homepage'));
    }

    /**
     * @Route("/self-assign", name = "self_assign")
     * @Method({"POST"})
     */
    public function selfAssignIssueAction(Request $request)
    {
        if (!$assignee = $this->get('session')->has('githubUsername')) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $issue = $this->getRepository()->find($request->get('issue_id'));
        $issue->setAssignedTo($this->get('session')->get('githubUsername'));
        $issue->setStatus(Issue::STATUS_WIP);

        $em->persist($issue);
        $em->flush();

        return $this->redirect($this->generateUrl('homepage'));
    }

    /**
     * @Route("/add_pull_request", name = "add_pr")
     * @Method({"POST"})
     */
    public function addPullRequestAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $issue = $this->getRepository()->find($request->get('issue_id'));
        $issue->setPullRequest(trim($request->get('url')));

        $em->persist($issue);
        $em->flush();

        return $this->redirect($this->generateUrl('homepage'));
    }

    private function getRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository('AppBundle:Issue');
    }
}
