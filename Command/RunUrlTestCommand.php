<?php
/**
 * Class RunUrlTestCommand
 * @package Hezarfen\DenetmenBundle\Command
 */


namespace Hezarfen\DenetmenBundle\Command;

use Hezarfen\DenetmenBundle\Service\DenetmenService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunUrlTestCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('denetmen:run:url-test')
            ->setDescription('Test urls.')
            ->addArgument('pattern', InputArgument::OPTIONAL, "Routing regex");
    }

    /**
     * Execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DenetmenService $denetmenService */
        $denetmenService = $this->getContainer()->get('hezarfen.denetmen.service.denetmen_service');
        $callableRoutes = $denetmenService->getCallableRoutes($denetmenService->getAllRoutes(), $input->getArgument('pattern'));

        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setHeaders(array("URL", "Route Key", "Status", "Response Time", "Exception"));
        $table->setRows($denetmenService->callRoutesUrl($callableRoutes));
        $table->render($output);
    }

}