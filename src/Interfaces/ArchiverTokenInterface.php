<?php

namespace App\Interfaces;

interface ArchiverTokenInterface
{
    /**
     * Archives the last token of a user.
     * @param ActivationToken $lastToken The activation token to be archived.
     * @return void
     */
    public function archiveLastToken(ActivationToken $lastToken): void;
}