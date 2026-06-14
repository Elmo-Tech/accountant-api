<?php

namespace Database\Seeders\Roles;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // premissions
        $permissions = [
            'all_users',
            'create_user',
            'edit_user',
            'update_user',
            'delete_user',
            'change_user_status',

            'all_roles',
            'create_role',
            'edit_role',
            'update_role',
            'delete_role',

            'all_clients',
            'create_client',
            'edit_client',
            'update_client',
            'delete_client',

            'all_client_addresses',
            'create_client_address',
            'edit_client_address',
            'update_client_address',
            'delete_client_address',

            'all_client_contacts',
            'create_client_contact',
            'edit_client_contact',
            'update_client_contact',
            'delete_client_contact',

            'all_client_service_discounts',
            'create_client_service_discount',
            'edit_client_service_discount',
            'update_client_service_discount',
            'delete_client_service_discount',

            'all_service_categories',
            'create_service_category',
            'edit_service_category',
            'update_service_category',
            'delete_service_category',

            'all_tasks',
            'create_task',
            'edit_task',
            'update_task',
            'delete_task',

            'all_admin_tasks',

            'all_task_time_logs',
            'create_task_time_log',
            'edit_task_time_log',
            'update_task_time_log',
            //'delete_task_time_log',

            'all_invoices',
            'create_invoice',
            'edit_invoice',
            'update_invoice',
            'delete_invoice',

            'all_parameters',
            'create_parameter',
            'edit_parameter',
            'update_parameter',
            'delete_parameter',

            'all_active_tasks',
            'update_active_task',

            'all_reports',

            'client_payment_type',
            'client_payment_period'


        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], [
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // roles
        $superAdmin = Role::create(['name' => 'superAdmin']);
        $superAdmin->givePermissionTo(Permission::get());

        $accountant = Role::create(['name' => 'admin']);
        $accountant->givePermissionTo([
            'all_users',
            'create_user',
            'edit_user',
            'update_user',
            'delete_user',
            'change_user_status',

            'all_roles',
            'create_role',
            'edit_role',
            'update_role',
            'delete_role',

            'all_clients',
            'create_client',
            'edit_client',
            'update_client',
            'delete_client',

            'all_client_addresses',
            'create_client_address',
            'edit_client_address',
            'update_client_address',
            'delete_client_address',

            'all_client_contacts',
            'create_client_contact',
            'edit_client_contact',
            'update_client_contact',
            'delete_client_contact',

            'all_client_service_discounts',
            'create_client_service_discount',
            'edit_client_service_discount',
            'update_client_service_discount',
            'delete_client_service_discount',

            'all_service_categories',
            'create_service_category',
            'edit_service_category',
            'update_service_category',
            'delete_service_category',

            'all_tasks',
            'create_task',
            'edit_task',
            'update_task',
            'delete_task',

            'all_task_time_logs',
            'create_task_time_log',
            'edit_task_time_log',
            'update_task_time_log',
            //'delete_task_time_log',

            'all_invoices',
            'create_invoice',
            'edit_invoice',
            'update_invoice',
            'delete_invoice',

            'all_parameters',
            'create_parameter',
            'edit_parameter',
            'update_parameter',
            'delete_parameter',

            'all_active_tasks',
            'update_active_task',

            'all_reports',

            'client_payment_type',
            'client_payment_period'
        ]);



    }
}
