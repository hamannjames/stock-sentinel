<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Console\Command;

class SeedRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // seed admin role and permissions to create user
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Site administrator, highest level permissions'
        ]);

        $manageUsers = Permission::firstOrCreate([
            'name' => 'manage-users',
            'display_name' => 'Manage Users',
            'description' => 'Permission to create, edit, and delete users'
        ]);

        $admin->attachPermission($manageUsers);
    }
}
