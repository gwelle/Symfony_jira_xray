<?php 

namespace App\Registry;

use App\Interfaces\EmailStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class EmailStrategyRegistry
{
    /** @var EmailStrategyInterface[] */
    private array $strategies = [];

    /**
     * EmailStrategyRegistry constructor.
     * @param iterable<EmailStrategyInterface> $strategies
     */
    public function __construct(
        #[TaggedIterator('app.email_strategy')]
        iterable $strategies
        )
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->getName()] = $strategy;
        }
    }

    /**
     * Get the email strategy by name.
     * @param string $name
     * @return EmailStrategyInterface
     */
    public function get(string $name): EmailStrategyInterface
    {
        if (!isset($this->strategies[$name])) {
            throw new \InvalidArgumentException("Stratégie inconnue : $name");
        }
        return $this->strategies[$name];
    }
}
