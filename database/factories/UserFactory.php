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
        
        $yearLevel = '3rd';
        $section = '3-1';
        $studentId = $this->generateUniqueStudentId($yearLevel);
        
        $program = $this->getWeightedProgram();
        
        $birthdate = $this->faker->dateTimeBetween('-22 years', '-20 years')->format('Y-m-d');
        
        $cityProvinceMap = [
            'Tagaytay' => 'Cavite',
            'Silang' => 'Cavite',
            'Dasmariñas' => 'Cavite',
            'General Trias' => 'Cavite',
            'Imus' => 'Cavite',
            'Bacoor' => 'Cavite',
            'Cavite City' => 'Cavite',
            'Trece Martires' => 'Cavite',
            'Alfonso' => 'Cavite',
            'Mendez' => 'Cavite',
            'Amadeo' => 'Cavite',
            'Indang' => 'Cavite',
            'Calamba' => 'Laguna',
            'Santa Rosa' => 'Laguna',
        ];
        $city = $this->faker->randomElement(array_keys($cityProvinceMap));
        $province = $cityProvinceMap[$city];
        
        $createdAt = $this->faker->dateTimeBetween('2026-01-01', '2026-10-30');
        $updatedAt = $this->faker->dateTimeBetween($createdAt, '2026-10-30');
        $isPaying = $this->faker->boolean(30);

        return [
            'first_name' => $firstName,
            'middle_initial' => $this->faker->boolean(85) ? strtoupper($this->faker->randomLetter()) : null,
            'last_name' => $lastName,
            'birthdate' => $birthdate,
            
            'program' => $program['code'],
            'program_id' => $program['id'],
            
            'section' => $section,
            'year_level' => $yearLevel,
            'year_level_confirmed' => true,
            'needs_section_confirmation' => false,
            
            'contact_number' => '09' . $this->faker->numerify('#########'),
            
            'address_line1' => $this->faker->streetAddress(),
            'city' => $city,
            'province' => $province,
            
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

    private function generateUniqueStudentId(?string $yearLevel = null): string
    {
        $prefix = match ($yearLevel) {
            '4th' => '2023',
            '3rd' => '2024',
            '2nd' => '2025',
            '1st' => '2026',
            default => '2026',
        };

        do {
            $studentId = $prefix . $this->faker->numerify('######');
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
            $yearLevel = $attributes['year_level'] ?? $this->faker->randomElement($yearLevels);
            $yearNum = $yearLevel[0];
            $section = $yearNum . '-' . $this->faker->numberBetween(1, 4);

            $birthdateRange = match ($yearLevel) {
                '1st' => ['-19 years', '-18 years'],
                '2nd' => ['-20 years', '-19 years'],
                '3rd' => ['-22 years', '-20 years'],
                '4th' => ['-24 years', '-21 years'],
                default => ['-24 years', '-18 years'],
            };
            $birthdate = $this->faker->dateTimeBetween($birthdateRange[0], $birthdateRange[1])->format('Y-m-d');

            $prefix = match ($yearLevel) {
                '4th' => '2023',
                '3rd' => '2024',
                '2nd' => '2025',
                '1st' => '2026',
                default => '2026',
            };
            do {
                $studentId = $prefix . $this->faker->numerify('######');
            } while (User::where('student_id', $studentId)->exists());

            $isActive = $this->faker->boolean(95);
            $isVerified = $this->faker->boolean(95);
            $admin = $isActive ? null : User::whereIn('role', ['system_admin', 'guidance_counselor'])->first();

            return [
                'year_level' => $yearLevel,
                'section' => $section,
                'birthdate' => $birthdate,
                'student_id' => $studentId,
                'year_level_confirmed' => true,
                'needs_section_confirmation' => false,
                'is_active' => $isActive,
                'deactivated_at' => $isActive ? null : $this->faker->dateTimeBetween('-1 year', 'now'),
                'deactivated_by' => $isActive ? null : ($admin ? $admin->id : null),
                'email_verified_at' => $isVerified ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            ];
        });
    }
}
