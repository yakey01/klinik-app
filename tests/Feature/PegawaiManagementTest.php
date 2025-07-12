<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use Tests\TestCase;

class PegawaiManagementTest extends TestCase
{
    public function test_pegawai_model_has_correct_fillable_fields()
    {
        $pegawai = new Pegawai;
        $expectedFillable = [
            'nik',
            'nama_lengkap',
            'tanggal_lahir',
            'jenis_kelamin',
            'jabatan',
            'jenis_pegawai',
            'aktif',
            'foto',
            'input_by',
        ];

        $this->assertEquals($expectedFillable, $pegawai->getFillable());
    }

    public function test_nik_is_required_field()
    {
        $pegawai = new Pegawai;

        // NIK field should be in fillable array
        $this->assertContains('nik', $pegawai->getFillable());

        // Test that NIK and nama_lengkap are both required
        $requiredFields = ['nik', 'nama_lengkap', 'jabatan', 'jenis_pegawai'];
        foreach ($requiredFields as $field) {
            $this->assertContains($field, $pegawai->getFillable());
        }
    }

    public function test_pegawai_badge_colors_are_correct()
    {
        $pegawai = new Pegawai;

        $pegawai->jenis_pegawai = 'Paramedis';
        $this->assertEquals('primary', $pegawai->jenis_pegawai_badge_color);

        $pegawai->jenis_pegawai = 'Non-Paramedis';
        $this->assertEquals('success', $pegawai->jenis_pegawai_badge_color);
    }

    public function test_pegawai_status_badge_colors()
    {
        $pegawai = new Pegawai;

        $pegawai->aktif = true;
        $this->assertEquals('success', $pegawai->status_badge_color);

        $pegawai->aktif = false;
        $this->assertEquals('danger', $pegawai->status_badge_color);
    }

    public function test_pegawai_default_avatar_generation()
    {
        $pegawai = new Pegawai;
        $pegawai->nama_lengkap = 'John Doe';

        $expectedUrl = 'https://ui-avatars.com/api/?name='.urlencode('John Doe').'&background=3b82f6&color=fff';
        $this->assertEquals($expectedUrl, $pegawai->default_avatar);
    }

    public function test_pegawai_casts_are_correct()
    {
        $pegawai = new Pegawai;
        $casts = $pegawai->getCasts();

        $this->assertEquals('date', $casts['tanggal_lahir']);
        $this->assertEquals('boolean', $casts['aktif']);
    }
}
