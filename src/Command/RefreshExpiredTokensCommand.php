<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\ActivationService;

#[AsCommand(
    name: 'app:refresh-expired-tokens',
    description: 'Refreshes expired activation tokens for users.',
)]
class RefreshExpiredTokensCommand extends Command
{
    private ActivationService $activationService;

    /**
     * Constructor for RefreshExpiredTokensCommand.
     *
     * @param ActivationService $activationService The service responsible for activation token management.
     */
    public function __construct(ActivationService $activationService)
    {
        parent::__construct();
        $this->activationService = $activationService;
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->setHelp('This command allows you to refresh expired activation tokens for users.');
    }

    /**
     * Execute the command.
     * This command refreshes expired activation tokens.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->activationService->refreshExpiredTokens();
        $output->writeln('Expired tokens have been refreshed.');
        return Command::SUCCESS;
    }
}
