<?php

namespace App\Console\Commands;

use App\Enums\LanguageConfigType;
use App\Enums\WalletLogType;
use App\Enums\WalletType;
use App\Models\Language;
use App\Models\LanguageConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateLang extends Command
{

    protected $signature = 'command:CreateLang';

    protected $description = 'Command description';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $list = WalletLogType::asArray();
        foreach ($list as $item) {
            $this->info($item);
            $slug = Str::upper($item);
            $langContent = WalletLogType::fromValue($item)->description;
            foreach (Language::query()->get() as $lang) {
                $content[$lang->slug] = $langContent;
            }
            $lc =  LanguageConfig::query()->firstOrCreate(['slug' => $slug], [
                'type' => LanguageConfigType::serve,
                'name' => $langContent,
                'content' => $content,
                'group' => 'WalletLogType'
            ]);
            $lc->group = 'WalletLogType';
            $lc->save();
        }

    }
}
