<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class ProjectScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente Cronograma']);
        $this->project = Project::factory()->for($client)->create(['name' => 'Projeto Cronograma']);
    }

    public function test_guest_cannot_access_project_schedule(): void
    {
        $this->get(route('project-schedules.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_open_the_spreadsheet_for_a_project(): void
    {
        User::factory()->create(['name' => 'Ana Responsável']);
        User::factory()->create(['name' => 'Bruno Inativo', 'active' => false]);
        $this->project->scheduleRows()->create([
            'position' => 1,
            'responsible' => 'Nome importado',
        ]);

        $this->actingAs($this->user)
            ->get(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertOk()
            ->assertSee('Projeto Cronograma')
            ->assertSee('Column 1')
            ->assertSee('Sugestão IA')
            ->assertSee('Quantidade de horas')
            ->assertSee('Adicionar nova linha')
            ->assertSee('Adicionar coluna')
            ->assertSee('Atualizar usando uma planilha Excel')
            ->assertSee('<option value="Ana Responsável">', false)
            ->assertSee('Bruno Inativo', false)
            ->assertSee('· Inativo', false)
            ->assertSee('Nome importado · Não cadastrado', false);
    }

    public function test_user_can_save_all_schedule_columns(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [[
                'column_1' => '1',
                'column_2' => '1.1',
                'demand' => 'Primeiro contato com o cliente',
                'ai_suggestion' => 'Primeiro contato',
                'completion_status' => 'Em andamento',
                'execution_date' => '2026-07-28',
                'responsible' => 'Pablo',
                'client_responsible' => 'Maria',
                'client_contact' => '(11) 99999-9999',
                'scope' => 'Tópicos do projeto',
                'completed_demands' => 'Contato realizado',
                'remaining_work' => 'Validar escopo',
                'completion_date' => '2026-07-31',
                'hours' => '4.50',
            ]]],
        );

        $response->assertRedirect(route('project-schedules.index', ['project_id' => $this->project->id]));
        $this->assertDatabaseHas('project_schedule_rows', [
            'project_id' => $this->project->id,
            'position' => 1,
            'column_1' => '1',
            'column_2' => '1.1',
            'demand' => 'Primeiro contato com o cliente',
            'completion_status' => 'Em andamento',
            'responsible' => 'Pablo',
            'hours' => 4.50,
        ]);
    }

    public function test_saving_again_removes_deleted_rows_and_reorders_the_others(): void
    {
        $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [
                ['demand' => 'Linha removida'],
                ['demand' => 'Linha mantida'],
            ]],
        );

        $this->actingAs($this->user)->put(
            route('project-schedules.update', $this->project),
            ['rows' => [['demand' => 'Linha mantida']]],
        )->assertSessionHas('success');

        $this->assertDatabaseCount('project_schedule_rows', 1);
        $this->assertDatabaseHas('project_schedule_rows', [
            'project_id' => $this->project->id,
            'position' => 1,
            'demand' => 'Linha mantida',
        ]);
        $this->assertDatabaseMissing('project_schedule_rows', ['demand' => 'Linha removida']);
    }

    public function test_schedule_rejects_invalid_status_and_negative_hours(): void
    {
        $this->actingAs($this->user)
            ->from(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->put(route('project-schedules.update', $this->project), [
                'rows' => [[
                    'completion_status' => 'Talvez',
                    'hours' => -1,
                ]],
            ])
            ->assertSessionHasErrors(['rows.0.completion_status', 'rows.0.hours']);

        $this->assertDatabaseCount('project_schedule_rows', 0);
    }

    public function test_user_can_add_a_custom_column_and_save_its_values(): void
    {
        $this->actingAs($this->user)
            ->get(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertOk();

        $this->actingAs($this->user)
            ->post(route('project-schedules.columns.store', $this->project), [
                'label' => 'Observações',
                'type' => 'textarea',
            ])
            ->assertSessionHas('success');

        $column = $this->project->scheduleColumns()->where('label', 'Observações')->firstOrFail();
        $this->assertTrue($column->is_custom);
        $this->assertSame(15, $column->position);

        $this->actingAs($this->user)
            ->put(route('project-schedules.update', $this->project), [
                'rows' => [[
                    'demand' => 'Revisar planejamento',
                    'custom_data' => [$column->column_key => 'Prioridade alta'],
                ]],
            ])
            ->assertSessionHas('success');

        $row = $this->project->scheduleRows()->firstOrFail();
        $this->assertSame('Prioridade alta', $row->custom_data[$column->column_key]);
    }

    public function test_user_can_move_columns_left_and_right(): void
    {
        $this->actingAs($this->user)
            ->get(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertOk();

        $responsible = $this->project->scheduleColumns()->where('column_key', 'responsible')->firstOrFail();
        $executionDate = $this->project->scheduleColumns()->where('column_key', 'execution_date')->firstOrFail();

        $this->actingAs($this->user)
            ->patch(route('project-schedules.columns.move', [$this->project, $responsible]), [
                'direction' => 'left',
            ])
            ->assertSessionHas('success');

        $this->assertSame(6, $responsible->refresh()->position);
        $this->assertSame(7, $executionDate->refresh()->position);

        $this->actingAs($this->user)
            ->patch(route('project-schedules.columns.move', [$this->project, $responsible]), [
                'direction' => 'right',
            ])
            ->assertSessionHas('success');

        $this->assertSame(7, $responsible->refresh()->position);
        $this->assertSame(6, $executionDate->refresh()->position);
    }

    public function test_user_can_remove_only_custom_columns(): void
    {
        $this->actingAs($this->user)
            ->get(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertOk();
        $standard = $this->project->scheduleColumns()->where('column_key', 'demand')->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('project-schedules.columns.destroy', [$this->project, $standard]))
            ->assertSessionHasErrors('column');
        $this->assertDatabaseHas('project_schedule_columns', ['id' => $standard->id]);

        $this->actingAs($this->user)->post(
            route('project-schedules.columns.store', $this->project),
            ['label' => 'Coluna temporária', 'type' => 'text'],
        );
        $custom = $this->project->scheduleColumns()->where('label', 'Coluna temporária')->firstOrFail();

        $this->actingAs($this->user)
            ->delete(route('project-schedules.columns.destroy', [$this->project, $custom]))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('project_schedule_columns', ['id' => $custom->id]);
    }

    public function test_user_can_replace_the_schedule_with_the_standard_excel_template(): void
    {
        $this->project->scheduleRows()->create([
            'position' => 1,
            'demand' => 'Linha antiga',
        ]);
        $spreadsheet = $this->spreadsheet();

        try {
            $response = $this->actingAs($this->user)->post(
                route('project-schedules.import', $this->project),
                ['spreadsheet' => $spreadsheet],
            );
        } finally {
            @unlink($spreadsheet->getPathname());
        }

        $response
            ->assertRedirect(route('project-schedules.index', ['project_id' => $this->project->id]))
            ->assertSessionHas('success', '1 linha importada da aba "Cronograma".');

        $this->assertDatabaseCount('project_schedule_rows', 1);
        $this->assertDatabaseMissing('project_schedule_rows', ['demand' => 'Linha antiga']);
        $this->assertDatabaseHas('project_schedule_rows', [
            'project_id' => $this->project->id,
            'position' => 1,
            'column_1' => '1',
            'column_2' => '1.1',
            'demand' => 'Primeiro contato com o cliente',
            'ai_suggestion' => 'Primeiro contato',
            'completion_status' => 'Não',
            'execution_date' => '2026-07-28 00:00:00',
            'completion_date' => '2026-07-31 00:00:00',
            'hours' => 4.50,
        ]);
    }

    public function test_invalid_excel_template_keeps_the_current_schedule(): void
    {
        $this->project->scheduleRows()->create([
            'position' => 1,
            'demand' => 'Linha preservada',
        ]);
        $spreadsheet = $this->spreadsheet(validHeaders: false);

        try {
            $response = $this->actingAs($this->user)->post(
                route('project-schedules.import', $this->project),
                ['spreadsheet' => $spreadsheet],
            );
        } finally {
            @unlink($spreadsheet->getPathname());
        }

        $response->assertSessionHasErrors('spreadsheet');
        $this->assertDatabaseCount('project_schedule_rows', 1);
        $this->assertDatabaseHas('project_schedule_rows', ['demand' => 'Linha preservada']);
    }

    private function spreadsheet(bool $validHeaders = true): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cronograma-xlsx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                <Default Extension="xml" ContentType="application/xml"/>
                <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
                <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
            </Types>
            XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
            </Relationships>
            XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                <sheets><sheet name="Cronograma" sheetId="1" r:id="rId1"/></sheets>
            </workbook>
            XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
            </Relationships>
            XML);

        $headers = [
            'Column 1',
            'Column 2',
            'Demandas',
            'Sugestão Ia',
            'Foi feito?',
            'Data Execução',
            'Responsável',
            'Responsável Cliente',
            'Contato Cliente',
            'Escopo',
            'Demandas realizadas',
            'O que falta',
            'Quando finaliza',
            $validHeaders ? 'Quantidade de horas' : 'Coluna inválida',
        ];
        $headerCells = '';

        foreach ($headers as $index => $header) {
            $letter = chr(65 + $index);
            $header = htmlspecialchars($header, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $headerCells .= "<c r=\"{$letter}3\" t=\"inlineStr\"><is><t>{$header}</t></is></c>";
        }

        $sheet = <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                <sheetData>
                    <row r="3">{$headerCells}</row>
                    <row r="4">
                        <c r="A4"><v>1</v></c>
                        <c r="B4" t="inlineStr"><is><t>1.1</t></is></c>
                        <c r="C4" t="inlineStr"><is><t>Primeiro contato com o cliente</t></is></c>
                        <c r="D4" t="inlineStr"><is><t>Primeiro contato</t></is></c>
                        <c r="E4" t="inlineStr"><is><t>Não</t></is></c>
                        <c r="F4"><v>46231</v></c>
                        <c r="G4" t="inlineStr"><is><t>Pablo</t></is></c>
                        <c r="H4" t="inlineStr"><is><t>Maria</t></is></c>
                        <c r="I4" t="inlineStr"><is><t>(11) 99999-9999</t></is></c>
                        <c r="J4" t="inlineStr"><is><t>Tópicos</t></is></c>
                        <c r="K4" t="inlineStr"><is><t>Contato realizado</t></is></c>
                        <c r="L4" t="inlineStr"><is><t>Validar escopo</t></is></c>
                        <c r="M4" t="inlineStr"><is><t>31/07/2026</t></is></c>
                        <c r="N4" t="inlineStr"><is><t>4,5</t></is></c>
                    </row>
                    <row r="8"><c r="D8" t="inlineStr"><is><t>Legenda que não deve ser importada</t></is></c></row>
                </sheetData>
            </worksheet>
            XML;

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return new UploadedFile(
            $path,
            'Cronograma.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
