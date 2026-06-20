<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIntegrationClient extends Command
{
    protected $signature = 'skb:integration-client
        {key_id : ID kredensial unik}
        {source_system : Kode sistem sumber}
        {--name= : Nama aplikasi}
        {--institution-code= : Kode instansi}
        {--institution-name= : Nama instansi}
        {--environment=sandbox : sandbox atau production}
        {--scopes=connection:test,cases:read,cases:write : Scope dipisahkan koma}';

    protected $description = 'Membuat integration client dan menampilkan secret satu kali.';

    public function handle(): int
    {
        $keyId = $this->argument('key_id');
        $sourceSystem = $this->argument('source_system');
        if (DB::table('integration_clients')->where('key_id', $keyId)->exists()) {
            $this->error('key_id sudah digunakan.');

            return self::FAILURE;
        }

        $institutionId = null;
        if ($code = $this->option('institution-code')) {
            $institutionId = DB::table('institutions')->where('code', $code)->value('id') ?: (string) Str::uuid();
            DB::table('institutions')->updateOrInsert(['code' => $code], [
                'id' => $institutionId,
                'name' => $this->option('institution-name') ?: $code,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $secret = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        DB::table('integration_clients')->insert([
            'id' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'key_id' => $keyId,
            'name' => $this->option('name') ?: $sourceSystem,
            'source_system' => $sourceSystem,
            'scopes' => json_encode(array_values(array_filter(array_map('trim', explode(',', $this->option('scopes')))))),
            'secret_encrypted' => Crypt::encryptString($secret),
            'environment' => $this->option('environment'),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->warn('Simpan secret berikut sekarang. Nilai ini tidak akan ditampilkan lagi.');
        $this->line('KEY_ID='.$keyId);
        $this->line('SECRET='.$secret);

        return self::SUCCESS;
    }
}
