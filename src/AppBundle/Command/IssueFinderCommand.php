<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use AppBundle\Entity\Issue;

class IssueFinderCommand extends ContainerAwareCommand
{
    private $rootDir;

    protected function configure()
    {
        $this
            ->setName('issues:find')
            ->setDescription('Looks for DX issues in the configured repos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->rootDir = __DIR__.'/../../../';
        $repositories = $this->getRepositoriesToScan();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $output->writeln("Looking for DX Issues");
        $output->writeln("=====================\n");

        foreach ($repositories as $repository) {
            $output->writeln("> Scanning $repository");

            $results = $this->findIssues($repository);
            $this->saveIssues($em, $results);
        }

        $em->flush();
    }

    private function getRepositoriesToScan()
    {
        $repositories = Yaml::parse($this->rootDir.'/app/config/repositories.yml');

        return $repositories['repositories'];
    }

    private function getGitHubClient()
    {
        return new \Github\Client(
            new \Github\HttpClient\CachedHttpClient(array(
                'cache_dir' => $this->rootDir.'/app/cache/github'
            ))
        );
    }

    private function findIssues($repositoryUrl)
    {
        list($vendor, $repository) = explode('/', $repositoryUrl);

        return $this->getGitHubClient()
            ->api('issue')
            ->all($vendor, $repository, array(
                'labels' => 'DX',
                'state'  => 'all', // by default it only looks for 'open' issues
            )
        );
    }

    private function saveIssues(EntityManager $em, $results)
    {
        foreach ($results as $result) {
            /** @var Issue $issue */
            $issue = $em->getRepository('AppBundle:Issue')->findOneBy(array(
                'githubId' => $result['id']
            ));

            if (null === $issue) {
                $issue = new Issue(array(
                    'githubId'   => $result['id'],
                    'title'      => $result['title'],
                    'body'       => $result['body'],
                    'url'        => $result['html_url'],
                    'repository' => 'symfony/symfony',
                    'author'     => $result['user']['login'],
                    'status'     => $this->getIssueStatus($result),
                    'createdAt'  => new \DateTime($result['created_at']),
                    'comments'   => $result['comments'] ? $result['comments'] : 0,
                ));

            } else {
                $issue
                    ->setTitle($result['title'])
                    ->setBody($result['body'])
                    ->setStatus($this->getIssueStatus($result))
                    ->setComments($result['comments'])
                ;
            }

            $em->persist($issue);
        }
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
