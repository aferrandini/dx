<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Issue;

class LookForIssuesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('issues:look-for')
            ->setDescription('Looks for DX issues in the configured repos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new \Github\Client(
            new \Github\HttpClient\CachedHttpClient(array(
                'cache_dir' => __DIR__.'/../../../app/cache/github'
            ))
        );

        $results = $client
            ->api('issue')
            ->all('symfony', 'symfony', array('labels' => 'DX')
        );

        $em = $this->getContainer()->get('doctrine')->getManager();

        foreach ($results as $result) {
            $entity = $em->getRepository('AppBundle:Issue')->findOneBy(array(
                'title' => $result['title']
            ));

            if (null === $entity) {
                $issue = new Issue(array(
                    'title'      => $result['title'],
                    'body'       => $result['body'],
                    'url'        => $result['html_url'],
                    'repository' => 'symfony/symfony',
                    'author'     => $result['user']['login'],
                    'status'     => $this->getIssueStatus($result),
                    'createdAt'  => new \DateTime($result['created_at']),
                ));

                $em->persist($issue);
            }
        }

        $em->flush();
    }

    private function getIssueStatus(array $issue)
    {
        if ('closed' === $issue['state']) {
            return Issue::STATUS_FINISHED;
        }

        if (count($issue['comments']) > 0) {
            return Issue::STATUS_DISCUSSING;
        }

        return Issue::STATUS_NEW;
    }
}
