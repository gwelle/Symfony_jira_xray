<?php

namespace App\Message;

class EmailSender
{
    /**
     * Constructor
     * @param string $strategyName
     * @param int $userId
     * @param string $token
     * Propriétés publiques (public) pour que Messenger puisse les sérialiser / désérialiser
     * quand il met le message dans la base ou le queue.
     */
    public function __construct(
        public string $strategyName, 
        public int $userId, 
        public ?string $token = null
    ){}
}
