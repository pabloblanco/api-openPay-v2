<?php

namespace App\Console\Commands;

use App\Bash;
use Illuminate\Console\Command;

class CheckProcess extends Command
{
  /**
   * The console command name.
   *
   * @var string
   */
  protected $signature = "command:checkprocess";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Verifica si el comando de recargas tiene mas de un tiempo definido en TTL_CRON ejecutandose.";

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {
    if(Bash::isActive()){
      $data = Bash::getBash();

      if(strtotime('+ ' . env('TTL_CRON', 15) . ' minutes', strtotime($data->date_begin)) <= time()){
        Bash::inactive();
      }
    }
  }
}
