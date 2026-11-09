<?php
namespace App\Services\Network;

use App\Models\SessionReseau;

final class NetworkCheckResult
{
    public function __construct(
        public readonly bool $verified,
        public readonly string $source,          // radius | controleur | demo | desactive
        public readonly string $message,
        public readonly ?SessionReseau $session = null,
        public readonly bool $isDemo = false,
    ) {}
}
