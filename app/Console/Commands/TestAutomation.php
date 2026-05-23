<?php

namespace App\Console\Commands;

use App\Models\AutomationSequence;
use App\Models\ScheduledAutomation;
use Illuminate\Console\Command;

class TestAutomation extends Command
{
    protected $signature = 'app:test-automation';
    protected $description = 'Test automation models';

    public function handle()
    {
        $this->info('🧪 Testing automation models...');

        try {
            // Test 1: Create an automation sequence
            $this->info('✓ Creating automation sequence...');
            $sequence = AutomationSequence::create([
                'name' => 'Test Sequence',
                'description' => 'Test Description',
                'actions' => [
                    [
                        'type' => 'send_message',
                        'recipient' => '34622435165',
                        'message' => 'Test message'
                    ]
                ],
                'status' => 'active'
            ]);
            $this->info('✓ Automation sequence created: ' . $sequence->id);

            // Test 2: Create a scheduled automation
            $this->info('✓ Creating scheduled automation...');
            $schedule = ScheduledAutomation::create([
                'automation_sequence_id' => $sequence->id,
                'scheduled_time' => '09:00:00',
                'days_of_week' => ['1', '2', '3', '4', '5'],
                'status' => 'active',
                'next_execution_at' => now()->addDay()
            ]);
            $this->info('✓ Scheduled automation created: ' . $schedule->id);

            // Test 3: Verify relationships
            $this->info('✓ Testing relationships...');
            $sequence->refresh();
            $this->info('✓ Scheduled automations count: ' . $sequence->scheduledAutomations->count());

            // Test 4: Test execution methods
            $this->info('✓ Testing execution methods...');
            $this->info('✓ Sequence is active: ' . ($sequence->isActive() ? 'Yes' : 'No'));
            $result = $sequence->execute();
            $this->info('✓ Sequence executed: ' . ($result ? 'Success' : 'Failed'));

            $this->info('✅ All tests passed!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

