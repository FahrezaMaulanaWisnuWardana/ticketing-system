<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tickets DROP COLUMN IF EXISTS search');
        DB::statement('ALTER TABLE tickets ADD COLUMN search tsvector');
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION tickets_refresh_search(p_ticket_id bigint) RETURNS void AS $$
            BEGIN
                UPDATE tickets SET search =
                    setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
                    setweight(to_tsvector('simple', coalesce(description, '')), 'B') ||
                    setweight(to_tsvector('simple', coalesce((
                        SELECT string_agg(body, ' ') FROM comments
                        WHERE ticket_id = p_ticket_id AND deleted_at IS NULL
                    ), '')), 'C')
                WHERE id = p_ticket_id;
            END;
            $$ LANGUAGE plpgsql
        SQL);
        DB::statement('DROP TRIGGER IF EXISTS tickets_search_trigger ON tickets');
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION tickets_search_trigger() RETURNS trigger AS $$
            BEGIN
                PERFORM tickets_refresh_search(NEW.id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        SQL);
        DB::statement(<<<'SQL'
            CREATE TRIGGER tickets_search_trigger AFTER INSERT OR UPDATE OF title, description ON tickets
            FOR EACH ROW EXECUTE FUNCTION tickets_search_trigger()
        SQL);
        DB::statement('DROP TRIGGER IF EXISTS comments_search_trigger ON comments');
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION comments_search_trigger() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    PERFORM tickets_refresh_search(OLD.ticket_id);
                    RETURN OLD;
                END IF;
                PERFORM tickets_refresh_search(NEW.ticket_id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        SQL);
        DB::statement(<<<'SQL'
            CREATE TRIGGER comments_search_trigger AFTER INSERT OR UPDATE OF body, deleted_at OR DELETE ON comments
            FOR EACH ROW EXECUTE FUNCTION comments_search_trigger()
        SQL);
        DB::statement('CREATE INDEX tickets_search_idx ON tickets USING gin (search)');
        DB::statement(<<<'SQL'
            UPDATE tickets SET search =
                setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('simple', coalesce(description, '')), 'B') ||
                setweight(to_tsvector('simple', coalesce((
                    SELECT string_agg(body, ' ') FROM comments
                    WHERE ticket_id = tickets.id AND deleted_at IS NULL
                ), '')), 'C')
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS comments_search_trigger ON comments');
        DB::statement('DROP FUNCTION IF EXISTS comments_search_trigger()');
        DB::statement('DROP TRIGGER IF EXISTS tickets_search_trigger ON tickets');
        DB::statement('DROP FUNCTION IF EXISTS tickets_search_trigger()');
        DB::statement('DROP FUNCTION IF EXISTS tickets_refresh_search(bigint)');
        DB::statement('DROP INDEX IF EXISTS tickets_search_idx');
        DB::statement('ALTER TABLE tickets DROP COLUMN IF EXISTS search');
    }
};
