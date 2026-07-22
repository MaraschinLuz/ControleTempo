<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\TimeEntryAudit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Administrador', 'email' => 'admin@tempo.local', 'password' => 'password']);
        $manager = User::factory()->manager()->create(['name' => 'Marina Gestora', 'email' => 'gestor@tempo.local', 'password' => 'password']);
        $ana = User::factory()->create(['name' => 'Ana Colaboradora', 'email' => 'ana@tempo.local', 'password' => 'password']);
        $bruno = User::factory()->create(['name' => 'Bruno Colaborador', 'email' => 'bruno@tempo.local', 'password' => 'password']);

        foreach (['retroactive_entry_max_days' => '30', 'require_retroactive_approval' => '1', 'allow_collaborator_edit' => '1', 'allow_collaborator_delete' => '0', 'maximum_running_timer_hours' => '24'] as $key => $value) {
            Setting::create(compact('key', 'value'));
        }
        $clients = collect([
            Client::create(['name' => 'Acme Tecnologia', 'description' => 'Soluções digitais', 'active' => true]),
            Client::create(['name' => 'Horizonte Saúde', 'description' => 'Operações de saúde', 'active' => true]),
            Client::create(['name' => 'Norte Logística', 'description' => 'Transportes e distribuição', 'active' => true]),
        ]);
        $projects = collect([
            Project::create(['client_id' => $clients[0]->id, 'name' => 'Portal do Cliente', 'status' => 'active', 'estimated_hours' => 180, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(2)]),
            Project::create(['client_id' => $clients[0]->id, 'name' => 'Integração ERP', 'status' => 'active', 'estimated_hours' => 120]),
            Project::create(['client_id' => $clients[1]->id, 'name' => 'Agenda Clínica', 'status' => 'active', 'estimated_hours' => 240]),
            Project::create(['client_id' => $clients[2]->id, 'name' => 'Painel de Entregas', 'status' => 'active', 'estimated_hours' => 160]),
            Project::create(['client_id' => $clients[2]->id, 'name' => 'Aplicativo Motorista', 'status' => 'planned', 'estimated_hours' => 320]),
        ]);
        $activities = collect(['Desenvolvimento', 'Reunião', 'Testes', 'Documentação', 'Planejamento', 'Suporte', 'Implantação'])->map(fn ($name) => Activity::create(['name' => $name, 'active' => true]));

        foreach ([$ana, $bruno] as $offset => $user) {
            foreach (range(1, 5) as $day) {
                $start = now()->subDays($day + $offset)->setTime(9, 0);
                $entry = TimeEntry::create(['user_id' => $user->id, 'project_id' => $projects[($day + $offset) % 4]->id, 'activity_id' => $activities[$day % $activities->count()]->id, 'description' => 'Atividade de exemplo', 'started_at' => $start, 'ended_at' => $start->copy()->addHours(7)->addMinutes(30), 'duration_seconds' => 27000, 'entry_type' => 'timer', 'status' => 'completed', 'created_by' => $user->id]);
                TimeEntryAudit::create(['time_entry_id' => $entry->id, 'changed_by' => $user->id, 'action' => 'created', 'new_values' => $entry->getAttributes()]);
            }
        }
        $start = now()->subDays(2)->setTime(18, 0);
        TimeEntry::create(['user_id' => $ana->id, 'project_id' => $projects[0]->id, 'activity_id' => $activities[1]->id, 'description' => 'Reunião extraordinária', 'started_at' => $start, 'ended_at' => $start->copy()->addHour(), 'duration_seconds' => 3600, 'entry_type' => 'manual', 'status' => 'pending', 'created_by' => $ana->id, 'justification' => 'Esquecimento no dia da reunião.']);
        $start = now()->subDays(3)->setTime(18, 0);
        TimeEntry::create(['user_id' => $bruno->id, 'project_id' => $projects[2]->id, 'activity_id' => $activities[3]->id, 'description' => 'Documentação final', 'started_at' => $start, 'ended_at' => $start->copy()->addHour(), 'duration_seconds' => 3600, 'entry_type' => 'manual', 'status' => 'approved', 'created_by' => $bruno->id, 'approved_by' => $manager->id, 'approved_at' => now()->subDays(2), 'justification' => 'Registro concluído fora do escritório.']);
    }
}
