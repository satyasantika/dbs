<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GuideGroup;
use App\Models\SelectionStage;
use App\Models\GuideAllocation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GuideAllocationGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Import Data kuota keseluruhan
        $csvData = fopen(base_path('/database/seeders/csvs/guideallocation2023.csv'), 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                GuideAllocation::create([
                    'user_id'  => User::where('initial',$data[0])->first()->id,
                    'year'      => 2023,
                    'guide1_quota'     => $data[1],
                    'guide2_quota'     => $data[2],
                    'active' => $data[3],
                ]);
            }
            $transRow = false;
        }
        fclose($csvData);

        // Import Data kuota pasangan
        $csvData = fopen(base_path('/database/seeders/csvs/guidegroup2023.csv'), 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                GuideGroup::create([
                    'group'      => $data[0],
                    'guide_allocation_id'  => GuideAllocation::where('user_id',User::where('initial',$data[1])->first()->id)->first()->id,
                    'guide1_quota'     => $data[2],
                    'guide2_quota'     => $data[3],
                    'active' => $data[4],
                ]);
            }
            $transRow = false;
        }
        fclose($csvData);

        // Import hasil tahap 1
        $csvData = fopen(base_path('/database/seeders/csvs/hasiltahap1.csv'), 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                $stage = SelectionStage::create([
                    'user_id' => User::where('username',$data[0])->first()->id,
                    'stage_order'=>1,
                    'guide1_id'=>User::where('initial',$data[1])->first()->id,
                    'guide2_id'=>User::where('initial',$data[2])->first()->id,
                    'final'=>1,
                ]);
                $stage->guides()->create([
                    'guide_group_id'  => 1,
                    'user_id'     => User::where('initial',$data[1])->first()->id,
                    'guide_order'     => 1,
                    'approved' => 1,
                ]);
                $stage->guides()->create([
                    'guide_group_id'  => 1,
                    'user_id'     => User::where('initial',$data[2])->first()->id,
                    'guide_order'     => 2,
                    'approved' => 1,
                ]);
            }
            $transRow = false;
        }
        fclose($csvData);
    }
}
