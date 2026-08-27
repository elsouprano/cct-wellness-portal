<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = $this->getFilipinoFirstName();
        $lastName = $this->getFilipinoLastName();
        
        $baseEmail = strtolower(preg_replace('/[^a-z0-9]/i', '', $firstName)) . '.' . 
                     strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName));
        
        $email = $this->generateUniqueEmail($baseEmail . '@citycollegeoftagaytay.edu.ph', $baseEmail);
        $studentId = $this->generateUniqueStudentId();
        
        $program = $this->getWeightedProgram();
        
        $yearLevels = ['1st', '2nd', '3rd', '4th'];
        $yearLevel = $this->faker->randomElement($yearLevels);
        
        // Extract number for section (e.g. 1st -> 1)
        $yearNum = $yearLevel[0];
        $section = $yearNum . '-' . $this->faker->numberBetween(1, 4);

        $isActive = $this->faker->boolean(95);
        $isVerified = $this->faker->boolean(90);
        $isPaying = $this->faker->boolean(70);

        // Fetch a valid admin user for deactivated_by if needed
        $adminId = null;
        if (!$isActive) {
            $admin = User::whereIn('role', ['system_admin', 'guidance_counselor'])->first();
            $adminId = $admin ? $admin->id : null;
        }

        $createdAt = $this->faker->dateTimeBetween('2026-01-01', '2026-12-31');
        $updatedAt = $this->faker->dateTimeBetween($createdAt, '2026-12-31');

        return [
            'first_name' => $firstName,
            'middle_initial' => $this->faker->boolean(80) ? $this->faker->randomLetter() : null,
            'last_name' => $lastName,
            'birthdate' => $this->faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            
            'program' => $program['code'],
            'program_id' => $program['id'],
            
            'section' => $section,
            'year_level' => $yearLevel,
            'year_level_confirmed' => $this->faker->boolean(90),
            
            'contact_number' => '09' . $this->faker->numerify('#########'),
            
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Tagaytay', 'Silang', 'Dasmariñas', 'General Trias', 'Imus', 'Bacoor', 'Cavite City', 'Trece Martires', 'Calamba', 'Santa Rosa']),
            'province' => 'Cavite', // Simplification based on cities
            
            'is_paying_student' => $isPaying,
            'role' => 'student',
            
            'is_active' => $isActive,
            'deactivated_at' => $isActive ? null : $this->faker->dateTimeBetween('-1 year', 'now'),
            'deactivated_by' => $isActive ? null : $adminId,
            
            'email' => $email,
            'student_id' => $studentId,
            'email_verified_at' => $isVerified ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => null,
            'profile_picture_path' => null,

            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function getFilipinoFirstName(): string
    {
        $names = ['Juan', 'Maria', 'John', 'Mark', 'Joshua', 'Angelo', 'Christian', 'Miguel', 'Andrea', 'Nicole', 'Jasmine', 'Samantha', 'Carlo', 'Daniel', 'Jose', 'Pedro', 'Grace', 'Joy', 'Michael', 'Gabriel'];
        return $this->faker->randomElement($names);
    }

    private function getFilipinoLastName(): string
    {
        $names = ['Dela Cruz', 'Garcia', 'Reyes', 'Ramos', 'Mendoza', 'Santos', 'Flores', 'Gonzales', 'Bautista', 'Villanueva', 'Fernandez', 'Cruz', 'De Leon', 'Ocampo', 'Tolentino', 'Ilagan'];
        return $this->faker->randomElement($names);
    }

    private function getWeightedProgram(): array
    {
        $programs = [
            ['id' => 1, 'code' => 'BSIT', 'weight' => 25],
            ['id' => 2, 'code' => 'BSCS', 'weight' => 15],
            ['id' => 5, 'code' => 'BSBA', 'weight' => 15],
            ['id' => 6, 'code' => 'BSHM', 'weight' => 12],
            ['id' => 7, 'code' => 'BSTM', 'weight' => 10],
            ['id' => 8, 'code' => 'BEED', 'weight' => 8],
            ['id' => 9, 'code' => 'BSED', 'weight' => 8],
            ['id' => 10, 'code' => 'AB Psych', 'weight' => 7],
        ];

        $totalWeight = array_sum(array_column($programs, 'weight'));
        $random = mt_rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($programs as $program) {
            $currentWeight += $program['weight'];
            if ($random <= $currentWeight) {
                return $program;
            }
        }
        
        return $programs[0];
    }

    private function generateUniqueEmail(string $email, string $base): string
    {
        $counter = 2;
        $originalEmail = $email;
        while (User::where('email', $email)->exists()) {
            $email = $base . $counter . '@citycollegeoftagaytay.edu.ph';
            $counter++;
        }
        return $email;
    }

    private function generateUniqueStudentId(): string
    {
        do {
            // Using 2026 as the base year for generated IDs to look consistent
            $studentId = '2026' . $this->faker->numerify('######');
        } while (User::where('student_id', $studentId)->exists());
        
        return $studentId;
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
