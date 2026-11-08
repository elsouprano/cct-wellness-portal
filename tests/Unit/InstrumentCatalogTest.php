<?php

namespace Tests\Unit;

use App\Models\InventoryScore;
use App\Models\QuestionCategory;
use App\Support\InstrumentCatalog;
use App\Support\InstrumentLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstrumentCatalogTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        InstrumentLabel::clearCache();
    }

    public function test_catalog_returns_official_name_with_acronym_in_parentheses(): void
    {
        $dass = InstrumentCatalog::get('dass21');
        $this->assertEquals('Depression Anxiety Stress Scales (DASS-21)', $dass);

        $bpaq = InstrumentCatalog::get('bpaq');
        $this->assertEquals('Buss-Perry Aggression Questionnaire (BPAQ)', $bpaq);

        $erq = InstrumentCatalog::get('erq');
        $this->assertEquals('Emotion Regulation Questionnaire (ERQ)', $erq);

        $ffmq = InstrumentCatalog::get('ffmq');
        $this->assertEquals('Five Facet Mindfulness Questionnaire (FFMQ)', $ffmq);

        $sdq = InstrumentCatalog::get('sdq');
        $this->assertEquals('Strengths and Difficulties Questionnaire (SDQ)', $sdq);

        $audit = InstrumentCatalog::get('audit');
        $this->assertEquals('Alcohol Use Disorders Identification Test (AUDIT)', $audit);

        $headsss = InstrumentCatalog::get('rapid_headsss');
        $this->assertEquals('Rapid HEADSSS Assessment (RAPID-HEADSSS) (Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety)', $headsss);

        $eat = InstrumentCatalog::get('eat');
        $this->assertEquals('Eating Assessment Tool (EAT-10)', $eat);

        $ars30 = InstrumentCatalog::get('ars30');
        $this->assertEquals('Academic Resilience Scale (ARS-30)', $ars30);
    }

    public function test_unconfirmed_codes_return_null_from_catalog(): void
    {
        $this->assertNull(InstrumentCatalog::get('caq'));
        $this->assertNull(InstrumentCatalog::get('unknown_xyz'));
    }

    public function test_instrument_label_falls_back_to_catalog(): void
    {
        $this->assertEquals('Depression Anxiety Stress Scales (DASS-21)', InstrumentLabel::for('dass21'));
        $this->assertEquals('Academic Resilience Scale (ARS-30)', InstrumentLabel::for('ars30'));
        $this->assertEquals('Interpersonal Regulation Questionnaire (IRQ)', InstrumentLabel::for('irq'));
    }

    public function test_instrument_label_falls_back_to_humanized_for_unconfirmed_codes(): void
    {
        // Acronyms should be uppercased
        $this->assertEquals('CAQ', InstrumentLabel::for('caq'));

        // Multi-word / snake_case should be Title Cased
        $this->assertEquals('Unconfirmed Category', InstrumentLabel::for('unconfirmed_category'));
    }

    public function test_instrument_label_prefers_model_display_name_when_provided(): void
    {
        $category = new QuestionCategory([
            'name' => 'custom_code',
            'display_name' => 'Custom Official Instrument (COI)',
        ]);

        $this->assertEquals('Custom Official Instrument (COI)', InstrumentLabel::for('custom_code', $category));
        $this->assertEquals('Custom Official Instrument (COI)', $category->label());
        $this->assertEquals('Custom Official Instrument (COI)', $category->label);
    }

    public function test_inventory_score_resolves_category_label(): void
    {
        $score = new InventoryScore([
            'category_name' => 'dass21',
            'subscale_name' => 'depression',
        ]);

        $this->assertEquals('Depression Anxiety Stress Scales (DASS-21)', $score->category_label);
    }
}
