<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage-profile',
            'view-berita',
            'create-berita',
            'edit-berita',
            'delete-berita',
            'view-pengumuman',
            'create-pengumuman',
            'edit-pengumuman',
            'delete-pengumuman',
            'view-agenda',
            'create-agenda',
            'edit-agenda',
            'delete-agenda',
            'view-prodi',
            'create-prodi',
            'edit-prodi',
            'delete-prodi',
            'view-kerjasama',
            'create-kerjasama',
            'edit-kerjasama',
            'delete-kerjasama',
            'view-mahasiswa',
            'create-mahasiswa',
            'edit-mahasiswa',
            'delete-mahasiswa',
            'import-mahasiswa',
            'view-karya',
            'create-karya',
            'edit-karya',
            'delete-karya',
            'approve-karya',
            'reject-karya',
            'manage-media'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $mahasiswaRole = Role::create(['name' => 'mahasiswa']);
        $mahasiswaRole->givePermissionTo([
            'view-berita',
            'view-pengumuman',
            'view-agenda',
            'view-prodi',
            'view-kerjasama',
            'view-karya',
            'create-karya',
            'edit-karya',
            'delete-karya'
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->assignRole('admin');
    }
}
