<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteAccountJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Account $account) {}

    public function handle(AccountService $service): void
    {
        $service->performDeletion($this->account);
    }
}
