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
        
        $createdAt = $this->faker->dateTimeBetween('2026-01-01', '2026-12-31');
        $updatedAt = $this->faker->dateTimeBetween($createdAt, '2026-12-31');
        $isPaying = $this->faker->boolean(70);

        return [
            'first_name' => $firstName,
            'middle_initial' => $this->faker->boolean(80) ? $this->faker->randomLetter() : null,
            'last_name' => $lastName,
            'birthdate' => $this->faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            
            'program' => $program['code'],
            'program_id' => $program['id'],
            
            'section' => '3-1',
            'year_level' => '3rd',
            'year_level_confirmed' => true,
            
            'contact_number' => '09' . $this->faker->numerify('#########'),
            
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Tagaytay', 'Silang', 'Dasmariñas', 'General Trias', 'Imus', 'Bacoor', 'Cavite City', 'Trece Martires', 'Calamba', 'Santa Rosa']),
            'province' => 'Cavite', // Simplification based on cities
            
            'is_paying_student' => $isPaying,
            'role' => 'student',
            
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
            
            'email' => $email,
            'student_id' => $studentId,
            'email_verified_at' => now(),
            
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
        $weightedCodes = [
            'BSIT' => 20,
            'BSCS' => 15,
            'BSHM' => 12,
            'BSTM' => 10,
            'BSBA-MM' => 7,
            'BSBA-HRDM' => 7,
            'BSOA' => 6,
            'BSPsych' => 6,
            'BSSW' => 5,
            'BSESS-FSM' => 4,
            'BSED-ENG' => 3,
            'BSED-FIL' => 2,
            'BSED-MATH' => 3,
            'BSED-SS' => 3,
            'BSED-GS' => 3,
            'BSBA' => 4,
            'BEED' => 3,
            'BSED' => 2,
            'BSA' => 2,
            'ABPsy' => 2,
        ];

        $totalWeight = array_sum($weightedCodes);
        $random = mt_rand(1, $totalWeight);
        $chosenCode = 'BSIT';

        $currentWeight = 0;
        foreach ($weightedCodes as $code => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                $chosenCode = $code;
                break;
            }
        }

        $program = \App\Models\Program::where('code', $chosenCode)->first();

        if (! $program) {
            $program = \App\Models\Program::first();
        }

        if ($program) {
            return [
                'id' => $program->id,
                'code' => $program->code ?? $chosenCode,
            ];
        }

        return [
            'id' => null,
            'code' => $chosenCode,
        ];
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

    public function seeded(): static
    {
        return $this->state(function (array $attributes) {
            $yearLevels = ['1st', '2nd', '3rd', '4th'];
            $yearLevel = $this->faker->randomElement($yearLevels);
            $yearNum = $yearLevel[0];
            $section = $yearNum . '-' . $this->faker->numberBetween(1, 4);

            $isActive = $this->faker->boolean(95);
            $isVerified = $this->faker->boolean(90);
            $admin = $isActive ? null : User::whereIn('role', ['system_admin', 'guidance_counselor'])->first();

            return [
                'year_level' => $yearLevel,
                'section' => $section,
                'year_level_confirmed' => $this->faker->boolean(90),
                'is_active' => $isActive,
                'deactivated_at' => $isActive ? null : $this->faker->dateTimeBetween('-1 year', 'now'),
                'deactivated_by' => $isActive ? null : ($admin ? $admin->id : null),
                'email_verified_at' => $isVerified ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            ];
        });
    }
}
