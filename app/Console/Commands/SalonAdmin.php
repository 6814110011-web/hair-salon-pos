<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class SalonAdmin extends Command
{
    protected $signature = 'salon:admin';

    protected $description = 'สร้างหรือกำหนดบัญชีผู้ดูแลร้านแบบโต้ตอบ';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->ask('อีเมลผู้ดูแลระบบ')));
        Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->validate();
        $user = User::where('email', $email)->first();
        if ($user) {
            if (! $this->confirm('เปลี่ยนบัญชีนี้เป็นผู้ดูแลระบบหรือไม่?')) {
                return self::SUCCESS;
            }
        } else {
            $name = $this->ask('ชื่อผู้ดูแลระบบ');
            $password = $this->secret('รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)');
            $confirmation = $this->secret('ยืนยันรหัสผ่าน');
            Validator::make(['name' => $name, 'password' => $password, 'password_confirmation' => $confirmation], [
                'name' => 'required|string|max:255', 'password' => 'required|string|min:8|confirmed',
            ])->validate();
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        }
        $user->forceFill(['role' => 'admin', 'is_active' => true])->save();
        $this->info('กำหนดผู้ดูแลระบบเรียบร้อย');

        return self::SUCCESS;
    }
}
