<?php

namespace Tests\Feature;

use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpenWACollaborationRecentMessagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', '3366');
        config()->set('database.connections.mysql.database', 'gestoria');
        config()->set('database.connections.mysql.username', 'gestoria');
        config()->set('database.connections.mysql.password', '1234');

        DB::purge('mysql');
        DB::reconnect('mysql');

        /** @lang MySQL */
        $dropTempTableSql = 'DROP TEMPORARY TABLE IF EXISTS whatsapp_messages';
        DB::connection('mysql')->statement($dropTempTableSql);

        /** @lang MySQL */
        $createTempTableSql = <<<'SQL'
            CREATE TEMPORARY TABLE whatsapp_messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                session_id VARCHAR(255) NULL,
                chat_id VARCHAR(255) NOT NULL,
                direction VARCHAR(255) NULL,
                message_id VARCHAR(255) NULL,
                text TEXT NULL,
                payload JSON NULL,
                status VARCHAR(255) NULL,
                error_message TEXT NULL,
                sent_at TIMESTAMP NULL,
                received_at TIMESTAMP NULL,
                delivered_at TIMESTAMP NULL,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
SQL;
        DB::connection('mysql')->statement($createTempTableSql);
    }

    protected function tearDown(): void
    {
        try {
            /** @lang MySQL */
            $dropTempTableSql = 'DROP TEMPORARY TABLE IF EXISTS whatsapp_messages';
            DB::connection('mysql')->statement($dropTempTableSql);
        } catch (\Throwable $e) {
            // Ignorar limpieza si la conexión ya se cerró.
        }

        parent::tearDown();
    }

    public function test_recent_messages_partial_renders_message_statuses(): void
    {
        $this->withoutMiddleware();

        WhatsappMessage::create([
            'chat_id' => '34600111222@c.us',
            'text' => 'Mensaje pendiente',
            'direction' => 'outbound',
            'status' => 'pending',
        ]);

        WhatsappMessage::create([
            'chat_id' => '34600111333@c.us',
            'text' => 'Mensaje enviado',
            'direction' => 'outbound',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        WhatsappMessage::create([
            'chat_id' => '34600111444@c.us',
            'text' => 'Mensaje fallido',
            'direction' => 'outbound',
            'status' => 'failed',
            'error_message' => 'OpenWA unavailable',
        ]);

        $response = $this->get(route('openwa.collab.recent-messages'));

        $response->assertOk();
        $response->assertSee('Pending', false);
        $response->assertSee('Sent', false);
        $response->assertSee('Failed', false);
        $response->assertSee('OpenWA unavailable', false);
    }
}





