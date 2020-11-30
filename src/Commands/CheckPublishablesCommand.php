<?php

namespace SkoreLabs\LaravelTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPublishablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check vendor dependencies in search of local outdated dependencies';

    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $filesystem;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->filesystem = Storage::createLocalDriver(['root' => base_path()]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $packagesArr = ServiceProvider::pathsToPublish();
        $publishablesArr = [];

        foreach ($packagesArr as $origin => $destination) {
            if (is_dir($origin)) {
                $originFiles = $this->filesystem->allFiles(str_replace(trim(base_path(' ')), '', $origin));

                foreach ($originFiles as $file) {
                    $publishablesArr[] = $this->getFileDiff($file, base_path(last(explode($origin, $file))));
                }
            } else {
                $publishablesArr[] = $this->getFileDiff($origin, $destination);
            }
        }

        $publishablesArr = array_filter($publishablesArr);
        $headers = ['from_path', 'path', 'last_updated'];

        $this->table(array_map('strtoupper', $headers), array_map(fn ($val) => Arr::only($val, $headers), $publishablesArr));

        // if ($this->confirm('Do you want to publish this packages?')) {
        //     //
        // }

        return 0;
    }

    protected function getFileDiff($origin, $destination)
    {
        $originPath = $this->getPathForStorage($origin);
        $destinationPath = $this->getPathForStorage($destination);

        if (!file_exists($destination)) {
            $this->info("${originPath} is not present in your app, you may want to customise or not (optional)", OutputInterface::VERBOSITY_VERBOSE);

            return [];
        }

        $originHash = sha1_file($origin);
        $destinationHash = sha1_file($destination);

        if ($originHash !== $destinationHash) {
            $this->info("${originHash} compared to ${destinationHash}", OutputInterface::VERBOSITY_VERBOSE);
            $this->warn("${destinationPath} is outdated!", OutputInterface::VERBOSITY_VERBOSE);

            return [
                'from' => $origin,
                'from_path' => $originPath,
                'fullpath' => $destination,
                'path' => $destinationPath,
                'last_updated' => Carbon::createFromTimestamp($this->filesystem->lastModified($this->getPathForStorage($origin)))->diffForHumans(
                    Carbon::createFromTimestamp($this->filesystem->lastModified($this->getPathForStorage($destination)))
                ),
            ];
        }

        return [];
    }

    protected function getPathForStorage($path)
    {
        return str_replace(trim(base_path(' ')), '', $path);
    }
}
