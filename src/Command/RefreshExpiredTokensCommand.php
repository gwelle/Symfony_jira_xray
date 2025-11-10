<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Interfaces\ExpiredTokenRefreshInterface;

#[AsCommand(
    name: 'app:refresh-expired-tokens',
    description: 'Refreshes expired activation tokens for users.',
)]
class RefreshExpiredTokensCommand extends Command
{
    private ExpiredTokenRefreshInterface $expiredTokenRefresh;

    /**
     * Constructor for RefreshExpiredTokensCommand.
     * @param ExpiredTokenRefreshInterface $expiredTokenRefresh The service responsible for activation refresh token management.
     */
    public function __construct(ExpiredTokenRefreshInterface $expiredTokenRefresh)
    {
        parent::__construct();
        $this->expiredTokenRefresh = $expiredTokenRefresh;
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
        $this->expiredTokenRefresh->refreshExpiredTokens();
        $output->writeln('Expired tokens have been refreshed.');
        return Command::SUCCESS;
    }
}
