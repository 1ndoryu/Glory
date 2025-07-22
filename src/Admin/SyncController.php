<?php

namespace Glory\Admin;

class SyncController
{
    private SyncManager $syncManager;

    public function __construct()
    {
        $this->syncManager = new SyncManager();
    }

    public function register(): void
    {
        $this->syncManager->registerHooks();
    }
}