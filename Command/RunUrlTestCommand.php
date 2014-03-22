<?php
/**
 * Class RunUrlTestCommand
 * @package Hezarfen\DenetmenBundle\Command
 */

namespace Hezarfen\DenetmenBundle\Command;

use Hezarfen\DenetmenBundle\Event\ErrorEvent;
use Hezarfen\DenetmenBundle\Service\DenetmenService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('pattern', null, InputOption::VALUE_OPTIONAL, "Routing regex for testing.", false)
            ->addOption("alert-email", null, InputOption::VALUE_OPTIONAL, "Sending an email on error.", false);
    }

    /**
     * Execute command
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DenetmenService $denetmenService */
        $denetmenService = $this->getContainer()->get('hezarfen.denetmen.service.denetmen_service');

        /** @var TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');

        $callableRoutes = $denetmenService->getCallableRoutes($denetmenService->getAllRoutes(), $input->getOption('pattern'));

        /** @var ProgressHelper $progress */
        $progress = $this->getHelperSet()->get('progress');
        $progress->setBarWidth(100);
        $progress->start($output,sizeof($callableRoutes));
        $outputRows = array();
        $table->setHeaders(array("URL", "Route Key", "Status", "Response Time", "Exception"));

        foreach ($callableRoutes as $routeKey => $route) {
            $progress->advance();
            $responseRow = $denetmenService->callRouteUrl($route, $routeKey);
            if ($responseRow['exception']) {
                $responseRow = $denetmenService->formatRow('error', $responseRow);
            } else {
                $responseRow = $denetmenService->formatRow('info', $responseRow);
            }
            array_push($outputRows, $responseRow);
        }
        $progress->finish();
        $table->setRows($outputRows);
        $table->render($output);

        $errors = array_filter($outputRows, function ($response) {
            return ($response['exception'])? true: false;
        });

        if (sizeof($errors) > 0) {
            $errorEvent = new ErrorEvent($input->getOptions());
            $errorEvent->setErrorRows($errors);
            $eventDispatcher = $this->getContainer()->get("event_dispatcher");
            $eventDispatcher->dispatch('hezarfen.denetmen.events.error', $errorEvent);
        }
    }

}
