<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;

class StudentInformationSheetSchema
{
    public const YES_NO = ['Yes', 'No'];

    public const STUDENT_TYPES = ['Old Student', 'New Student', 'Transferee'];

    public const EMPLOYMENT_STATUSES = ['Full-time', 'Part-time', 'N/A'];

    public const COURSE_CHOICES = ['First Choice', 'Second Choice', 'Third Choice'];

    public const SPECIAL_STATUSES = [
        'Person with Disability',
        'Solo Parent',
        'Member of an Indigenous Group',
        'Indigent',
        'Not Applicable',
    ];

    public const DISABILITY_TYPES = [
        'Physical Disability',
        'Psychosocial Disability',
        'Intellectual Disability',
        'Learning Disability',
        'Mental Disability',
        'Visual Disability',
        'Hearing Disability',
        'Speech and Language Impairment',
        'Disability Due to Chronic Illness',
        'Multiple Disabilities',
        'Cancer and Rare Diseases',
        'Acquired Brain Injury',
        'Not Applicable',
    ];

    public const FAMILY_MEDICAL_CONDITIONS = [
        'Cancer',
        'Kidney Diseases',
        'Respiratory Problems',
        'Heart and other cardiovascular problems',
        'Liver diseases',
        'Rheumatism',
        'Hypertension',
        'Mental disorders',
        'Substance-related disorders',
        'None that I know of.',
    ];

    public const SUBSTANCE_FREQUENCIES = [
        'Not Anymore',
        'Never',
        'Seldom',
        'Sometimes',
        'Always',
        'Habitually',
    ];

    public const RESIDENCE_NATURES = [
        "Parents' house",
        "Relative's house",
        'Boarding house/Dormitory',
        'Own Residence',
    ];

    public const RESIDENCE_OWNERSHIPS = ['Owned', 'Rented', 'N/A'];

    public const FAMILY_SETUPS = [
        'Living with both parents',
        'Living with mother',
        'Living with father',
        'Living independently',
        'Living with relatives',
    ];

    public const ONE_PARENT_REASONS = [
        'Parents are separated',
        'Father is an OFW',
        'Mother is an OFW',
        'N/A',
    ];

    public const RELATIVE_RELATIONS = [
        'Grandfather',
        'Grandmother',
        'Sister',
        'Brother',
        'Aunt',
        'Uncle',
        'Cousin',
        'Others',
        'N/A',
    ];

    public const EMERGENCY_RELATIONSHIPS = [
        'Mother',
        'Father',
        'Older Sibling',
        'Spouse',
        'Guardian / Others',
    ];

    public const MONTHLY_INCOMES = [
        'PhP 10,000- below',
        'PhP 10,001- 20,000',
        'PhP 20,001- 30,000',
        'PhP 30,001- 40,000',
        'Above PhP 40,000',
    ];

    public const ORG_MEMBERSHIP = ['Yes', 'No', 'Before'];

    public const GUIDANCE_CONCERNS = [
        'Academics',
        'Financial',
        'Family',
        'Health',
        'Home',
        'Career',
    ];

    public static function defaultsForUser(User $user, array $saved = []): array
    {
        $userProgram = $user->structuredProgram?->name ?? $user->program ?? '';
        $fullName = trim(implode(' ', array_filter([
            $user->last_name ? $user->last_name . ',' : null,
            $user->first_name,
            $user->middle_initial,
        ])));

        $defaults = [
            // Part 1: Personal Data
            'full_name' => $fullName,
            'nickname' => '',
            'sex' => $user->gender ?? '',
            'place_of_birth' => '',
            'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : '',
            'civil_status' => '',
            'address_line1' => $user->address_line1 ?? '',
            'city' => $user->city ?? '',
            'province' => $user->province ?? '',
            'citizenship' => 'Filipino',
            'religion' => '',
            'course' => $userProgram,
            'student_number' => $user->student_id ?? '',
            'section' => $user->section ?? '',
            'type_of_student' => '',
            'school_year_admitted' => '',
            'phone' => $user->contact_number ?? '',
            'personal_email' => '',
            'school_email' => $user->email ?? '',
            'is_working_student' => '',
            'employment_status' => 'N/A',
            'company_name' => 'N/A',
            'company_address' => 'N/A',

            // Part 2: Educational & Health Background
            'elementary_school' => '',
            'elementary_year_graduated' => '',
            'secondary_school' => '',
            'secondary_year_graduated' => '',
            'attended_als' => 'No',
            'als_school' => 'N/A',
            'als_year_graduated' => 'N/A',
            'tertiary_school' => 'N/A',
            'tertiary_last_year_attended' => 'N/A',
            'course_choice' => '',
            'special_status' => '',
            'disability_type' => 'Not Applicable',
            'hospitalized_before' => '',
            'hospitalization_cause' => 'N/A',
            'family_medical_conditions' => [],
            'smoking_frequency' => '',
            'liquor_frequency' => '',
            'coffee_frequency' => '',
            'prohibited_drugs_frequency' => '',
            'psych_consultation_before' => '',
            'psych_consultation_date' => 'N/A',
            'psych_consultation_reason' => 'N/A',

            // Part 3: Family Background & Residence
            'residence_nature' => '',
            'residence_ownership' => '',
            'boarding_house_address' => 'N/A',
            'family_setup' => '',
            'one_parent_reason' => 'N/A',
            'living_with_relatives_relation' => 'N/A',
            'father_name' => '',
            'father_age' => '',
            'father_contact' => '',
            'father_email' => '',
            'father_education' => '',
            'father_occupation' => '',
            'mother_name' => '',
            'mother_age' => '',
            'mother_contact' => '',
            'mother_email' => '',
            'mother_education' => '',
            'mother_occupation' => '',
            'emergency_contact_name' => '',
            'emergency_relationship' => '',
            'emergency_contact_number' => '',
            'emergency_email' => '',
            'emergency_address' => '',
            'siblings_names' => '',
            'siblings_ages' => '',
            'siblings_education_occupation' => '',
            'family_monthly_income' => '',

            // Part 4: Interests, Personality Traits & Guidance Concerns
            'interests_hobbies_skills' => '',
            'awards_recognitions' => '',
            'cct_org_member' => '',
            'cct_org_name' => 'N/A',
            'strengths_traits' => '',
            'traits_to_change' => '',
            'personal_goals' => '',
            'happiness_source' => '',
            'worries_difficulties' => '',
            'college_success_plan' => '',
            'close_friend_name' => '',
            'guidance_concerns' => [],
            'guidance_concerns_explanation' => 'N/A',
            'residence_google_maps_link' => '',
        ];

        $merged = array_merge($defaults, $saved);
        foreach (['family_medical_conditions', 'guidance_concerns'] as $arrayKey) {
            if (! is_array($merged[$arrayKey])) {
                $merged[$arrayKey] = [];
            }
        }

        return $merged;
    }

    public static function validationRules(): array
    {
        return [
            // Part 1: Personal Data
            'sis.nickname' => ['required', 'string', 'max:100'],
            'sis.sex' => ['nullable', Rule::in(User::GENDERS)],
            'sis.place_of_birth' => ['required', 'string', 'max:150'],
            'sis.birthdate' => ['required', 'date', 'before:today'],
            'sis.civil_status' => ['nullable', 'string', 'max:50'],
            'sis.address_line1' => ['required', 'string', 'max:255'],
            'sis.city' => ['required', 'string', 'max:100'],
            'sis.province' => ['required', 'string', 'max:100'],
            'sis.citizenship' => ['required', 'string', 'max:100'],
            'sis.religion' => ['required', 'string', 'max:100'],
            'sis.course' => ['required', 'string', 'max:200'],
            'sis.student_number' => ['required', 'string', 'max:60'],
            'sis.section' => ['required', 'string', 'max:60'],
            'sis.type_of_student' => ['required', Rule::in(self::STUDENT_TYPES)],
            'sis.school_year_admitted' => ['required', 'string', 'max:50'],
            'sis.phone' => ['required', 'regex:/^[0-9]{11}\z/'],
            'sis.personal_email' => ['required', 'email', 'max:150'],
            'sis.school_email' => ['required', 'email', 'max:150'],
            'sis.is_working_student' => ['required', Rule::in(self::YES_NO)],
            'sis.employment_status' => ['required', Rule::in(self::EMPLOYMENT_STATUSES)],
            'sis.company_name' => ['required', 'string', 'max:200'],
            'sis.company_address' => ['required', 'string', 'max:255'],

            // Part 2: Educational & Health Background
            'sis.elementary_school' => ['required', 'string', 'max:200'],
            'sis.elementary_year_graduated' => ['required', 'string', 'max:50'],
            'sis.secondary_school' => ['required', 'string', 'max:200'],
            'sis.secondary_year_graduated' => ['required', 'string', 'max:50'],
            'sis.attended_als' => ['required', Rule::in(self::YES_NO)],
            'sis.als_school' => ['required', 'string', 'max:200'],
            'sis.als_year_graduated' => ['required', 'string', 'max:50'],
            'sis.tertiary_school' => ['required', 'string', 'max:200'],
            'sis.tertiary_last_year_attended' => ['required', 'string', 'max:50'],
            'sis.course_choice' => ['required', Rule::in(self::COURSE_CHOICES)],
            'sis.special_status' => ['required', Rule::in(self::SPECIAL_STATUSES)],
            'sis.disability_type' => ['required', Rule::in(self::DISABILITY_TYPES)],
            'sis.hospitalized_before' => ['required', Rule::in(self::YES_NO)],
            'sis.hospitalization_cause' => ['required', 'string', 'max:255'],
            'sis.family_medical_conditions' => ['required', 'array', 'min:1'],
            'sis.family_medical_conditions.*' => ['string', Rule::in(self::FAMILY_MEDICAL_CONDITIONS)],
            'sis.smoking_frequency' => ['required', Rule::in(self::SUBSTANCE_FREQUENCIES)],
            'sis.liquor_frequency' => ['required', Rule::in(self::SUBSTANCE_FREQUENCIES)],
            'sis.coffee_frequency' => ['required', Rule::in(self::SUBSTANCE_FREQUENCIES)],
            'sis.prohibited_drugs_frequency' => ['nullable', Rule::in(self::SUBSTANCE_FREQUENCIES)],
            'sis.psych_consultation_before' => ['required', Rule::in(self::YES_NO)],
            'sis.psych_consultation_date' => ['required', 'string', 'max:150'],
            'sis.psych_consultation_reason' => ['required', 'string', 'max:255'],

            // Part 3: Residence, Family Background & Emergency Contact
            'sis.residence_nature' => ['required', Rule::in(self::RESIDENCE_NATURES)],
            'sis.residence_ownership' => ['required', Rule::in(self::RESIDENCE_OWNERSHIPS)],
            'sis.boarding_house_address' => ['required', 'string', 'max:255'],
            'sis.family_setup' => ['required', Rule::in(self::FAMILY_SETUPS)],
            'sis.one_parent_reason' => ['required', Rule::in(self::ONE_PARENT_REASONS)],
            'sis.living_with_relatives_relation' => ['required', Rule::in(self::RELATIVE_RELATIONS)],
            'sis.father_name' => ['required', 'string', 'max:150'],
            'sis.father_age' => ['required', 'string', 'max:30'],
            'sis.father_contact' => ['required', 'string', 'max:50'],
            'sis.father_email' => ['nullable', 'email', 'max:150'],
            'sis.father_education' => ['required', 'string', 'max:150'],
            'sis.father_occupation' => ['required', 'string', 'max:150'],
            'sis.mother_name' => ['required', 'string', 'max:150'],
            'sis.mother_age' => ['required', 'string', 'max:30'],
            'sis.mother_contact' => ['required', 'string', 'max:50'],
            'sis.mother_email' => ['nullable', 'email', 'max:150'],
            'sis.mother_education' => ['required', 'string', 'max:150'],
            'sis.mother_occupation' => ['required', 'string', 'max:150'],
            'sis.emergency_contact_name' => ['required', 'string', 'max:150'],
            'sis.emergency_relationship' => ['required', Rule::in(self::EMERGENCY_RELATIONSHIPS)],
            'sis.emergency_contact_number' => ['required', 'string', 'max:50'],
            'sis.emergency_email' => ['required', 'email', 'max:150'],
            'sis.emergency_address' => ['nullable', 'string', 'max:255'],
            'sis.siblings_names' => ['required', 'string', 'max:500'],
            'sis.siblings_ages' => ['required', 'string', 'max:250'],
            'sis.siblings_education_occupation' => ['required', 'string', 'max:500'],
            'sis.family_monthly_income' => ['required', Rule::in(self::MONTHLY_INCOMES)],

            // Part 4: Interests, Personality Traits & Guidance Support
            'sis.interests_hobbies_skills' => ['required', 'string', 'max:500'],
            'sis.awards_recognitions' => ['nullable', 'string', 'max:500'],
            'sis.cct_org_member' => ['required', Rule::in(self::ORG_MEMBERSHIP)],
            'sis.cct_org_name' => ['required', 'string', 'max:255'],
            'sis.strengths_traits' => ['required', 'string', 'max:500'],
            'sis.traits_to_change' => ['required', 'string', 'max:500'],
            'sis.personal_goals' => ['required', 'string', 'max:500'],
            'sis.happiness_source' => ['required', 'string', 'max:300'],
            'sis.worries_difficulties' => ['required', 'string', 'max:500'],
            'sis.college_success_plan' => ['nullable', 'string', 'max:500'],
            'sis.close_friend_name' => ['required', 'string', 'max:150'],
            'sis.guidance_concerns' => ['nullable', 'array'],
            'sis.guidance_concerns.*' => ['string', Rule::in(self::GUIDANCE_CONCERNS)],
            'sis.guidance_concerns_explanation' => ['required', 'string', 'max:500'],
            'sis.residence_google_maps_link' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function validationAttributes(): array
    {
        return [
            'sis.nickname' => 'Nickname',
            'sis.place_of_birth' => 'Place of Birth',
            'sis.birthdate' => 'Birthdate',
            'sis.address_line1' => 'Address Line 1',
            'sis.city' => 'City',
            'sis.province' => 'Province',
            'sis.citizenship' => 'Citizenship',
            'sis.religion' => 'Religion',
            'sis.course' => 'Course / Program',
            'sis.student_number' => 'Student Number',
            'sis.section' => 'Section',
            'sis.type_of_student' => 'Type of Student',
            'sis.school_year_admitted' => 'School Year Admitted',
            'sis.phone' => 'Contact Number',
            'sis.personal_email' => 'Personal Email Address',
            'sis.school_email' => 'School Email Address',
            'sis.is_working_student' => 'Are you a Working Student?',
            'sis.employment_status' => 'Status of Employment',
            'sis.company_name' => 'Name of Company',
            'sis.company_address' => 'Company Address',
            'sis.elementary_school' => 'Elementary School Attended',
            'sis.elementary_year_graduated' => 'Elementary Year Graduated',
            'sis.secondary_school' => 'Secondary School Attended',
            'sis.secondary_year_graduated' => 'Secondary Year Graduated',
            'sis.attended_als' => 'Attended ALS?',
            'sis.als_school' => 'ALS School Attended',
            'sis.als_year_graduated' => 'ALS Year Graduated',
            'sis.tertiary_school' => 'Tertiary School Attended',
            'sis.tertiary_last_year_attended' => 'Last School Year Attended',
            'sis.course_choice' => 'Course Choice',
            'sis.special_status' => 'Special Category Status',
            'sis.disability_type' => 'Type of Disability',
            'sis.hospitalized_before' => 'Hospital Confinement History',
            'sis.hospitalization_cause' => 'Hospitalization Cause',
            'sis.family_medical_conditions' => 'Family Medical Conditions',
            'sis.smoking_frequency' => 'Do you smoke?',
            'sis.liquor_frequency' => 'Do you drink liquor?',
            'sis.coffee_frequency' => 'Do you drink coffee?',
            'sis.psych_consultation_before' => 'Psychologist/Psychiatrist Consultation',
            'sis.psych_consultation_date' => 'Consultation Date',
            'sis.psych_consultation_reason' => 'Consultation Reason',
            'sis.residence_nature' => 'Nature of Residence',
            'sis.residence_ownership' => 'Residence Ownership',
            'sis.boarding_house_address' => 'Address of Boarding House/Dormitory',
            'sis.family_setup' => 'Family Setup',
            'sis.one_parent_reason' => 'If living with one parent',
            'sis.living_with_relatives_relation' => 'If living with relatives',
            'sis.father_name' => "Father's Name",
            'sis.father_age' => "Father's Age",
            'sis.father_contact' => "Father's Contact Number",
            'sis.father_email' => "Father's Email",
            'sis.father_education' => "Father's Highest Educational Attainment",
            'sis.father_occupation' => "Father's Occupation",
            'sis.mother_name' => "Mother's Name",
            'sis.mother_age' => "Mother's Age",
            'sis.mother_contact' => "Mother's Contact Number",
            'sis.mother_email' => "Mother's Email",
            'sis.mother_education' => "Mother's Highest Educational Attainment",
            'sis.mother_occupation' => "Mother's Occupation",
            'sis.emergency_contact_name' => 'Emergency Contact Person',
            'sis.emergency_relationship' => 'Emergency Contact Relationship',
            'sis.emergency_contact_number' => 'Emergency Contact Number',
            'sis.emergency_email' => 'Emergency Contact Email',
            'sis.siblings_names' => 'Name of Sibling/s',
            'sis.siblings_ages' => 'Age of Sibling/s',
            'sis.siblings_education_occupation' => 'Sibling Educational Attainment/Occupation',
            'sis.family_monthly_income' => 'Approximate Monthly Income of the Family',
            'sis.interests_hobbies_skills' => 'Interests/Hobbies/Talents/Skills',
            'sis.cct_org_member' => 'CCT Student Organization Membership',
            'sis.cct_org_name' => 'Student Organization Name',
            'sis.strengths_traits' => 'Strengths and Positive Traits',
            'sis.traits_to_change' => 'Traits you want to change',
            'sis.personal_goals' => 'Personal Goals',
            'sis.happiness_source' => 'What gives you happiness?',
            'sis.worries_difficulties' => 'Worries/Problems/Difficulties',
            'sis.close_friend_name' => 'Close Friend at CCT',
            'sis.guidance_concerns_explanation' => 'Explanation of Concerns',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'sis.phone.regex' => 'Contact number must contain exactly 11 digits (numbers only).',
            'sis.family_medical_conditions.required' => 'Please select at least one medical condition option (or "None that I know of.").',
            'sis.family_medical_conditions.min' => 'Please select at least one medical condition option (or "None that I know of.").',
        ];
    }
}
