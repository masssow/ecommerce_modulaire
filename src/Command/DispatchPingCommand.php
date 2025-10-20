<?php

namespace App\Command;

use App\Message\TestPing;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:dispatch:ping')]
final class DispatchPingCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bus->dispatch(new TestPing('hello-from-sandbox-' . date('Ymd-His')));
        $output->writeln('<info>Ping dispatched to async transport.</info>');
        return Command::SUCCESS;
    }
}
