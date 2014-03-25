<?php
/**
 * Class DenetmenExceptionListener
 * @package Hezarfen\DenetmenBundle\EventListener
 */

namespace Hezarfen\DenetmenBundle\EventListener;

use Hezarfen\DenetmenBundle\Event\ErrorEvent;

class DenetmenEventListener
{
    /** @var  \Swift_Mailer */
    private $mailerService;
    /** @var  \Twig_Environment */
    private $twig;

    public function __construct(\Swift_Mailer $mailerService, \Twig_Environment $twig)
    {
        $this->mailerService = $mailerService;
        $this->twig = $twig;
    }

    public function onError(ErrorEvent $event)
    {
        if ($event->getCommandOptions()['alert-email']) {
            $message = \Swift_Message::newInstance()
                ->setSubject("Denetmen Errors")
                ->setTo($event->getCommandOptions()['alert-email'])
                ->setFrom(sprintf('denetmen-error@%s', gethostname()))
                ->setContentType("text/html")
                ->setBody($this->twig->render(
                    "HezarfenDenetmenBundle:Template:errors.html.twig",
                    array("errors" => $event->getErrorRows())
                ));

            $this->mailerService->send($message);
        }
    }
}
