# E-IDS Part 1: Foundation — Dependencies, Config & Migrations

> **For agentic workers:** Use superpowers:executing-plans to run this plan task-by-task.

**Goal:** Get the database, config, and package dependencies ready so all subsequent parts can build on a solid base.

**Tech Stack:** Laravel 13.8, PHP 8.3, SQLite, barryvdh/laravel-dompdf

## Global Constraints
- Working directory: `/home/muqriz/personal/eidsv2`
- Database: SQLite (file already exists at `database/database.sqlite`)
- All migration files must run clean via `php artisan migrate:fresh --seed`
- No Breeze, no Jetstream, no Livewire

---

### Task 1: Install dompdf & run storage:link

**Files:**
- Modify: `composer.json` (via composer command)
- Create: `storage/app/public/photos/.gitkeep`

- [ ] **Step 1: Install dompdf**

```bash
cd /home/muqriz/personal/eidsv2 && composer require barryvdh/laravel-dompdf
```

Expected: `barryvdh/laravel-dompdf` appears in `composer.json` require block.

- [ ] **Step 2: Create storage symlink**

```bash
php artisan storage:link
```

Expected: `public/storage` symlink created pointing to `storage/app/public`.

- [ ] **Step 3: Create photos directory placeholder**

```bash
mkdir -p storage/app/public/photos && touch storage/app/public/photos/.gitkeep
```

- [ ] **Step 4: Verify**

```bash
php artisan about | grep -i dompdf
ls -la public/storage
```

Expected: dompdf listed in packages, `public/storage` is a symlink.

---

### Task 2: Create `config/eids.php`

**Files:**
- Create: `config/eids.php`

- [ ] **Step 1: Write the config file**

```php
<?php
// config/eids.php
return [
    'sample_divisor'    => 60,       // GFA ÷ 60 = N samples
    'levelling_max_mm'  => 3.0,      // levelling deviation threshold
    'joint_max_mm'      => 1.0,      // joint width threshold
    'me_score'          => 2.00,     // fixed M&E contribution
    'external_score'    => 11.80,    // fixed External Works contribution

    'rating' => [
        'baik'      => 85,           // score >= 85 → BAIK
        'sederhana' => 70,           // score >= 70 → SEDERHANA
        // below 70 → LEMAH
    ],

    'default_locations' => [
        'Living Room', 'Service Area', 'Passageway',
        'Bedroom 1', 'Bedroom 2', 'Bedroom 3', 'Bathroom',
    ],

    'components' => [
        'A1_FLOOR'   => ['name' => 'Floor (Lantai)',             'weightage' => 18],
        'A2_WALL'    => ['name' => 'Internal Wall (Dinding Dalam)', 'weightage' => 18],
        'A3_CEILING' => ['name' => 'Ceiling (Siling)',           'weightage' => 10],
        'A4_DOOR'    => ['name' => 'Door (Pintu)',               'weightage' => 10],
        'A5_WINDOW'  => ['name' => 'Window (Tingkap)',           'weightage' =>  8],
        'A6_FIXTURES'=> ['name' => 'Internal Fixtures',          'weightage' =>  5],
        'A7_ROOF'    => ['name' => 'Roof (Bumbung)',             'weightage' => 10],
        'A8_EXT_WALL'=> ['name' => 'External Wall (Dinding Luar)', 'weightage' => 10],
    ],
];
```

- [ ] **Step 2: Verify config loads**

```bash
php artisan tinker --execute="dd(config('eids.components'));"
```

Expected: array of 8 components printed.

---

### Task 3: Migration — alter users table

**Files:**
- Create: `database/migrations/2026_08_02_000001_add_role_fields_to_users_table.php`

- [ ] **Step 1: Create migration file**

```bash
php artisan make:migration add_role_fields_to_users_table --table=users
```

- [ ] **Step 2: Write migration content**

Open the generated file and replace its content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'lead_auditor', 'inspector', 'supervisor'])
                  ->default('inspector')
                  ->after('email');
            $table->string('employee_id', 50)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'employee_id']);
        });
    }
};
```

---

### Task 4: Migration — projects table

**Files:**
- Create: `database/migrations/2026_08_02_000002_create_projects_table.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_projects_table
```

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_no')->unique();
            $table->string('project_name');
            $table->string('location')->nullable();
            $table->string('developer_name');
            $table->string('contractor_name');
            $table->enum('building_type', ['teres', 'semi_d', 'banglo'])->default('teres');
            $table->integer('total_units')->default(1);
            $table->decimal('floor_area_sqm', 8, 2)->default(0.00);
            $table->integer('calculated_samples')->default(1);
            $table->decimal('overall_score', 5, 2)->default(0.00);
            $table->enum('status', ['draf', 'dalam_pemeriksaan', 'selesai'])
                  ->default('dalam_pemeriksaan');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

---

### Task 5: Migration — project_samples table

**Files:**
- Create: `database/migrations/2026_08_02_000003_create_project_samples_table.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_project_samples_table
```

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('sample_index');
            $table->string('location_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_samples');
    }
};
```

---

### Task 6: Migration — component_assessments table

**Files:**
- Create: `database/migrations/2026_08_02_000004_create_component_assessments_table.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_component_assessments_table
```

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')
                  ->constrained('project_samples')
                  ->cascadeOnDelete();
            $table->string('component_code');
            $table->string('component_name');
            $table->decimal('weightage', 5, 2);
            $table->enum('finishing_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('hollow_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('levelling_mm', 5, 2)->nullable();
            $table->enum('levelling_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('joint_mm', 5, 2)->nullable();
            $table->enum('joint_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('crack_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('overall_sample_status', ['PASS', 'FAIL'])->default('PASS');
            $table->string('photo_path')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_assessments');
    }
};
```

---

### Task 7: Migration — defects table

**Files:**
- Create: `database/migrations/2026_08_02_000005_create_defects_table.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration create_defects_table
```

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')
                  ->nullable()
                  ->constrained('component_assessments')
                  ->nullOnDelete();
            $table->string('component_name');
            $table->string('location');
            $table->text('defect_description');
            $table->string('photo_path')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])->default('OPEN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};
```

---

### Task 8: DatabaseSeeder

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Write seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'        => 'Admin E-IDS',
            'email'       => 'admin@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'admin',
            'employee_id' => 'ADMIN-001',
        ]);

        $inspector = User::create([
            'name'        => 'Ahmad Inspector',
            'email'       => 'inspector@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'inspector',
            'employee_id' => 'INSP-2024-001',
        ]);

        User::create([
            'name'        => 'Ikhwan Azmi',
            'email'       => 'auditor@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'lead_auditor',
            'employee_id' => 'AUDT-2024-001',
        ]);

        User::create([
            'name'        => 'Siti Supervisor',
            'email'       => 'supervisor@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'supervisor',
            'employee_id' => 'SUPV-2024-001',
        ]);

        $project = Project::create([
            'project_no'          => 'PRJ-2026-001',
            'project_name'        => 'Taman Merlimau Perdana',
            'location'            => 'Merlimau, Melaka',
            'developer_name'      => 'Mutiara Development Sdn Bhd',
            'contractor_name'     => 'Bina Jaya Construction Sdn Bhd',
            'building_type'       => 'teres',
            'total_units'         => 50,
            'floor_area_sqm'      => 210.00,
            'calculated_samples'  => 4,
            'overall_score'       => 0.00,
            'status'              => 'dalam_pemeriksaan',
            'created_by'          => $inspector->id,
        ]);

        $locations = ['Living Room', 'Service Area', 'Passageway', 'Bedroom 1'];
        foreach ($locations as $index => $location) {
            ProjectSample::create([
                'project_id'   => $project->id,
                'sample_index' => $index + 1,
                'location_name'=> $location,
            ]);
        }
    }
}
```

---

### Task 9: Run migrations & seed

- [ ] **Step 1: Run fresh migration with seed**

```bash
cd /home/muqriz/personal/eidsv2 && php artisan migrate:fresh --seed
```

Expected output:
```
INFO  Preparing database.
...
INFO  Running seeders.
INFO  Seeding: Database\Seeders\DatabaseSeeder
INFO  Seeded:  Database\Seeders\DatabaseSeeder (XXms)
```

- [ ] **Step 2: Verify tables exist**

```bash
php artisan tinker --execute="
    echo 'Users: ' . App\Models\User::count() . PHP_EOL;
    echo 'Projects: ' . App\Models\Project::count() . PHP_EOL;
    echo 'Samples: ' . App\Models\ProjectSample::count() . PHP_EOL;
"
```

Expected:
```
Users: 4
Projects: 1
Samples: 4
```

- [ ] **Step 3: Verify config**

```bash
php artisan tinker --execute="echo config('eids.levelling_max_mm');"
```

Expected: `3`

---

**Part 1 complete.** Proceed to Part 2: Models & Middleware.
